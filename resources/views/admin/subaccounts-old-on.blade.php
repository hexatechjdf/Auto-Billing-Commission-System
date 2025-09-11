@extends('layouts.app')

@section('title', 'Subaccounts - Auto-Billing Commission System')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Subaccounts Management</h1>
        <button class="btn btn-primary" onclick="refreshSubaccounts()">
            <i class="fas fa-sync-alt me-2"></i>
            <span class="loading-text">Refresh</span>
            <span class="loading-spinner spinner-border spinner-border-sm" role="status"></span>
        </button>
    </div>

    <!-- Agency Connection Status -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plug me-2"></i>
                        Agency Connection Status
                    </h5>
                </div>
                <div class="card-body">
                    <div id="connection-status" class="d-flex align-items-center">
                        <div class="spinner-border text-primary me-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span>Checking connection status...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Subaccounts Selection -->
    <div class="row">
        <div class="col-12">
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
                            <button class="btn btn-warning">
                                <i class="fas fa-link me-2"></i>
                                Connect Agency
                            </button>
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
                            <div class="col-md-4 d-flex align-items-end">
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
                        <div id="subaccounts-grid" class="row">
                            <!-- Subaccount cards will be populated here -->
                        </div>
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
    </div>
@endsection

@push('scripts')
    <script>
        let subaccountsData = [];
        let currentPrimary = null;

        $(document).ready(function() {
            checkAgencyConnection();
            loadSubaccounts();

            // Initialize Select2
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
        });

        function checkAgencyConnection() {
            // Simulate checking agency connection
            setTimeout(() => {
                const isConnected = true; // This should come from actual API

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
            }, 1000);
        }

        function loadSubaccounts() {
            $.ajax({
                url: '/api/subaccounts',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        subaccountsData = response.data;
                        populateSubaccounts(response.data);
                        loadCurrentPrimary();
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
            // Populate Select2
            const select = $('#subaccount-select');
            select.empty();
            select.append('<option value="">Select a subaccount...</option>');

            subaccounts.forEach(subaccount => {
                select.append(new Option(subaccount.name, subaccount.id, false, false));
            });

            // Populate grid
            const grid = $('#subaccounts-grid');
            grid.empty();

            subaccounts.forEach(subaccount => {
                const card = createSubaccountCard(subaccount);
                grid.append(card);
            });
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
                <div class="fw-bold">${data.name}</div>
                <small class="text-muted">${data.address || 'No address'}</small>
            </div>
            <small class="badge bg-light text-dark ms-2">${data.id}</small>
        </div>
    `);
        }

        function formatSubaccountSelection(subaccount) {
            if (!subaccount.id) return subaccount.text;

            const data = subaccountsData.find(s => s.id === subaccount.id);
            return data ? data.name : subaccount.text;
        }

        function selectSubaccount(subaccountId) {
            $('#subaccount-select').val(subaccountId).trigger('change');

            // Highlight selected card
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
                url: '/api/subaccounts/set-primary',
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
                url: '/api/subaccounts/primary',
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
                    $('#current-primary-name').text(primaryData.name);
                    $('#current-primary').removeClass('d-none');
                }
            }
        }

        function refreshSubaccounts() {
            const btn = event.target.closest('button');
            showLoading(btn);

            $('#loading-subaccounts').removeClass('d-none');
            $('#subaccounts-container').addClass('d-none');

            loadSubaccounts();

            setTimeout(() => {
                hideLoading(btn);
            }, 1000);
        }
    </script>
@endpush

@push('styles')
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
