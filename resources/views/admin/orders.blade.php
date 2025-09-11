@extends('layouts.app')

@section('title', 'Orders - Auto-Billing Commission System')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Orders</h1>
        <button class="btn btn-outline-primary" onclick="refreshOrders()">
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
            <form id="order-filters" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date Filter</label>
                    <select id="date-filter" class="form-select">
                        <option value="">All Time</option>
                        <option value="this_week">This Week</option>
                        <option value="this_month">This Month</option>
                        <option value="this_year">This Year</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div class="col-md-3 d-none" id="custom-start-date">
                    <label class="form-label">Start Date</label>
                    <input type="date" id="start-date" class="form-control">
                </div>
                <div class="col-md-3 d-none" id="custom-end-date">
                    <label class="form-label">End Date</label>
                    <input type="date" id="end-date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Location ID</label>
                    <input type="text" id="location-filter" class="form-control" placeholder="Enter Location ID">
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

    <!-- Loading State -->
    <div id="loading-orders" class="text-center py-5">
        <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div>Loading orders...</div>
    </div>

    <!-- Orders Content -->
    <div id="orders-content" class="d-none">
        <!-- Empty State -->
        <div id="empty-orders" class="text-center py-5 d-none">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No Orders Found</h5>
            <p class="text-muted">Orders will appear once they are created via webhooks.</p>
        </div>

        <!-- Orders Table -->
        <div class="table-responsive">
            <table id="orders-table" class="table table-hover">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Contact ID</th>
                        <th>Location ID</th>
                        <th>Amount</th>
                        <th>Currency</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Table content will be populated here -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
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
                                <p><strong>Order ID:</strong> <span id="order-id"></span></p>
                                <p><strong>Contact ID:</strong> <span id="order-contact-id"></span></p>
                                <p><strong>Location ID:</strong> <span id="order-location-id"></span></p>
                                <p><strong>Amount:</strong> <span id="order-amount"></span></p>
                                <p><strong>Currency:</strong> <span id="order-currency"></span></p>
                                <p><strong>Status:</strong> <span id="order-status"></span></p>
                                <p><strong>Live Mode:</strong> <span id="order-live-mode"></span></p>
                                <p><strong>Created At:</strong> <span id="order-created-at"></span></p>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Order Items</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Item Name</th>
                                                <th>Quantity</th>
                                                <th>Product ID</th>
                                                <th>Product Name</th>
                                                <th>Price ID</th>
                                                <th>Price Name</th>
                                                <th>Amount</th>
                                                <th>Currency</th>
                                                <th>Type</th>
                                            </tr>
                                        </thead>
                                        <tbody id="order-items-table">
                                            <!-- Items will be populated here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Additional Metadata</h6>
                            </div>
                            <div class="card-body">
                                <pre id="order-metadata" class="bg-light p-3 rounded overflow-auto" style="max-height: 300px;"></pre>
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

        let ordersData = [];
        let dataTable = null;

        $(document).ready(function() {
            loadOrders();

            $('#order-filters').on('submit', function(e) {
                e.preventDefault();
                loadOrders();
            });

            $('#date-filter').on('change', function() {
                const value = $(this).val();
                if (value === 'custom') {
                    $('#custom-start-date').removeClass('d-none');
                    $('#custom-end-date').removeClass('d-none');
                } else {
                    $('#custom-start-date').addClass('d-none');
                    $('#custom-end-date').addClass('d-none');
                }
            });
        });

        function loadOrders() {
            const filter = $('#date-filter').val();
            const startDate = $('#start-date').val();
            const endDate = $('#end-date').val();
            const locationId = $('#location-filter').val();

            $.ajax({
                url: '{{ route('admin.orders.data') }}',
                method: 'GET',
                data: {
                    filter: filter,
                    start_date: startDate,
                    end_date: endDate,
                    location_id: locationId
                },
                success: function(response) {
                    if (response.success) {
                        ordersData = response.data;
                        populateOrdersTable(ordersData);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: handleAjaxError,
                complete: function() {
                    $('#loading-orders').addClass('d-none');
                    $('#orders-content').removeClass('d-none');
                }
            });
        }

        function populateOrdersTable(orders) {
            const tbody = $('#orders-table tbody');
            tbody.empty();

            if (orders.length === 0) {
                $('#empty-orders').removeClass('d-none');
                if (dataTable) {
                    dataTable.destroy();
                    dataTable = null;
                }
                return;
            }

            $('#empty-orders').addClass('d-none');

            orders.forEach(order => {
                const row = `
                    <tr>
                        <td><code>${order.order_id}</code></td>
                        <td>${order.contact_id || 'N/A'}</td>
                        <td>${order.location_id}</td>
                        <td>${order.amount}</td>
                        <td>${order.currency}</td>
                        <td><span class="badge bg-success">${order.status}</span></td>
                        <td>${new Date(order.created_at).toLocaleString()}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewOrderDetails(${order.id})">
                                <i class="fas fa-eye"></i> View Details
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
            dataTable = $('#orders-table').DataTable({
                responsive: true,
                pageLength: 10,
                order: [
                    [6, 'desc']
                ], // Sort by Created At descending by default
                columnDefs: [{
                        orderable: false,
                        targets: [7]
                    } // Disable sorting on Actions
                ]
            });
        }

        function viewOrderDetails(id) {
            $('#order-details-loading').removeClass('d-none');
            $('#order-details-content').addClass('d-none');

            const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
            modal.show();

            $.ajax({
                url: `/admin/orders/details/${id}`,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        const order = response.data;
                        $('#order-id').text(order.order_id);
                        $('#order-contact-id').text(order.contact_id || 'N/A');
                        $('#order-location-id').text(order.location_id);
                        $('#order-amount').text(order.amount);
                        $('#order-currency').text(order.currency);
                        $('#order-status').text(order.status);
                        $('#order-live-mode').text(order.live_mode ? 'Yes' : 'No');
                        $('#order-created-at').text(new Date(order.created_at).toLocaleString());

                        const itemsTbody = $('#order-items-table');
                        itemsTbody.empty();

                        order.items.forEach(item => {
                            const itemRow = `
                                <tr>
                                    <td>${item.item_name}</td>
                                    <td>${item.qty}</td>
                                    <td>${item.product_id}</td>
                                    <td>${item.product_name}</td>
                                    <td>${item.price_id}</td>
                                    <td>${item.price_name}</td>
                                    <td>${item.amount}</td>
                                    <td>${item.currency}</td>
                                    <td>${item.type}</td>
                                </tr>
                            `;
                            itemsTbody.append(itemRow);
                        });

                        $('#order-metadata').text(JSON.stringify(order.metadata, null, 2));

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

        function refreshOrders() {
            const btn = event.target.closest('button');
            showLoading(btn);

            $('#loading-orders').removeClass('d-none');
            $('#orders-content').addClass('d-none');

            loadOrders();

            setTimeout(() => {
                hideLoading(btn);
                $('#orders-content').removeClass('d-none');
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
