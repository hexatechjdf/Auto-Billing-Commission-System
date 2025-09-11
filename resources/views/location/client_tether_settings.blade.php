@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3">
        <h2 class="mb-4 fw-bold">Client Tether Settings & User Mappings</h2>

        <form id="settings-form">
            @csrf
            <!-- Hidden Default Event Type -->
            <input type="hidden" name="default_event_type" value="{{ $settings->default_event_type ?? 'appointment' }}">

            <!-- Settings Card -->
            <div class="mb-4 bg-white shadow-sm rounded">
                <div class="card-header bg-gradient-primary text-white p-3">
                    <h4 class="mb-0 fw-semibold">Settings</h4>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3 position-relative">
                        <label for="default_user_id" class="form-label fw-medium" data-bs-toggle="tooltip"
                            title="The default user for appointments when no mapping is found">Default Client Tether
                            User</label>
                        <select class="form-control select2" id="default_user_id" name="default_user_id" required disabled>
                            <option value="">Select Default User</option>
                        </select>
                        <div class="default-user-loader loader">Loading users...</div>
                    </div>
                    <div class="mb-3 position-relative">
                        <label for="timezone" class="form-label fw-medium" data-bs-toggle="tooltip"
                            title="The timezone for appointment scheduling">Timezone</label>
                        <select class="form-control select2" id="timezone" name="timezone" required>
                            <option value="">Select Timezone</option>
                            @foreach (getTimezones() as $code => $name)
                                <option value="{{ $code }}" {{ $settings->timezone == $code ? 'selected' : '' }}>
                                    {{ $name }} ({{ $code }})</option>
                            @endforeach
                        </select>
                        <div class="timezone-loader loader d-none">Loading timezone...</div>
                    </div>
                </div>
            </div>

            <!-- User Mappings Card -->
            <div class="mb-4 bg-white shadow-sm rounded position-relative">
                <div class="card-header bg-gradient-primary text-white p-3">
                    <h4 class="mb-0 fw-semibold">CRM to Client Tether User Mappings</h4>
                </div>
                <div class="card-body p-4">
                    <div id="user-mappings" class="mb-3">
                        <div class="mappings-loader loader">Loading users...</div>
                    </div>
                </div>
            </div>

            <!-- Sticky Footer for Buttons -->
            <div class="sticky-footer bg-white shadow-sm p-3 d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-secondary fw-medium" id="clear-mappings" disabled>Clear All
                    Mappings</button>
                <button type="submit" class="btn btn-primary fw-medium" id="save-btn" disabled>Save Settings &
                    Mappings</button>
            </div>
        </form>
    </div>

    @push('style')
        <style>
            /* User Mapping Rows */
            .user-mapping-row {
                display: flex;
                align-items: center;
                gap: 15px;
                margin-bottom: 10px;
                padding: 8px;
                border-radius: 5px;
                transition: background-color 0.3s ease;
            }

            .user-mapping-row:hover {
                background-color: #f8f9fa;
            }

            .user-mapping-label {
                flex: 1;
                font-weight: 500;
                color: #343a40;
            }

            .user-mapping-select {
                flex: 1;
                min-width: 200px;
            }

            /* Responsive Design for User Mappings */
            @media (max-width: 768px) {
                .user-mapping-row {
                    flex-direction: column;
                    align-items: stretch;
                }

                .user-mapping-select {
                    min-width: 100%;
                }
            }
        </style>
    @endpush

    @push('script')
        <script>
            $(document).ready(function() {
                // Initialize tooltips
                $('[data-bs-toggle="tooltip"]').tooltip();

                // Initialize Select2
                $('.select2').select2({
                    placeholder: function() {
                        return $(this).attr('id') === 'timezone' ? 'Select Timezone' : 'Select a user';
                    },
                    allowClear: true,
                    theme: "bootstrap5"
                });

                // Show loaders and disable selections
                $('.default-user-loader').show();
                $('.mappings-loader').show();
                $('#default_user_id').prop('disabled', true);
                // $('#timezone').prop('disabled', true);
                $('#clear-mappings').prop('disabled', true);
                $('#save-btn').prop('disabled', true);

                // Counter to track Select2 change events
                let changeEventsTriggered = 0;
                let totalSelects = 0;

                // Function to enable selections after all Select2 changes
                function checkAllChanges() {
                    changeEventsTriggered++;
                    if (changeEventsTriggered >= totalSelects) {
                        $('#default_user_id').prop('disabled', false);
                        // $('#timezone').prop('disabled', false);
                        $('select[name^="user_map"]').prop('disabled', false);
                        $('#clear-mappings').prop('disabled', false);
                        $('#save-btn').prop('disabled', false);
                    }
                }

                // Fetch GHL users
                $.ajax({
                    url: '{{ route('location.crm-users', $locationId) }}',
                    method: 'GET',
                    success: function(response) {
                        $('.mappings-loader').hide();
                        const ghlUsers = response.users;
                        const userMap = @json($settings->user_map ?? []);
                        let html = '';
                        ghlUsers.forEach(user => {
                            html += `
                        <div class="user-mapping-row">
                            <div class="user-mapping-label">${user.name}</div>
                            <div class="user-mapping-select">
                                <select name="user_map[${user.id}]" class="form-control select2" disabled>
                                    <option value="">--Select Client Tether User--</option>
                                </select>
                            </div>
                        </div>
                    `;
                        });
                        $('#user-mappings').html(html);
                        $('.select2').select2({
                            placeholder: "Select a user",
                            allowClear: true,
                            theme: "bootstrap5"
                        });

                        // Count total Select2 elements
                        totalSelects = $('select[name^="user_map"]').length +
                            2; // +2 for default_user_id and timezone

                        // Attach change event listeners
                        $('select[name^="user_map"]').on('change', checkAllChanges);
                        $('#default_user_id').on('change', checkAllChanges);
                        $('#timezone').on('change', checkAllChanges);

                        // Fetch Client Tether users
                        $.ajax({
                            url: '{{ route('location.ct-users', $locationId) }}',
                            method: 'GET',
                            success: function(response) {
                                $('.default-user-loader').hide();
                                const ctUsers = response.users;
                                $('select[name^="user_map"]').each(function() {
                                    const $select = $(this);
                                    ctUsers.forEach(user => {
                                        $select.append(
                                            `<option value="${user.id}">${user.name}</option>`
                                        );
                                    });
                                    const ghlUserId = $select.attr('name').match(
                                        /\[(.+)\]/)[1];
                                    if (userMap[ghlUserId]) {
                                        $select.val(userMap[ghlUserId]).trigger(
                                            'change');
                                    } else {
                                        checkAllChanges
                                    (); // Trigger for unmapped selects
                                    }
                                });
                                $('#default_user_id').empty().append(
                                    '<option value="">Select Default User</option>');
                                ctUsers.forEach(user => {
                                    $('#default_user_id').append(
                                        `<option value="${user.id}">${user.name}</option>`
                                    );
                                });
                                $('#default_user_id').val(
                                    '{{ $settings->default_user_id ?? '' }}').trigger(
                                    'change');

                                // Trigger change for timezone (already populated in Blade)
                                $('#timezone').val('{{ $settings->timezone ?? 'MDT' }}')
                                    .trigger('change');
                            },
                            error: function(xhr) {
                                $('.default-user-loader').hide();
                                $('.mappings-loader').hide();
                                $('#default_user_id').prop('disabled', false);
                                $('#timezone').prop('disabled', false);
                                $('select[name^="user_map"]').prop('disabled', false);
                                $('#clear-mappings').prop('disabled', false);
                                $('#save-btn').prop('disabled', false);
                                toastr.error('Error fetching Client Tether users: ' + (xhr
                                    .responseJSON?.message || 'Unknown error'));
                            }
                        });
                    },
                    error: function(xhr) {
                        $('.default-user-loader').hide();
                        $('.mappings-loader').hide();
                        $('#default_user_id').prop('disabled', false);
                        $('#timezone').prop('disabled', false);
                        $('select[name^="user_map"]').prop('disabled', false);
                        $('#clear-mappings').prop('disabled', false);
                        $('#save-btn').prop('disabled', false);
                        toastr.error('Error fetching GHL users: ' + (xhr.responseJSON?.message ||
                            'Unknown error'));
                    }
                });

                // Form submission with SweetAlert2 confirmation
                $('#settings-form').submit(function(e) {
                    e.preventDefault();
                    const formData = $(this).serializeArray();
                    console.log('Form Data:', formData); // Debug form data
                    Swal.fire({
                        title: 'Confirm Save',
                        text: 'Are you sure you want to save these settings and mappings?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Save',
                        cancelButtonText: 'Cancel',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-primary mx-2',
                            cancelButton: 'btn btn-outline-secondary mx-2'
                        }
                    }).then((result) => {
                        if (result.value) {
                            $('#save-btn').prop('disabled', true).text('Saving...');
                            $.ajax({
                                url: '{{ route('location.ct-settings.save', $locationId) }}',
                                method: 'POST',
                                data: formData,
                                success: function(response) {
                                    toastr.success(
                                        'Settings and mappings saved successfully!');
                                    $('#save-btn').prop('disabled', false).text(
                                        'Save Settings & Mappings');
                                },
                                error: function(xhr) {
                                    toastr.error('Error saving settings: ' + (xhr
                                        .responseJSON?.message || 'Unknown error'));
                                    $('#save-btn').prop('disabled', false).text(
                                        'Save Settings & Mappings');
                                }
                            });
                        }
                    });
                });

                // Clear all mappings
                $('#clear-mappings').click(function() {
                    Swal.fire({
                        title: 'Clear All Mappings',
                        text: 'Are you sure you want to clear all user mappings? This will not affect the default user.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Clear',
                        cancelButtonText: 'Cancel',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-danger mx-2',
                            cancelButton: 'btn btn-outline-secondary mx-2'
                        }
                    }).then((result) => {
                        if (result.value) {
                            $('select[name^="user_map"]').val('').trigger('change');
                            toastr.success('All mappings cleared!');
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
