@extends('layouts.app')

@section('title', 'Admin • Settings')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <style>
        .subaccount-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .subaccount-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .subaccount-card.border-primary {
            border-color: var(--primary-color) !important;
            border-width: 2px !important;
        }

        .select2-container {
            z-index: 1050;
        }

        .select2-container--default .select2-results__option {
            padding: 12px;
        }

        .alert-sm {
            padding: 8px 12px;
            font-size: 0.875rem;
        }
    </style>
@endpush

@section('content')
    @php
        $integrationBase = 'admin.setting.component.integration.';
        $saveRoot = route('admin.settings.save');
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Admin Settings</h1>
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    {{-- Flash messages --}}
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-bold mb-1">Please fix the following:</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row mt-2">
        @include($integrationBase . 'autologin', ['type' => 'sso'])

        <div class="col-md-12">
            <form action="{{ $saveRoot }}" class="submitForm" method="POST">
                @csrf
                <div class="row mt-2">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">CRM OAuth Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <h6>Redirect URI - add while creating app</h6>
                                        <p class="h6">{{ route('crm.oauth_callback') }}</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <h6>Scopes - select while creating app</h6>
                                        <p class="h6"> {{ $scopes }} </p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <h6>* Note - App distribution Agency and Subaccount both!</h6>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="clientID" class="form-label">Client ID</label>
                                            <input type="text" class="form-control"
                                                value="{{ $settings['crm_client_id'] ?? '' }}" id="crm_client_id"
                                                name="setting[crm_client_id]" aria-describedby="clientID" required>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="clientSecret" class="form-label">Client Secret</label>
                                            <input type="text" class="form-control"
                                                value="{{ $settings['crm_client_secret'] ?? '' }}" id="crm_secret_id"
                                                name="setting[crm_client_secret]" aria-describedby="secretID" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 m-2">
                                        <button id="form_submit" class="btn btn-primary">Save</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>

        <!-- Agency Connection Status -->
        <div class="col-md-12 mt-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plug me-2"></i>
                        CRM OAuth Connectivity
                    </h5>
                </div>
                <div class="card-body">
                    <div id="connection-status" class="d-flex align-items-center">
                        <div class="spinner-border text-primary me-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span>Checking connection status...</span>
                    </div>
                    <div class="ml-2">
                        <p></p>
                        @if ($company_name && $company_id)
                            <p>Company: <span class="fw-bold">{{ $company_name }}</span></p>
                            <p>Company ID: <span class="fw-bold">{{ $company_id }}</span></p>
                        @endif

                        @php($connect = $company_id ? 'Reconnect' : 'Connect')
                        <a class="btn btn-primary" href="{{ $connecturl }}">{{ $connect }} with
                            Agency</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Primary Subaccount Selection -->
        <div class="col-md-12 mt-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-building me-2"></i>
                        Select Primary Subaccount
                    </h5>
                </div>
                <div class="card-body">
                    <div id="agency-not-connected" class="alert alert-warning d-none" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Agency Not Connected!</strong> Please connect your GoHighLevel agency first to view
                        subaccounts.
                        <div class="mt-3">
                            <a href="{{ $connecturl }}" class="btn btn-warning">
                                <i class="fas fa-link me-2"></i>
                                Connect Agency
                            </a>
                        </div>
                    </div>

                    <div id="subaccounts-container" class="d-none">
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <label for="subaccount-select" class="form-label">
                                    <strong>Choose Primary Subaccount:</strong>
                                </label>
                                <select id="subaccount-select" class="form-select" style="width: 100%;">
                                    <option value="">Loading subaccounts...</option>
                                </select>
                                <div class="form-text">
                                    The primary subaccount will be used for product and pricing configuration.
                                </div>
                            </div>
                            <div class="col-md-4 d-flex align-items-center">
                                <button id="set-primary-btn" class="btn btn-success w-100" disabled
                                    onclick="setPrimarySubaccount()">
                                    <i class="fas fa-check me-2"></i>
                                    <span class="loading-text">Set as Primary</span>
                                    <span class="loading-spinner spinner-border spinner-border-sm" role="status"></span>
                                </button>
                            </div>
                        </div>

                        <div id="current-primary" class="alert alert-info d-none">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Current Primary:</strong> <span id="current-primary-name"></span>
                        </div>

                        <!-- Subaccounts Grid -->
                        {{--
                            <div id="subaccounts-grid" class="row">
                                <!-- Subaccount cards will be populated here -->
                            </div>
                            --}}
                    </div>

                    <div id="loading-subaccounts" class="text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div>Loading subaccounts...</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12 mt-4">
            <form action="{{ $saveRoot }}" class="submitForm" method="POST">
                @csrf
                <div class="row mt-2">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Stripe Secret Key</h5>
                            </div>
                            <div class="card-body">

                                <div class="row mt-2">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="clientID" class="form-label">Stripe Secret Key</label>
                                            <input type="text" class="form-control"
                                                value="{{ $settings['stripe_secret_key'] ?? '' }}" id="stripe_secret_key"
                                                name="setting[stripe_secret_key]" aria-describedby="Stripe Secrct Key"
                                                required>
                                        </div>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-md-12 m-2">
                                        <button id="form_submit" class="btn btn-primary">Save</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>


        {{-- TODO: make sepaerete comment for this user updata form --}}
        <div class="col-md-12 mt-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="h4">Update Profile</h4>
                </div>
                <div class="card-body">
                    <div class="copy-container">
                        <form class="submitForm" action="{{ route('admin.setting.user.profile', $authuser) }}"
                            method="PUT">
                            @csrf
                            <div class="mb-3">
                                <label for="username" class="form-label">Enter UserName</label>
                                <input type="text" name="username" class="form-control"
                                    value="{{ $authuser->name }}">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Enter Email</label>
                                <input type="email" name="email" class="form-control" id="example"
                                    aria-describedby="emailHelp" value="{{ $authuser->email }}">

                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Enter Password</label>
                                <input type="password" name="password" class="form-control" id="password">
                            </div>
                            <button type="submit" id="submitButton" class="btn btn-primary submit_btn">Save</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- @include('components.crm-webhook-details') --}}
    </div>

