@extends('layouts.app')

@section('title', 'Transactions - Auto-Billing Commission System')

@section('content')

    <!-- Filters -->

    {{-- <div class="card mb-4">
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
        --}}

    {{-- <div class="card shadow-sm mb-4 border-0">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 d-flex align-items-center">
                    <i class="fas fa-filter me-2 text-primary"></i>
                    Filters
                </h5>
                <button id="toggle-filters" class="btn btn-sm btn-light border">
                    <i class="fas fa-chevron-up me-1"></i>
                    Collapse
                </button>
            </div>

            <div class="card-body" id="filters-container">
                <form id="transaction-filters" class="row g-3">
                    <!-- Date Range -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Date Range</label>
                        <div class="input-group">
                            <input type="date" id="start-date" class="form-control" placeholder="Start Date">
                            <span class="input-group-text">to</span>
                            <input type="date" id="end-date" class="form-control" placeholder="End Date">
                        </div>
                    </div>

                    <!-- Location ID -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Location</label>
                        <select id="location-filter" class="form-select">
                            <option value="">All Locations</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->location_id }}">
                                    {{ $location->email }} ({{ $location->location_id }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select id="status-filter" class="form-select">
                            <option value="">All</option>
                            <option value="0">Pending</option>
                            <option value="1">Paid</option>
                            <option value="2">Failed</option>
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="col-md-2 d-flex align-items-end justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary w-50">
                            <i class="fas fa-search me-1"></i> Apply
                        </button>
                        <button type="button" id="reset-filters" class="btn btn-outline-secondary w-50">
                            <i class="fas fa-undo me-1"></i> Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>
        --}}

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h1 class="h3 mb-0 text-gray-800">Transactions</h1>


        <!-- Filters Dropdown -->
        <div class="dropdown">

            <button class="btn btn-outline-primary dropdown-toggle" type="button" id="filtersDropdown"
                data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                <i class="fas fa-filter me-1"></i> Filters
            </button>


            <button class="btn btn-outline-primary" onclick="refreshTransactions()">
                <i class="fas fa-sync-alt me-2"></i>
                <span class="loading-text">Refresh</span>
                <span class="loading-spinner spinner-border spinner-border-sm" role="status"></span>
            </button>

            <div class="dropdown-menu dropdown-menu-end shadow p-3" aria-labelledby="filtersDropdown"
                style="min-width: 350px;">
                <form id="transaction-filters" class="row g-3">
                    <!-- Date Range -->
                    <div class="col-12">
                        <label class="form-label fw-semibold">Date Range</label>
                        <div class="input-group">
                            <input type="date" id="start-date" class="form-control" placeholder="Start Date">
                            <span class="input-group-text">to</span>
                            <input type="date" id="end-date" class="form-control" placeholder="End Date">
                        </div>
                    </div>

                    <!-- Location -->
                    @if ($isAdmin)
                        <div class="col-12">
                            <label class="form-label fw-semibold">Location</label>
                            <select id="location-filter" class="form-select w-100">
                                <option value="">All Locations</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location->location_id }}">
                                        {{ $location->email }} ({{ $location->location_id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <!-- Status -->
                    <div class="col-12">
                        <label class="form-label fw-semibold">Status</label>
                        <select id="status-filter" class="form-select">
                            <option value="">All</option>
                            <option value="0">Pending</option>
                            <option value="1">Paid</option>
                            <option value="2">Failed</option>
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="col-12 d-flex justify-content-between mt-2">
                        <button type="button" id="reset-filters" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-undo me-1"></i> Reset
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search me-1"></i> Apply
                        </button>
                    </div>
                </form>
            </div>
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


        <!-- Transactions Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    {{-- <i class="fas fa-table me-2"></i> --}}
                    <i class="fas fa-list me-2"></i>
                    Transactions List
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="transactions-table" class="table table-hover w-100">
                        <thead>
                            <tr>

                                <th>Location ID</th>
                                <th>Sum Commission Amount</th>
                                {{-- <th>Currency</th> --}}
                                <th>Status</th>
                                <th>Charged At</th>
                                <th>Reason</th>
                                <th>Payment Intent</th>
                                <th>Invoice ID</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Empty State -->
                            <div id="empty-transactions" class="text-center py-5 d-none">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Transactions Found</h5>
                                <p class="text-muted">Transactions will appear once they are created.</p>
                            </div>

                            <!-- Table content will be populated by DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
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
                            <table id="orders-table" class="table table-hover w-100">
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
                                    <!-- Orders will be populated by DataTables -->
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
                                <p><strong>Amount Charge Percentage:</strong> <span
                                        id="detail-amount-charge-percent"></span></p>
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
            max-width: 70%;
        }

        .table-hover tbody tr:hover {
            background-color: #f8fafc;
        }

        #transactions-table {
            width: 100% !important;
        }

        #orders-table {
            width: 100% !important;
        }

        #viewOrdersModal {
            z-index: 11060;
        }

        #orderDetailsModal {
            z-index: 11070;
        }


        /* Ensure Select2 takes full width inside dropdown */
        .select2-container {
            width: 100% !important;
        }

        /* Prevent dropdown from closing when interacting with Select2 */
        .dropdown-menu.show {
            overflow: visible !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- Flatpickr CSS & JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>


    <script>
        $(document).ready(function() {

            // Initialize Flatpickr for GHL-like date range picker
            flatpickr("#date-range", {
                mode: "range",
                dateFormat: "Y-m-d",
                allowInput: true,
            });

            // Initialize Select2 for Locations
            $('#location-filter').select2({
                dropdownParent: $('#transaction-filters'), // ensures dropdown stays inside
                width: '100%',
                placeholder: "Select Location",
                allowClear: true
            });

            // Prevent dropdown from closing when interacting with Select2 or Flatpickr
            $(document).on('click', '.select2-container, .flatpickr-calendar', function(e) {
                e.stopPropagation();
            });



            // Initialize DataTable
            let transactionsTable = $('#transactions-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.transactions.data') }}',
                    data: function(d) {
                        // Extract start and end dates from flatpickr
                        const dateRange = $('#date-range').val();
                        const [startDate, endDate] = dateRange ? dateRange.split(' to ') : [null, null];

                        d.start_date = startDate;
                        d.end_date = endDate;
                        d.location_id = $('#location-filter').val();
                        d.status = $('#status-filter').val();
                    }
                },
                columns: [{
                        data: 'location_id',
                        name: 'location_id'
                    },
                    {
                        data: 'sum_commission_amount_formatted',
                        name: 'sum_commission_amount'
                    },
                    /*{
                        data: 'currency',
                        name: 'currency'
                    },*/
                    {
                        data: 'status_text',
                        name: 'status',
                        orderable: false
                    },
                    {
                        data: 'charged_at',
                        name: 'charged_at',
                        render: function(data) {
                            return data ? new Date(data).toLocaleString() : 'N/A';
                        }
                    },
                    {
                        data: 'reason',
                        name: 'reason',
                        render: function(data) {
                            return data || 'N/A';
                        }
                    },
                    {
                        data: 'pm_intent',
                        name: 'pm_intent',
                        render: function(data) {
                            return data || 'N/A';
                        }
                    },
                    {
                        data: 'invoice_id',
                        name: 'invoice_id',
                        render: function(data) {
                            return data || 'N/A';
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                responsive: true,
                pageLength: 10,
                order: [
                    [0, 'desc']
                ],
                initComplete: function() {
                    this.api().columns.adjust().draw();
                    $('#loading-transactions').addClass('d-none');
                    $('#transactions-content').removeClass('d-none');
                    if (this.api().rows().data().length === 0) {
                        $('#empty-transactions').removeClass('d-none');
                    } else {
                        $('#empty-transactions').addClass('d-none');
                    }
                },
                drawCallback: function() {
                    this.api().columns.adjust();
                }
            });

            // Handle filter form submission
            $('#transaction-filters').on('submit', function(e) {
                e.preventDefault();
                transactionsTable.ajax.reload();

                // Manually close the dropdown
                const dropdown = bootstrap.Dropdown.getInstance($('#filtersDropdown')[0]);
                dropdown.hide();
            });


            // Handle View Orders button click
            $('#transactions-table').on('click', '.view-orders', function() {
                let transactionId = $(this).data('id');
                viewOrders(transactionId);
            });

            // Handle View Order Details button click in orders table
            $('#viewOrdersModal').on('click', '.view-order-details', function() {
                //let orderId = $(this).data('id');
                let orderRecord = $(this).data('order-record');
                viewOrderDetails(orderRecord);
            });

            // Refresh button
            $('#refresh-transactions').on('click', function() {
                const btn = $(this);
                showLoading(btn);
                transactionsTable.ajax.reload(function() {
                    hideLoading(btn);
                });
            });
        });

        // View Orders function
        function viewOrders(transactionId) {
            $('#orders-loading').removeClass('d-none');
            $('#orders-content').addClass('d-none');

            const modal = new bootstrap.Modal(document.getElementById('viewOrdersModal'));
            modal.show();

            $('#transaction-id').text(transactionId);

            // Destroy previous DataTable if exists
            if ($.fn.DataTable.isDataTable('#orders-table')) {
                $('#orders-table').DataTable().destroy();
            }

            // Initialize Orders DataTable
            let ordersTable = $('#orders-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.transactions.orders_data') }}',
                    data: {
                        transaction_id: transactionId
                    }
                },
                columns: [{
                        data: 'order_id',
                        name: 'order_id'
                    },
                    {
                        data: 'contact_id',
                        name: 'contact_id',
                        render: function(data) {
                            return data || 'N/A';
                        }
                    },
                    {
                        data: 'amount_formatted',
                        name: 'amount'
                    },
                    {
                        data: 'currency',
                        name: 'currency'
                    },
                    {
                        data: 'amount_charge_percent',
                        name: 'amount_charge_percent'
                    },
                    {
                        data: 'calculated_commission_amount_formatted',
                        name: 'calculated_commission_amount'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        render: function(data) {
                            return new Date(data).toLocaleString();
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                responsive: true,
                pageLength: 10,
                order: [
                    [0, 'asc']
                ],
                initComplete: function() {
                    this.api().columns.adjust().draw();
                    $('#orders-loading').addClass('d-none');
                    $('#orders-content').removeClass('d-none');
                },
                drawCallback: function() {
                    this.api().columns.adjust();
                }
            });
        }

        // View Order Details function
        function viewOrderDetails(orderRecord) {
            $('#order-details-loading').removeClass('d-none');
            $('#order-details-content').addClass('d-none');

            const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
            modal.show();


            const order = orderRecord;
            $('#order-id').text(order.order_id);
            $('#detail-order-id').text(order.order_id);
            $('#detail-contact-id').text(order.contact_id || 'N/A');
            $('#detail-location-id').text(order.location_id);
            $('#detail-amount').text(number_format(order.amount, 2));
            $('#detail-currency').text(order.currency);
            $('#detail-amount-charge-percent').text(order.amount_charge_percent + '%');
            $('#detail-calculated-commission').text(number_format(order.calculated_commission_amount,
                2));
            $('#detail-transaction-id').text(order.transaction_id || 'N/A');
            $('#detail-status').text(order.status);
            $('#detail-created-at').text(new Date(order.created_at).toLocaleString());
            $('#detail-updated-at').text(new Date(order.updated_at).toLocaleString());

            // Format payload for better readability
            //const metadata = order.payload || {};
            //$('#detail-metadata').text(JSON.stringify(metadata, null, 4));


            let metadata = {};
            try {
                metadata = typeof order.payload === 'string' ?
                    JSON.parse(order.payload) :
                    order.payload;
            } catch (e) {
                console.error('Invalid JSON in payload:', order.payload);
                metadata = {};
            }

            $('#detail-metadata').text(JSON.stringify(metadata, null, 4));

            const crmUrl =
                `https://app.gohighlevel.com/location/${order.location_id}/orders/${order.order_id}`;
            $('#view-in-crm').attr('href', crmUrl);

            $('#order-details-loading').addClass('d-none');
            $('#order-details-content').removeClass('d-none');

            /*
            $.ajax({
                url: '{{ route('admin.transactions.order_details') }}',
                method: 'GET',
                data: {
                    order_id: orderId
                },
                success: function(response) {
                    const order = response.data;
                    $('#order-id').text(order.order_id);
                    $('#detail-order-id').text(order.order_id);
                    $('#detail-contact-id').text(order.contact_id || 'N/A');
                    $('#detail-location-id').text(order.location_id);
                    $('#detail-amount').text(number_format(order.amount, 2));
                    $('#detail-currency').text(order.currency);
                    $('#detail-amount-charge-percent').text(order.amount_charge_percent + '%');
                    $('#detail-calculated-commission').text(number_format(order.calculated_commission_amount,
                        2));
                    $('#detail-transaction-id').text(order.transaction_id || 'N/A');
                    $('#detail-status').text(order.status);
                    $('#detail-created-at').text(new Date(order.created_at).toLocaleString());
                    $('#detail-updated-at').text(new Date(order.updated_at).toLocaleString());

                    // Format payload for better readability
                    //const metadata = order.payload || {};
                    //$('#detail-metadata').text(JSON.stringify(metadata, null, 4));


                    let metadata = {};
                    try {
                        metadata = typeof order.payload === 'string' ?
                            JSON.parse(order.payload) :
                            order.payload;
                    } catch (e) {
                        console.error('Invalid JSON in payload:', order.payload);
                        metadata = {};
                    }

                    $('#detail-metadata').text(JSON.stringify(metadata, null, 4));

                    const crmUrl =
                        `https://app.gohighlevel.com/location/${order.location_id}/orders/${order.order_id}`;
                    $('#view-in-crm').attr('href', crmUrl);

                    $('#order-details-loading').addClass('d-none');
                    $('#order-details-content').removeClass('d-none');
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'An error occurred';
                    toastr.error(message);
                    modal.hide();
                }
            });
            */
        }

        function refreshTransactions() {
            const btn = $('#refresh-transactions');
            showLoading(btn);
            $('#loading-transactions').removeClass('d-none');
            $('#transactions-content').addClass('d-none');

            $('#transactions-table').DataTable().ajax.reload(function() {
                hideLoading(btn);
                $('#loading-transactions').addClass('d-none');
                $('#transactions-content').removeClass('d-none');
            });
        }

        function showLoading(btn) {
            btn.find('.loading-text').addClass('d-none');
            btn.find('.loading-spinner').removeClass('d-none');
            btn.prop('disabled', true);
        }

        function hideLoading(btn) {
            btn.find('.loading-text').removeClass('d-none');
            btn.find('.loading-spinner').addClass('d-none');
            btn.prop('disabled', false);
        }

        function number_format(number, decimals = 2) {
            return parseFloat(number).toFixed(decimals);
        }


        $(document).ready(function() {
            // Collapse / Expand Filters
            $('#toggle-filters').on('click', function() {
                const $container = $('#filters-container');
                const $icon = $(this).find('i');
                $container.slideToggle(200);

                // Toggle chevron direction
                if ($icon.hasClass('fa-chevron-up')) {
                    $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                    $(this).text(' Expand').prepend($icon);
                } else {
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                    $(this).text(' Collapse').prepend($icon);
                }
            });

            // Handle Apply Filters
            /* $('#transaction-filters').on('submit', function(e) {
                 e.preventDefault();

                 // Collect filter values
                 const filters = {
                     start_date: $('#start-date').val(),
                     end_date: $('#end-date').val(),
                     location_id: $('#location-filter').val(),
                     status: $('#status-filter').val(),
                 };

                 // Refresh DataTable or trigger AJAX call
                 $('#transactions-table').DataTable().ajax.reload();
             });
             */

            // Reset Filters
            $('#reset-filters').on('click', function() {
                $('#transaction-filters')[0].reset();
                $('#location-filter').val('').trigger('change');
                $('#date-range').val('');
                $('#transactions-table').DataTable().ajax.reload();
            });
        });
    </script>
@endpush
