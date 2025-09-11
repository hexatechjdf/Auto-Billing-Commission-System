@extends('layouts.app')

@section('title', 'Transactions - Auto-Billing Commission System')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Transactions</h1>
        <button class="btn btn-outline-primary" onclick="refreshTransactions()">
            <i class="fas fa-sync-alt me-2"></i>
            <span class="loading-text">Refresh</span>
            <span class="loading-spinner spinner-border spinner-border-sm" role="status"></span>
        </button>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter me-2"></i>
                Filters
            </h5>
        </div>
        <div class="card-body">
            <form id="transaction-filters" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" id="start-date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" id="end-date" class="form-control">
                </div>
                @if ($isAdmin)
                    <div class="col-md-3">
                        <label class="form-label">Location ID</label>
                        <input type="text" id="location-filter" class="form-control" placeholder="Enter Location ID">
                    </div>
                @endif
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loading-transactions" class="text-center py-5">
        <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div>Loading transactions...</div>
    </div>

    <!-- Transactions Content -->
    <div id="transactions-content" class="d-none">
        <!-- Empty State -->
        <div id="empty-transactions" class="text-center py-5 d-none">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No Transactions Found</h5>
            <p class="text-muted">Transactions will appear once they are created.</p>
        </div>

        <!-- Transactions Table -->
        <div class="table-responsive">
            <table id="transactions-table" class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Location ID</th>
                        <th>Sum Commission Amount</th>
                        <th>Currency</th>
                        <th>Status</th>
                        <th>Charged At</th>
                        <th>Reason</th>
                        <th>Invoice ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Table content will be populated here -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- View Orders Modal -->
    <div class="modal fade" id="viewOrdersModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Orders for Transaction <span id="transaction-id"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="orders-loading" class="text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div>Loading orders...</div>
                    </div>
                    <div id="orders-content" class="d-none">
                        <div class="table-responsive">
                            <table id="orders-table" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Contact ID</th>
                                        <th>Amount</th>
                                        <th>Currency</th>
                                        <th>Amount Charge %</th>
                                        <th>Calculated Commission</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Orders will be populated here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details for Order <span id="order-id"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="order-details-loading" class="text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div>Loading order details...</div>
                    </div>
                    <div id="order-details-content" class="d-none">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Order Information</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Order ID:</strong> <span id="detail-order-id"></span></p>
                                <p><strong>Contact ID:</strong> <span id="detail-contact-id"></span></p>
                                <p><strong>Location ID:</strong> <span id="detail-location-id"></span></p>
                                <p><strong>Amount:</strong> <span id="detail-amount"></span></p>
                                <p><strong>Currency:</strong> <span id="detail-currency"></span></p>
                                <p><strong>Amount Charge %:</strong> <span id="detail-amount-charge-percent"></span></p>
                                <p><strong>Calculated Commission:</strong> <span id="detail-calculated-commission"></span>
                                </p>
                                <p><strong>Transaction ID:</strong> <span id="detail-transaction-id"></span></p>
                                <p><strong>Status:</strong> <span id="detail-status"></span></p>
                                <p><strong>Created At:</strong> <span id="detail-created-at"></span></p>
                                <p><strong>Updated At:</strong> <span id="detail-updated-at"></span></p>
                                <a id="view-in-crm" class="btn btn-primary" target="_blank">View in CRM</a>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Metadata</h6>
                            </div>
                            <div class="card-body">
                                <pre id="detail-metadata" class="bg-light p-3 rounded overflow-auto" style="max-height: 400px;"></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        pre {
            white-space: pre-wrap;
            word-break: break-word;
        }

        .modal-dialog.modal-xl {
            max-width: 90%;
        }

        .table-hover tbody tr:hover {
            background-color: #f8fafc;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        let transactionsData = [];
        let dataTable = null;

        $(document).ready(function() {
            loadTransactions();

            $('#transaction-filters').on('submit', function(e) {
                e.preventDefault();
                loadTransactions();
            });
        });

        function loadTransactions() {
            const startDate = $('#start-date').val();
            const endDate = $('#end-date').val();
            const locationId = $('#location-filter').val();

            $.ajax({
                url: '{{ route('admin.transactions.data') }}',
                method: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    location_id: locationId
                },
                success: function(response) {

                    // Handle DataTables response structure
                    if (response && response.data) {
                        transactionsData = response.data;
                        populateTransactionsTable(transactionsData);
                    } else {
                        console.error('Invalid response format:', response);
                        toastr.error(response.message || 'Invalid data received from server');
                    }
                },
                error: handleAjaxError,
                complete: function() {
                    $('#loading-transactions').addClass('d-none');
                    $('#transactions-content').removeClass('d-none');
                }
            });
        }

        function populateTransactionsTable(transactions) {
            const tbody = $('#transactions-table tbody');
            tbody.empty();

            if (transactions.length === 0) {
                $('#empty-transactions').removeClass('d-none');
                if (dataTable) {
                    dataTable.destroy();
                    dataTable = null;
                }
                return;
            }

            $('#empty-transactions').addClass('d-none');

            transactions.forEach(transaction => {
                const row = `
                    <tr>
                        <td>${transaction.id}</td>
                        <td>${transaction.location_id}</td>
                        <td>${number_format(transaction.sum_commission_amount, 2)}</td>
                        <td>${transaction.currency}</td>
                        <td>${getStatusBadge(transaction.status)}</td>
                        <td>${transaction.charged_at ? new Date(transaction.charged_at).toLocaleString() : 'N/A'}</td>
                        <td>${transaction.reason || 'N/A'}</td>
                        <td>${transaction.invoice_id || 'N/A'}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary view-orders" onclick="viewOrders()" data-id="${transaction.id}">
                                <i class="fas fa-eye"></i> View Orders
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });

            // Initialize or reinitialize DataTables
            if (dataTable) {
                dataTable.destroy();
            }
            dataTable = $('#transactions-table').DataTable({
                responsive: true,
                pageLength: 10,
                order: [
                    [0, 'desc']
                ], // Sort by ID descending by default
                columnDefs: [{
                        orderable: false,
                        targets: [8]
                    } // Disable sorting on Actions
                ]
            });
        }

        function getStatusBadge(status) {
            const statuses = ['Pending', 'Paid', 'Failed'];
            const colors = ['warning', 'success', 'danger'];
            return `<span class="badge bg-${colors[status]}">${statuses[status]}</span>`;
        }

        function viewOrders(transactionId) {
            $('#orders-loading').removeClass('d-none');
            $('#orders-content').addClass('d-none');

            const modal = new bootstrap.Modal(document.getElementById('viewOrdersModal'));
            modal.show();

            $('#transaction-id').text(transactionId);

            $.ajax({
                url: '{{ route('admin.transactions.orders_data') }}',
                method: 'GET',
                data: {
                    transaction_id: transactionId
                },
                success: function(response) {
                    if (response && response.data) {
                        populateOrdersTable(response.data);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: handleAjaxError,
                complete: function() {
                    $('#orders-loading').addClass('d-none');
                    $('#orders-content').removeClass('d-none');
                }
            });
        }

        function populateOrdersTable(orders) {
            const tbody = $('#orders-table tbody');
            tbody.empty();

            orders.forEach(order => {
                const row = `
                    <tr>
                        <td>${order.order_id}</td>
                        <td>${order.contact_id || 'N/A'}</td>
                        <td>${number_format(order.amount, 2)}</td>
                        <td>${order.currency}</td>
                        <td>${order.amount_charge_percent}%</td>
                        <td>${number_format(order.calculated_commission_amount, 2)}</td>
                        <td>${order.status}</td>
                        <td>${new Date(order.created_at).toLocaleString()}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-info view-order-details" data-id="${order.id}">
                                <i class="fas fa-eye"></i> Details
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });

            $('#orders-table').DataTable({
                responsive: true,
                pageLength: 10,
                order: [
                    [0, 'asc']
                ], // Sort by Order ID
                columnDefs: [{
                        orderable: false,
                        targets: [8]
                    } // Disable sorting on Actions
                ]
            });
        }

        function viewOrderDetails(orderId) {
            $('#order-details-loading').removeClass('d-none');
            $('#order-details-content').addClass('d-none');

            const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
            modal.show();

            $.ajax({
                url: '{{ route('admin.transactions.order_details') }}',
                method: 'GET',
                data: {
                    order_id: orderId
                },
                success: function(response) {
                    if (response.success) {
                        const order = response.data;
                        $('#detail-order-id').text(order.order_id);
                        $('#detail-contact-id').text(order.contact_id || 'N/A');
                        $('#detail-location-id').text(order.location_id);
                        $('#detail-amount').text(number_format(order.amount, 2));
                        $('#detail-currency').text(order.currency);
                        $('#detail-amount-charge-percent').text(order.amount_charge_percent + '%');
                        $('#detail-calculated-commission').text(number_format(order
                            .calculated_commission_amount, 2));
                        $('#detail-transaction-id').text(order.transaction_id || 'N/A');
                        $('#detail-status').text(order.status);
                        $('#detail-created-at').text(new Date(order.created_at).toLocaleString());
                        $('#detail-updated-at').text(new Date(order.updated_at).toLocaleString());

                        const metadata = JSON.stringify(order.metadata, null, 2);
                        $('#detail-metadata').text(metadata);

                        const crmUrl =
                            `https://app.gohighlevel.com/location/${order.location_id}/orders/${order.order_id}`;
                        $('#view-in-crm').attr('href', crmUrl);

                        $('#order-details-loading').addClass('d-none');
                        $('#order-details-content').removeClass('d-none');
                    } else {
                        toastr.error(response.message);
                        modal.hide();
                    }
                },
                error: handleAjaxError
            });
        }

        function refreshTransactions() {
            const btn = event.target.closest('button');
            showLoading(btn);

            $('#loading-transactions').removeClass('d-none');
            $('#transactions-content').addClass('d-none');

            loadTransactions();

            setTimeout(() => {
                hideLoading(btn);
                $('#transactions-content').removeClass('d-none');
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

        function number_format(number, decimals = 2) {
            return parseFloat(number).toFixed(decimals);
        }
    </script>
@endpush
