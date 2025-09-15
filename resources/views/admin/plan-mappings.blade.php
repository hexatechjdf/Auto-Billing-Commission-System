@extends('layouts.app')

@section('title', 'Plan Mappings - Auto-Billing Commission System')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Plan Mappings</h1>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="refreshMappings()">
                <i class="fas fa-sync-alt me-2"></i>
                <span class="loading-text">Refresh</span>
                <span class="loading-spinner spinner-border spinner-border-sm" role="status"></span>
            </button>
            <button id="sync-prices-btn" class="btn btn-success" onclick="syncPrices()" disabled>
                <i class="fas fa-cloud-download-alt me-2"></i>
                <span class="loading-text">Sync Prices</span>
                <span class="loading-spinner spinner-border spinner-border-sm" role="status"></span>
            </button>
        </div>
    </div>

    <!-- Primary Subaccount Status -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-building me-2"></i>
                        Primary Subaccount Status
                    </h5>
                </div>
                <div class="card-body">
                    <div id="primary-status" class="d-flex align-items-center">
                        <div class="spinner-border text-primary me-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span>Checking primary subaccount...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Plan Mappings Content -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-layer-group me-2"></i>
                        Product Price Mappings
                    </h5>
                </div>
                <div class="card-body">
                    <!-- No Primary Selected -->
                    <div id="no-primary-selected" class="alert alert-warning d-none" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>No Primary Subaccount Selected!</strong> Please select a primary subaccount first to
                        configure plan mappings.
                        <div class="mt-3">
                            <a href="{{ route('admin.subaccounts') }}" class="btn btn-warning">
                                <i class="fas fa-building me-2"></i>
                                Select Primary Subaccount
                            </a>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div id="loading-mappings" class="text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div>Loading plan mappings...</div>
                    </div>

                    <!-- Mappings Content -->
                    <div id="mappings-content" class="d-none">
                        <!-- Sync Info -->
                        {{-- <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Automatic Sync:</strong> Prices are automatically synced daily and when new prices are
                            created via webhooks.
                            You can also manually sync using the "Sync Prices" button above.
                        </div>
                        --}}
                        <!-- Mappings Table -->
                        <div class="table-responsive">
                            <table id="mappings-table" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        {{-- <th>Interval</th>
                                        <th>Amount</th> --}}
                                        <th>Threshold Amount</th>
                                        <th>Charge %</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Table content will be populated here -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div id="empty-mappings" class="text-center py-5 d-none">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Plan Mappings Found</h5>
                            <p class="text-muted">Click "Sync Prices" to fetch products and prices from your primary
                                subaccount.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Mapping Modal -->
    <div class="modal fade" id="editMappingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Plan Mapping</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="edit-mapping-form">
                        <input type="hidden" id="edit-mapping-id">

                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" id="edit-product-name" class="form-control" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Price Name</label>
                            <input type="text" id="edit-price-name" class="form-control" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Price ID</label>
                            <input type="text" id="edit-price-id" class="form-control" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="edit-threshold-amount" class="form-label">Threshold Amount ($)</label>
                            <input type="number" id="edit-threshold-amount" class="form-control" step="0.01"
                                min="0" required>
                            <div class="form-text">Minimum order amount to trigger commission charge</div>
                        </div>

                        <div class="mb-3">
                            <label for="edit-charge-percent" class="form-label">Charge Percentage (%)</label>
                            <input type="number" id="edit-charge-percent" class="form-control" step="0.01"
                                min="0" max="100" required>
                            <div class="form-text">Percentage to charge as commission</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveMapping()">
                        <span class="loading-text">Save Changes</span>
                        <span class="loading-spinner spinner-border spinner-border-sm" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let currentPrimary = null;
        let mappingsData = [];

        $(document).ready(function() {
            checkPrimarySubaccount();
        });

        function checkPrimarySubaccount() {
            $.ajax({
                url: '{{ route('admin.settings.subaccounts.primary') }}',
                method: 'GET',
                success: function(response) {
                    if (response.success && response.data.location_id) {
                        currentPrimary = response.data.location_id;
                        showPrimaryStatus(true, response.data.location_id);
                        loadPlanMappings();
                    } else {
                        showPrimaryStatus(false);
                    }
                },
                error: function() {
                    showPrimaryStatus(false);
                }
            });
        }

        function showPrimaryStatus(hasPrimary, locationId = null) {
            const statusDiv = $('#primary-status');

            if (hasPrimary) {
                statusDiv.html(`
            <i class="fas fa-check-circle text-success me-2"></i>
            <span class="text-success">Primary Subaccount: <strong>${locationId}</strong></span>
        `);
                $('#sync-prices-btn').prop('disabled', false);
                $('#mappings-content').removeClass('d-none');
            } else {
                statusDiv.html(`
            <i class="fas fa-times-circle text-danger me-2"></i>
            <span class="text-danger">No Primary Subaccount Selected</span>
        `);
                $('#no-primary-selected').removeClass('d-none');
                $('#sync-prices-btn').prop('disabled', true);
            }

            $('#loading-mappings').addClass('d-none');
        }

        function loadPlanMappings() {
            if (!currentPrimary) return;

            $.ajax({
                url: '/admin/plan-mappings',
                method: 'GET',
                data: {
                    location_id: currentPrimary
                },
                success: function(response) {
                    if (response.success) {
                        mappingsData = response.data;
                        populateMappingsTable(response.data);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: handleAjaxError,
                complete: function() {
                    $('#loading-mappings').addClass('d-none');
                }
            });
        }

        function populateMappingsTable(mappings) {
            const tbody = $('#mappings-table tbody');
            tbody.empty();

            if (mappings.length === 0) {
                $('#empty-mappings').removeClass('d-none');
                return;
            }

            $('#empty-mappings').addClass('d-none');

            mappings.forEach(mapping => {
                const row = createMappingRow(mapping);
                tbody.append(row);
            });
        }

        function createMappingRow(mapping) {
            return `
        <tr>
            <td>
                <div class="fw-bold">${mapping.product_name}</div>
                <small class="text-muted">${mapping.product_id}</small>
            </td>
            <td>
                <div class="fw-bold">${mapping.price_name}</div>
                <code>${mapping.price_id}</code>
            </td>
           {{-- <td>
                <span class="badge bg-light text-dark">Monthly</span>
            </td>
            <td>
                <strong>$${parseFloat(mapping.threshold_amount).toFixed(2)}</strong>
            </td>
            --}}
            <td>
                <span class="badge bg-info">$${parseFloat(mapping.threshold_amount).toFixed(2)}</span>
            </td>
            <td>
                <span class="badge bg-success">${parseFloat(mapping.amount_charge_percent).toFixed(2)}%</span>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="editMapping(${mapping.id})">
                    <i class="fas fa-edit"></i>
                </button>
            </td>
        </tr>
    `;
        }

        function syncPrices() {
            if (!currentPrimary) {
                toastr.warning('No primary subaccount selected.');
                return;
            }

            const btn = $('#sync-prices-btn');
            showLoading(btn);

            $.ajax({
                url: '/admin/plan-mappings/sync',
                method: 'POST',
                data: {
                    location_id: currentPrimary
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Prices synced successfully!');
                        loadPlanMappings();
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

        function editMapping(mappingId) {
            const mapping = mappingsData.find(m => m.id === mappingId);
            if (!mapping) return;

            $('#edit-mapping-id').val(mapping.id);
            $('#edit-product-name').val(mapping.product_name);
            $('#edit-price-name').val(mapping.price_name);
            $('#edit-price-id').val(mapping.price_id);
            $('#edit-threshold-amount').val(mapping.threshold_amount);
            $('#edit-charge-percent').val(mapping.amount_charge_percent);

            const modal = new bootstrap.Modal(document.getElementById('editMappingModal'));
            modal.show();
        }

        function saveMapping() {
            const mappingId = $('#edit-mapping-id').val();
            const thresholdAmount = $('#edit-threshold-amount').val();
            const chargePercent = $('#edit-charge-percent').val();

            if (!thresholdAmount || !chargePercent) {
                toastr.warning('Please fill in all required fields.');
                return;
            }

            const btn = event.target;
            showLoading(btn);

            $.ajax({
                url: `/admin/plan-mappings/${mappingId}`,
                method: 'PUT',
                data: {
                    threshold_amount: thresholdAmount,
                    amount_charge_percent: chargePercent
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Plan mapping updated successfully!');
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editMappingModal'));
                        modal.hide();
                        loadPlanMappings();
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

        function refreshMappings() {
            const btn = event.target.closest('button');
            showLoading(btn);

            $('#loading-mappings').removeClass('d-none');
            $('#mappings-content').addClass('d-none');

            checkPrimarySubaccount();

            setTimeout(() => {
                hideLoading(btn);
                $('#mappings-content').removeClass('d-none');
            }, 1000);
        }
    </script>
@endpush

@push('styles')
    <style>
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
    </style>
@endpush
