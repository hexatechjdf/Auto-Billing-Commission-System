@extends('layouts.app')

@section('title', 'Subaccounts - Auto-Billing Commission System')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Subaccounts Management</h1>
        <div>
            @if ($isAgencyConnected)
                <button class="btn btn-outline-primary me-2" id="sync-subaccounts" onclick="syncSubaccounts()">
                    <i class="fas fa-cloud-download-alt me-2"></i>
                    <span class="loading-text">Sync Subaccounts</span>
                    <span class="loading-spinner spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
                <button class="btn btn-outline-primary me-2" id="assign-plans" onclick="openAssignPlansModal()">
                    <i class="fas fa-list-alt me-2"></i>
                    <span class="loading-text">Assign Plans</span>
                    <span class="loading-spinner spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            @endif
            <button class="btn btn-outline-primary" id="refresh-subaccounts" onclick="refreshSubaccounts()">
                <i class="fas fa-sync-alt me-2"></i>
                <span class="loading-text">Refresh</span>
                <span class="loading-spinner spinner-border spinner-border-sm d-none" role="status"></span>
            </button>
        </div>
    </div>

    <!-- Filters -->
    {{-- <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter me-2"></i>
                Filters
            </h5>
        </div>
        <div class="card-body">
            <form id="subaccount-filters" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" id="subaccounts-search" class="form-control" placeholder="Search by location ID or email">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select id="status-filter" class="form-control">
                        <option value="">All</option>
                        <option value="0">Active</option>
                        <option value="1">Paused</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Chargeable</label>
                    <select id="chargeable-filter" class="form-control">
                        <option value="">All</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>
    --}}

    <!-- Loading State -->
    <div id="loading-subaccounts" class="text-center py-5">
        <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div>Loading subaccounts...</div>
    </div>

    <!-- Subaccounts Content -->
    <div id="subaccounts-content" class="d-none">
        <!-- Empty State -->
        <div id="empty-subaccounts" class="text-center py-5 d-none">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No Subaccounts Found</h5>
            <p class="text-muted">Subaccounts will appear once they are created via webhooks.</p>
        </div>

        <!-- Subaccounts Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-building me-2"></i>
                    Connected Subaccounts
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="subaccounts-table" class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>Location ID</th>
                                <th>Email</th>
                                <th>Chargeable</th>
                                <th>Allow Uninstall</th>
                                <th>Stripe Payment Method ID</th>
                                <th>Stripe Customer ID</th>
                                <th>Contact ID</th>
                                <th>Charge %</th>
                                <th>Threshold Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Table content will be populated by DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Subaccount Modal -->
    <div class="modal fade" id="editSubaccountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Subaccount</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="edit-subaccount-form">
                        <input type="hidden" id="edit-subaccount-id">
                        <div class="mb-3">
                            <label class="form-label">Location ID</label>
                            <input type="text" id="edit-location-id" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" id="edit-email" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact phone</label>
                            <input type="text" id="edit-contact-phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="edit-chargeable" class="form-label">Chargeable</label>
                            <select id="edit-chargeable" class="form-control" required>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit-allow-uninstall" class="form-label">Allow Uninstall</label>
                            <select id="edit-allow-uninstall" class="form-control" required>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit-charge-percent" class="form-label">Charge Percentage (%)</label>
                            <input type="number" id="edit-charge-percent" class="form-control" step="0.01"
                                min="0" max="100" required>
                            <div class="form-text">Percentage to charge as commission</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit-threshold-amount" class="form-label">Threshold Amount</label>
                            <input type="number" id="edit-threshold-amount" class="form-control" step="0.01"
                                min="0" required>
                            <div class="form-text">Minimum commission threshold</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit-currency" class="form-label">Currency</label>
                            <select id="edit-currency" class="form-control" required>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stripe Payment Method ID</label>
                            <input type="text" id="edit-stripe-payment-method-id" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stripe Customer ID</label>
                            <input type="text" id="edit-stripe-customer-id" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Id</label>
                            <input type="text" id="edit-contact-id" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location Name</label>
                            <input type="text" id="edit-location-name" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="edit-paused" class="form-label">Status</label>
                            <select id="edit-paused" class="form-control" required>
                                <option value="0">Active</option>
                                <option value="1">Paused</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveSubaccount()">
                        <span class="loading-text">Save Changes</span>
                        <span class="loading-spinner spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Plans Modal -->
    <div class="modal fade" id="assignPlansModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Plans to Subaccounts</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="assign-plans-form">
                        <div id="plans-loading" class="text-center py-5">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div>Loading plans and subaccounts...</div>
                        </div>
                        <div id="plans-content" class="d-none">
                            <div class="mb-3">
                                <label for="plan-select" class="form-label">Select Plan</label>
                                <select id="plan-select" class="form-control" required>
                                    <option value="">Select a plan</option>
                                </select>
                            </div>
                            {{--
                            <div class="mb-3">
                                <label class="form-label">Search Subaccounts</label>
                                <input type="text" id="subaccounts-plan-search" class="form-control"
                                    placeholder="Search by location ID or email">
                            </div>
                            --}}
                            <div class="mb-3">
                                <label class="form-check-label">
                                    <input type="checkbox" id="select-all-subaccounts" class="form-check-input">
                                    Select All Subaccounts
                                </label>
                            </div>
                            <input type="text" id="subaccounts-plan-search" class="form-control"
                                placeholder="Search by location ID or email">
                            <div id="subaccounts-list" class="border rounded p-3"
                                style="max-height: 400px; overflow-y: auto;">
                                <!-- Subaccounts will be populated here -->
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="savePlans()">
                        <span class="loading-text">Assign Plans</span>
                        <span class="loading-spinner spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: #374151;
            background-color: #f8fafc;
        }

        .table td {
            vertical-align: middle;
        }

        code {
            background-color: #f1f5f9;
            color: #475569;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.875rem;
        }

        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 12px 12px 0 0;
        }

        .btn-close {
            filter: invert(1);
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 20px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 20px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.toggle-slider {
            background-color: #28a745;
        }

        input:checked+.toggle-slider:before {
            transform: translateX(20px);
        }

        #subaccounts-table {
            width: 100% !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            let subaccountsTable = $('#subaccounts-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.subaccounts.data') }}',
                    data: function(d) {
                        d.status = $('#status-filter').val();
                        d.chargeable = $('#chargeable-filter').val();
                    }
                },
                columns: [{
                        data: 'location_id',
                        name: 'location_id'
                    },
                    {
                        data: 'email',
                        name: 'email',
                        render: function(data) {
                            return data || 'N/A';
                        }
                    },
                    {
                        data: 'chargeable',
                        name: 'chargeable',
                        render: function(data, type, row) {
                            return `<label class="toggle-switch"><input type="checkbox" ${data ? 'checked' : ''} onchange="toggleSubaccount(${row.id}, 'chargeable', this.checked)"><span class="toggle-slider"></span></label>`;
                        }
                    },
                    {
                        data: 'allow_uninstall',
                        name: 'allow_uninstall',
                        render: function(data, type, row) {
                            return `<label class="toggle-switch"><input type="checkbox" ${data ? 'checked' : ''} onchange="toggleSubaccount(${row.id}, 'allow_uninstall', this.checked)"><span class="toggle-slider"></span></label>`;
                        }
                    },
                    {
                        data: 'stripe_payment_method_id',
                        name: 'stripe_payment_method_id',
                        render: function(data) {
                            return data || 'N/A';
                        }
                    },
                    {
                        data: 'stripe_customer_id',
                        name: 'stripe_customer_id',
                        render: function(data) {
                            return data || 'N/A';
                        }
                    },
                    {
                        data: 'contact_id',
                        name: 'contact_id'
                    },
                    {
                        data: 'charge_percent',
                        name: 'amount_charge_percent'
                    },
                    {
                        data: 'threshold_amount',
                        name: 'threshold_amount'
                    },
                    {
                        data: 'status',
                        name: 'paused'
                    },

                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                responsive: true,
                pageLength: 20,
                order: [
                    [0, 'asc']
                ],
                dom: 'rt<"d-flex justify-content-between"i p>',
                initComplete: function() {
                    this.api().columns.adjust().draw();
                    $('#loading-subaccounts').addClass('d-none');
                    $('#subaccounts-content').removeClass('d-none');
                    if (this.api().rows().data().length === 0) {
                        $('#empty-subaccounts').removeClass('d-none');
                    } else {
                        $('#empty-subaccounts').addClass('d-none');
                    }
                },
                drawCallback: function() {
                    this.api().columns.adjust();
                }
            });

            // Handle filter form submission
            $('#subaccount-filters').on('submit', function(e) {
                e.preventDefault();
                subaccountsTable.ajax.reload();
            });

            // Handle search input
            $('#subaccounts-search').on('keyup', function() {
                subaccountsTable.search(this.value).draw();
            });
        });

        function toggleSubaccount(id, field, value) {
            $.ajax({
                url: '{{ route('admin.subaccounts.toggle') }}',
                method: 'PUT',
                data: {
                    id: id,
                    field: field,
                    value: value ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Subaccount updated successfully!');
                        $('#subaccounts-table').DataTable().ajax.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'An error occurred';
                    toastr.error(message);
                }
            });
        }

        function editSubaccount(id) {
            $.ajax({
                url: '{{ route('admin.subaccounts.data') }}',
                method: 'GET',
                data: {
                    id: id
                },
                success: function(response) {
                    const subaccount = response.data[0];
                    if (!subaccount) {
                        toastr.error('Subaccount not found');
                        return;
                    }
                    $('#edit-subaccount-id').val(subaccount.id);
                    $('#edit-location-id').val(subaccount.location_id);
                    $('#edit-email').val(subaccount.email || '');
                    $('#edit-chargeable').val(subaccount.chargeable ? '1' : '0');
                    $('#edit-allow-uninstall').val(subaccount.allow_uninstall ? '1' : '0');
                    $('#edit-charge-percent').val(subaccount.amount_charge_percent);
                    $('#edit-threshold-amount').val(subaccount.threshold_amount);
                    $('#edit-currency').val(subaccount.currency || 'USD');
                    $('#edit-stripe-payment-method-id').val(subaccount.stripe_payment_method_id || '');
                    $('#edit-stripe-customer-id').val(subaccount.stripe_customer_id || '');


                    $('#edit-contact-id').val(subaccount.contact_id || '');
                    $('#edit-contact-phone').val(subaccount.contact_phone || '');
                    $('#edit-location-name').val(subaccount.location_name || '');

                    $('#edit-paused').val(subaccount.paused ? '1' : '0');



                    $('#edit-paused').val(subaccount.paused ? '1' : '0');

                    const modal = new bootstrap.Modal(document.getElementById('editSubaccountModal'));
                    modal.show();
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'An error occurred';
                    toastr.error(message);
                }
            });
        }

        function saveSubaccount() {
            const btn = event.target;
            showLoading(btn);

            const data = {
                id: $('#edit-subaccount-id').val(),
                chargeable: $('#edit-chargeable').val() === '1' ? 1 : 0,
                allow_uninstall: $('#edit-allow-uninstall').val() === '1' ? 1 : 0,
                amount_charge_percent: $('#edit-charge-percent').val(),
                threshold_amount: $('#edit-threshold-amount').val(),
                currency: $('#edit-currency').val(),
                stripe_payment_method_id: $('#edit-stripe-payment-method-id').val(),
                stripe_customer_id: $('#edit-stripe-customer-id').val(),

                contact_id: $('#edit-contact-id').val(),
                contact_phone: $('#edit-contact-phone').val(),
                location_name: $('#edit-location-name').val(),


                paused: $('#edit-paused').val() === '1' ? 1 : 0,
            };

            if (!data.amount_charge_percent || !data.threshold_amount || !data.currency) {
                toastr.warning('Please fill in all required fields.');
                hideLoading(btn);
                return;
            }

            $.ajax({
                url: '{{ route('admin.subaccounts.update') }}',
                method: 'PUT',
                data: data,
                success: function(response) {
                    if (response.success) {
                        toastr.success('Subaccount updated successfully!');
                        const modal = bootstrap.Modal.getInstance(document.getElementById(
                            'editSubaccountModal'));
                        modal.hide();
                        $('#subaccounts-table').DataTable().ajax.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'An error occurred';
                    toastr.error(message);
                },
                complete: function() {
                    hideLoading(btn);
                }
            });
        }

        function syncSubaccounts() {
            Swal.fire({
                title: 'Sync Subaccounts',
                text: 'Are you sure you want to sync all subaccounts from CRM? This may take some time.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Sync',
                cancelButtonText: 'Cancel',
            }).then((result) => {
                if (result.isConfirmed) {
                    const btn = $('#sync-subaccounts');
                    showLoading(btn);
                    $.ajax({
                        url: '{{ route('admin.subaccounts.sync') }}',
                        method: 'POST',
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                $('#subaccounts-table').DataTable().ajax.reload();
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            const message = xhr.responseJSON?.message || 'An error occurred';
                            toastr.error(message);
                        },
                        complete: function() {
                            hideLoading(btn);
                        }
                    });
                }
            });
        }

        function openAssignPlansModal() {
            $('#plans-loading').removeClass('d-none');
            $('#plans-content').addClass('d-none');

            const modal = new bootstrap.Modal(document.getElementById('assignPlansModal'));
            modal.show();

            $.ajax({
                url: '{{ route('admin.subaccounts.plan_mappings') }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        populatePlansForm(response.data);
                    } else {
                        toastr.error(response.message);
                        modal.hide();
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'An error occurred';
                    toastr.error(message);
                    modal.hide();
                },
                complete: function() {
                    $('#plans-loading').addClass('d-none');
                    $('#plans-content').removeClass('d-none');
                }
            });
        }

        function populatePlansForm(data) {
            const planSelect = $('#plan-select');
            planSelect.empty().append('<option value="">Select a plan</option>');
            data.plans.forEach(plan => {
                planSelect.append(`<option value="${plan.value}">${plan.display}</option>`);
            });

            const subaccountsList = $('#subaccounts-list');
            subaccountsList.empty();
            data.subaccounts.forEach(chunk => {
                chunk.forEach(subaccount => {
                    subaccountsList.append(`
                        <div class="form-check subaccount-item">
                            <input class="form-check-input subaccount-checkbox" type="checkbox" value="${subaccount.id}" id="subaccount-${subaccount.id}">
                            <label class="form-check-label" for="subaccount-${subaccount.id}">
                                ${subaccount.location_id} (${subaccount.email || 'N/A'})
                            </label>
                        </div>
                    `);
                });
            });

            $('#subaccounts-plan-search').on('keyup', function() {
                const search = $(this).val().toLowerCase();
                $('.subaccount-item').each(function() {
                    const text = $(this).text().toLowerCase();
                    $(this).toggle(text.includes(search));
                });
            });

            $('#select-all-subaccounts').on('change', function() {
                $('.subaccount-checkbox:visible').prop('checked', this.checked);
            });
        }

        function savePlans() {
            const btn = event.target;
            showLoading(btn);

            const plan = $('#plan-select').val();
            const subaccountIds = $('.subaccount-checkbox:checked').map(function() {
                return this.value;
            }).get();

            if (!plan || subaccountIds.length === 0) {
                toastr.warning('Please select a plan and at least one subaccount.');
                hideLoading(btn);
                return;
            }

            $.ajax({
                url: '{{ route('admin.subaccounts.assign_plans') }}',
                method: 'POST',
                data: {
                    plan: plan,
                    subaccount_ids: subaccountIds
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        const modal = bootstrap.Modal.getInstance(document.getElementById('assignPlansModal'));
                        modal.hide();
                        $('#subaccounts-table').DataTable().ajax.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'An error occurred';
                    toastr.error(message);
                },
                complete: function() {
                    hideLoading(btn);
                }
            });
        }

        function refreshSubaccounts() {
            const btn = $('#refresh-subaccounts');
            showLoading(btn);
            $('#loading-subaccounts').removeClass('d-none');
            $('#subaccounts-content').addClass('d-none');

            $('#subaccounts-table').DataTable().ajax.reload(function() {
                hideLoading(btn);
                $('#subaccounts-content').removeClass('d-none');
            });
        }

        function showLoading(btn) {
            $(btn).find('.loading-text').addClass('d-none');
            $(btn).find('.loading-spinner').removeClass('d-none');
            $(btn).prop('disabled', true);
        }

        function hideLoading(btn) {
            $(btn).find('.loading-text').removeClass('d-none');
            $(btn).find('.loading-spinner').addClass('d-none');
            $(btn).prop('disabled', false);
        }
    </script>
@endpush
