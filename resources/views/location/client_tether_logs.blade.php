@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3">
        <h2 class="mb-4 text-center fw-bold">Client Tether Integration Logs</h2>
        <div class="p-4 bg-white shadow-sm rounded">
            <h4 class="mb-4 fw-semibold">Log History</h4>
            <div class="mb-4">
                <label for="type_filter" class="form-label fw-medium" data-bs-toggle="tooltip"
                    title="Filter logs by type">Filter by Log Type</label>
                <select class="form-control select2" id="type_filter">
                    <option value="">All Types</option>
                </select>
            </div>
            <table id="logs-table" class="table table-striped w-100">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Message</th>
                        <th>Data</th>
                        <th>Location ID</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    @push('style')
        <style>
            .form-control,
            .select2-container--bootstrap5 .select2-selection--single {
                border: 1px solid #ced4da !important;
                border-radius: 5px;
                box-sizing: border-box;
                transition: border-color 0.3s ease, box-shadow 0.3s ease;
            }

            .form-control:focus,
            .select2-container--bootstrap5 .select2-selection--single:focus {
                border-color: #007bff !important;
                box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
                outline: none;
            }

            .select2-container--bootstrap5 .select2-selection--single {
                height: 38px;
                display: flex;
                align-items: center;
                background-color: #fff;
                transition: background-color 0.3s ease;
            }

            .select2-container--bootstrap5 .select2-selection--single:hover {
                background-color: #f8f9fa;
            }

            .select2-container--bootstrap5 .select2-selection--single .select2-selection__rendered {
                line-height: 38px;
                color: #495057;
                padding-left: 10px;
            }

            .select2-container--bootstrap5 .select2-selection--single .select2-selection__arrow {
                height: 38px;
                right: 10px;
            }

            .select2-container {
                width: 100% !important;
            }

            .form-label {
                font-weight: 500;
                color: #343a40;
            }

            .table th,
            .table td {
                vertical-align: middle;
            }

            .table-striped tbody tr:nth-of-type(odd) {
                background-color: #f9f9f9;
            }

            .dataTables_wrapper .dataTables_paginate .paginate_button {
                border-radius: 5px;
                margin: 2px;
                padding: 5px 10px;
            }

            .dataTables_wrapper .dataTables_paginate .paginate_button.current {
                background: linear-gradient(90deg, #007bff, #0056b3);
                color: #fff !important;
                border: none;
            }

            .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
                background: linear-gradient(90deg, #0056b3, #003087);
                color: #fff !important;
                border: none;
            }
        </style>
    @endpush

    @push('script')
        <script>
            $(document).ready(function() {
                // Initialize tooltips
                $('[data-bs-toggle="tooltip"]').tooltip();

                // Initialize Select2 for type filter
                $('#type_filter').select2({
                    placeholder: "Select a log type",
                    allowClear: true,
                    theme: "bootstrap5",
                    ajax: {
                        url: '{{ route('location.client-tether.log-types', $locationId) }}',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.types.map(type => ({
                                    id: type,
                                    text: type
                                }))
                            };
                        },
                        cache: true
                    }
                });

                // Initialize DataTable
                const table = $('#logs-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('location.client-tether.logs.data', $locationId) }}',
                        data: function(d) {
                            d.type = $('#type_filter').val();
                        }
                    },
                    columns: [{
                            data: 'id',
                            name: 'id'
                        },
                        {
                            data: 'type',
                            name: 'type'
                        },
                        {
                            data: 'message',
                            name: 'message'
                        },
                        {
                            data: 'data',
                            name: 'data'
                        },
                        {
                            data: 'location_id',
                            name: 'location_id'
                        },
                        {
                            data: 'created_at',
                            name: 'created_at'
                        }
                    ],
                    pageLength: 10,
                    responsive: true,
                    order: [
                        [5, 'desc']
                    ], // Order by created_at descending
                    language: {
                        processing: '<div class="loader">Loading logs...</div>'
                    }
                });

                // Reload table on type filter change
                $('#type_filter').on('change', function() {
                    table.draw();
                });
            });
        </script>
    @endpush
@endsection
