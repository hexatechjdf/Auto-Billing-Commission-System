@extends('layouts.app')

@section('title', 'Subaccounts - Auto-Billing Commission System')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Subaccounts</h1>
            <button class="btn btn-outline-primary" onclick="refreshSubaccounts()">
                <i class="fas fa-sync-alt me-2"></i>
                <span class="loading-text">Refresh</span>
                <span class="loading-spinner spinner-border spinner-border-sm" role="status"></span>
            </button>
        </div>

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
            <div class="table-responsive">
                <table id="subaccounts-table" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Location ID</th>
                            <th>Email</th>
                            <th>Chargeable</th>
                            <th>Allow Uninstall</th>
                            <th>Charge %</th>
                            <th>Paused</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Table content will be populated here -->
                    </tbody>
                </table>
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
                                <input type="number" id="edit-charge-percent" class="form-control" step="0.01" min="0" max="100" required>
                                <div class="form-text">Percentage to charge as commission</div>
                            </div>

                            <div class="mb-3">
                                <label for="edit-paused" class="form-label">Paused</label>
                                <select id="edit-paused" class="form-control" required>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveSubaccount()">
                            <span class="loading-text">Save Changes</span>
                            <span class="loading-spinner spinner-border spinner-border-sm" role="status"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

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

@push('scripts')
    <script>
        let subaccountsData = [];

        $(document).ready(function() {
            loadSubaccounts();
        });

        function loadSubaccounts() {
            $.ajax({
                url: '{{ route('admin.subaccounts.data') }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        subaccountsData = response.data;
                        populateSubaccountsTable(subaccountsData);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: handleAjaxError,
                complete: function() {
                    $('#loading-subaccounts').addClass('d-none');
                    $('#subaccounts-content').removeClass('d-none');
                }
            });
        }

        function populateSubaccountsTable(subaccounts) {
            const tbody = $('#subaccounts-table tbody');
            tbody.empty();

            if (subaccounts.length === 0) {
                $('#empty-subaccounts').removeClass('d-none');
                return;
            }

            $('#empty-subaccounts').addClass('d-none');

            subaccounts.forEach(subaccount => {
                const row = `
                    <tr>
                        <td><code>${subaccount.location_id}</code></td>
                        <td>${subaccount.email || 'N/A'}</td>
                        <td><span class="badge bg-${subaccount.chargeable ? 'success' : 'danger'}">${subaccount.chargeable ? 'Yes' : 'No'}</span></td>
                        <td><span class="badge bg-${subaccount.allow_uninstall ? 'success' : 'danger'}">${subaccount.allow_uninstall ? 'Yes' : 'No'}</span></td>
                        <td><span class="badge bg-info">${parseFloat(subaccount.amount_charge_percent).toFixed(2)}%</span></td>
                        <td><span class="badge bg-${subaccount.paused ? 'warning' : 'success'}">${subaccount.paused ? 'Yes' : 'No'}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editSubaccount(${subaccount.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        }

        function editSubaccount(id) {
            const subaccount = subaccountsData.find(s => s.id === id);
            if (!subaccount) return;

            $('#edit-subaccount-id').val(subaccount.id);
            $('#edit-location-id').val(subaccount.location_id);
            $('#edit-email').val(subaccount.email || '');
            $('#edit-chargeable').val(subaccount.chargeable ? '1' : '0');
            $('#edit-allow-uninstall').val(subaccount.allow_uninstall ? '1' : '0');
            $('#edit-charge-percent').val(subaccount.amount_charge_percent);
            $('#edit-paused').val(subaccount.paused ? '1' : '0');

            const modal = new bootstrap.Modal(document.getElementById('editSubaccountModal'));
            modal.show();
        }

        function saveSubaccount() {
            const id = $('#edit-subaccount-id').val();
            const chargeable = $('#edit-chargeable').val() === '1';
            const allowUninstall = $('#edit-allow-uninstall').val() === '1';
            const chargePercent = $('#edit-charge-percent').val();
            const paused = $('#edit-paused').val() === '1';

            if (!chargePercent) {
                toastr.warning('Please fill in all required fields.');
                return;
            }

            const btn = event.target;
            showLoading(btn);

            $.ajax({
                url: '{{ route('admin.subaccounts.update') }}',
                method: 'PUT',
                data: {
                    id: id,
                    chargeable: chargeable,
                    allow_uninstall: allowUninstall,
                    amount_charge_percent: chargePercent,
                    paused: paused
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Subaccount updated successfully!');
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editSubaccountModal'));
                        modal.hide();
                        loadSubaccounts();
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

        function refreshSubaccounts() {
            const btn = event.target.closest('button');
            showLoading(btn);

            $('#loading-subaccounts').removeClass('d-none');
            $('#subaccounts-content').addClass('d-none');

            loadSubaccounts();

            setTimeout(() => {
                hideLoading(btn);
                $('#subaccounts-content').removeClass('d-none');
            }, 1000);
        }

        function handleAjaxError(xhr) {
            const message = xhr.responseJSON?.message || 'An error occurred';
            toastr.error(message);
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