@endsection

@push('scripts')
    <script>
        // Copy URL functionality
        $("body").on('click', '.copy_url', function(e) {
            e.preventDefault();
            let msg = $(this).data('message') ?? 'Copied';
            let url = $(this).data('href') ?? "";
            if (url === '') {
                url = $(this).closest('.copy-container').find('.code_url').val();
            }
            try {
                if (url) {
                    navigator.clipboard.writeText(url).then(() => {
                        toastr.success(msg);
                    }, () => {
                        toastr.error('Error while copying');
                    }).catch(() => {
                        toastr.error('Request denied');
                    });
                } else {
                    toastr.error('No data found to copy');
                }
            } catch (error) {
                toastr.error('Unable to copy');
            }
        });

        // Form submission
        $(document).ready(function() {
            $('body').on('submit', '.submitForm', function(e) {
                e.preventDefault();
                const $form = $(this);
                const data = $form.serialize();
                const url = $form.attr('action');
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            toastr.success('Settings saved successfully');
                            if (response.view) {
                                $('.appendBody').html(response.view);
                            }
                            $('#sourceModal').modal('hide');
                        } else {
                            toastr.error(response.message || 'Failed to save settings');
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'An error occurred');
                    }
                });
            });

            // Initialize Select2 for subaccount selection
            $('#subaccount-select').select2({
                placeholder: 'Select a subaccount...',
                allowClear: true,
                templateResult: formatSubaccount,
                templateSelection: formatSubaccountSelection
            });

            // Handle selection change
            $('#subaccount-select').on('change', function() {
                const selectedValue = $(this).val();
                $('#set-primary-btn').prop('disabled', !selectedValue);
            });


            // Check agency connection and load subaccounts
            checkAgencyConnection();
            loadSubaccounts();
        });

        // Subaccount selection JavaScript
        let subaccountsData = [];
        //let currentPrimary = null;
        let currentPrimary = '{{ $settings['primary_subaccount'] ?? null }}';

        function checkAgencyConnection() {
            const isConnected = !!'{{ $company_id }}';
            $('#connection-status').html(
                isConnected ?
                '<i class="fas fa-check-circle text-success me-2"></i><span class="text-success">Agency Connected Successfully</span>' :
                '<i class="fas fa-times-circle text-danger me-2"></i><span class="text-danger">Agency Not Connected</span>'
            );

            if (isConnected) {
                $('#subaccounts-container').removeClass('d-none');
            } else {
                $('#agency-not-connected').removeClass('d-none');
                $('#loading-subaccounts').addClass('d-none');
            }
        }

        function loadSubaccounts() {
            $.ajax({
                url: '{{ route('admin.settings.subaccounts') }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        subaccountsData = response.data;

                        // First load current primary, then populate
                        //loadCurrentPrimary().then(() => {
                        //   populateSubaccounts(subaccountsData);
                        //});

                        updateCurrentPrimaryDisplay();
                        populateSubaccounts(subaccountsData);

                    } else {
                        toastr.error(response.message);
                        $('#agency-not-connected').removeClass('d-none');
                    }
                },
                error: function(xhr) {
                    handleAjaxError(xhr);
                    if (xhr.status === 400) {
                        $('#agency-not-connected').removeClass('d-none');
                    }
                },
                complete: function() {
                    $('#loading-subaccounts').addClass('d-none');
                }
            });
        }

        function populateSubaccounts(subaccounts) {
            const select = $('#subaccount-select');
            select.empty();
            select.append('<option value="">Select a subaccount...</option>');

            subaccounts.forEach(subaccount => {
                select.append(new Option(subaccount.name, subaccount.id, false, subaccount.id == currentPrimary));
            });

            /*
            const grid = $('#subaccounts-grid');
            grid.empty();

            subaccounts.forEach(subaccount => {
                const card = createSubaccountCard(subaccount);
                grid.append(card);
            });
            */
        }

        function createSubaccountCard(subaccount) {
            return `
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 subaccount-card" data-id="${subaccount.id}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h6 class="card-title mb-0">${subaccount.name}</h6>
                                <span class="badge bg-light text-dark">${subaccount.id}</span>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    ${subaccount.address || 'No address'}
                                </small>
                                <small class="text-muted d-block">
                                    <i class="fas fa-phone me-1"></i>
                                    ${subaccount.phone || 'No phone'}
                                </small>
                                <small class="text-muted d-block">
                                    <i class="fas fa-envelope me-1"></i>
                                    ${subaccount.email || 'No email'}
                                </small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    ${subaccount.timezone || 'UTC'}
                                </small>
                                <button class="btn btn-sm btn-outline-primary" onclick="selectSubaccount('${subaccount.id}')">
                                    <i class="fas fa-check me-1"></i>
                                    Select
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function formatSubaccount(subaccount) {
            if (!subaccount.id) return subaccount.text;
            const data = subaccountsData.find(s => s.id === subaccount.id);
            if (!data) return subaccount.text;
            return $(`
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="fw-bold">${data.name} (${data.email})</div>
                        <small class="text-muted">${data.address || 'No address'}</small>
                    </div>
                    <small class="badge bg-light text-dark ms-2">${data.id}</small>
                </div>
            `);
        }

        function formatSubaccountSelection(subaccount) {
            if (!subaccount.id) return subaccount.text;
            const data = subaccountsData.find(s => s.id === subaccount.id);
            return data ? `${data.name} (${data.email})` : subaccount.text;
        }

        function selectSubaccount(subaccountId) {
            $('#subaccount-select').val(subaccountId).trigger('change');
            $('.subaccount-card').removeClass('border-primary');
            $(`.subaccount-card[data-id="${subaccountId}"]`).addClass('border-primary');
        }

        function setPrimarySubaccount() {
            const selectedId = $('#subaccount-select').val();
            if (!selectedId) {
                toastr.warning('Please select a subaccount first.');
                return;
            }
            const btn = $('#set-primary-btn');
            showLoading(btn);
            $.ajax({
                url: '{{ route('admin.settings.subaccounts.set-primary') }}',
                method: 'POST',
                data: {
                    location_id: selectedId
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Primary subaccount set successfully!');
                        currentPrimary = selectedId;
                        updateCurrentPrimaryDisplay();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: handleAjaxError,
                complete: function() {
                    hideLoading(btn);
                }
            });
        }

        function loadCurrentPrimary() {
            $.ajax({
                url: '{{ route('admin.settings.subaccounts.primary') }}',
                method: 'GET',
                success: function(response) {
                    if (response.success && response.data.location_id) {
                        currentPrimary = response.data.location_id;
                        updateCurrentPrimaryDisplay();
                    }
                },
                error: function() {
                    // Silently handle error - no current primary set
                }
            });
        }

        function updateCurrentPrimaryDisplay() {
            if (currentPrimary) {
                const primaryData = subaccountsData.find(s => s.id === currentPrimary);
                if (primaryData) {
                    $('#current-primary-name').text(`${primaryData.name} (${primaryData.id})`);
                    $('#current-primary').removeClass('d-none');
                } else {
                    // If saved ID doesn't exist anymore, hide display
                    $('#current-primary').addClass('d-none');
                }
            } else {
                // If no primary selected, hide display
                $('#current-primary').addClass('d-none');
            }
        }
    </script>
@endpush
