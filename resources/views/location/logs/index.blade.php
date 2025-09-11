@extends('layouts.app')
@section('content')
    <div class="container-fluid px-3 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Logs</h2>
            <div>
                <label for="typeFilter" class="me-2">Filter by Type:</label>
                <select id="typeFilter" class="form-select w-auto d-inline-block">
                    <option value="">All</option>
                    <option value="1">CRM</option>
                    <option value="2">ClientTether</option>
                    <option value="3">Warning</option>
                </select>
            </div>
        </div>
        <table id="logsTable" class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
            </thead>
        </table>
    </div>

    <!-- Bootstrap Modal -->
    <div class="modal fade" id="logDetailModal" tabindex="-1" aria-labelledby="logDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logDetailModalLabel">Log Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table">
                        <tr>
                            <th>ID</th>
                            <td id="modal-log-id"></td>
                        </tr>
                        <tr>
                            <th>Type</th>
                            <td id="modal-type"></td>
                        </tr>
                        <tr>
                            <th>Location ID</th>
                            <td id="modal-location-id"></td>
                        </tr>
                        <tr>
                            <th>Message</th>
                            <td id="modal-message"></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td id="modal-status"></td>
                        </tr>
                        <tr>
                            <th>Created At</th>
                            <td id="modal-created-at"></td>
                        </tr>
                        <tr>
                            <th>Payload</th>
                            <td>
                                <pre id="modal-payload"></pre>
                            </td>
                        </tr>
                        <tr>
                            <th>Response</th>
                            <td>
                                <pre id="modal-response"></pre>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-top-right',
                timeOut: 5000,
            };

            const table = $('#logsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('location.logs.data') }}',
                    data: function(d) {
                        d.type = $('#typeFilter').val();
                    }
                },
                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'type_name'
                    },
                    {
                        data: 'message'
                    },
                    {
                        data: 'status'
                    },
                    {
                        data: 'created_at'
                    },
                    {
                        data: 'action'
                    },
                ],
            });

            $('#typeFilter').on('change', function() {
                table.ajax.reload();
            });


            $('#logsTable').on('click', '.retry-btn', function() {
                const logId = $(this).data('log-id');
                const logType = $(this).data('type'); // Get type from data attribute
                const payload = []; //$(this).data('payload');



                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Do you want to retry this operation?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, retry it!'
                }).then((result) => {
                    if (result.value) {

                        // Show loading SweetAlert
                        Swal.fire({
                            title: 'Processing... <i class="fas fa-spinner fa-spin me-2"></i>',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            allowEnterKey: false,
                            showConfirmButton: false, // Hides the "OK" button
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajax({
                            url: logType == 1 ? '{{ route('crm.webhook.appointment') }}/' +
                                logId : 'er',


                            method: 'POST',
                            data: {
                                logId: logId,
                                ...payload
                            },
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {


                                // Wait 2 seconds before closing and reloading
                                setTimeout(() => {
                                    Swal.close();
                                    toastr.success(response.message ||
                                        'Retry dispatched and will be processed in the backgrond'
                                    );
                                    table.ajax.reload();
                                }, 2000);
                            },
                            error: function(xhr) {
                                Swal.close(); // Close loading modal
                                toastr.error('Retry failed: ' + (xhr.responseJSON
                                    ?.error || 'Unknown error'));
                            }
                        });
                    }
                });
            });


            $('#logsTable').on('click', '.detail-btn', function() {
                const logId = $(this).data('log-id');

                $.ajax({
                    url: '{{ route('location.logs.data') }}/' + logId,
                    method: 'GET',
                    success: function(log) {
                        $('#modal-log-id').text(log.id);
                        // $('#modal-type').text(log.type == 1 ? 'CRM' : 'ClientTether');
                        $('#modal-type').text(log.type == 1 ? 'CRM' : (log.type == 2 ?
                            'ClientTether' : 'Warning'));
                        $('#modal-location-id').text(log.location_id);
                        $('#modal-message').text(log.message);
                        // $('#modal-status').html(log.status == 1
                        //     ? '<span class="badge bg-success">Success</span>'
                        //     : '<span class="badge bg-danger">Failed</span>');

                        const statusLabels = {
                            0: {
                                class: 'bg-secondary text-white',
                                text: 'Queued'
                            },
                            1: {
                                class: 'bg-warning text-dark',
                                text: 'Processing'
                            },
                            2: {
                                class: 'bg-success',
                                text: 'Succeeded'
                            },
                            3: {
                                class: 'bg-danger',
                                text: 'Failed'
                            }
                        };

                        const status = statusLabels[log.status] || {
                            class: 'bg-secondary',
                            text: 'Unknown'
                        };

                        $('#modal-status').html(
                            `<span class="badge ${status.class}">${status.text}</span>`);
                        $('#modal-created-at').text(log.created_at);
                        $('#modal-payload').text(JSON.stringify(log.payload, null, 2));
                        $('#modal-response').text(JSON.stringify(log.response, null,
                            2)); // Display response
                        $('#logDetailModal').modal('show');
                    },
                    error: function(xhr) {
                        toastr.error('Failed to load log details: ' + (xhr.responseJSON
                            ?.error || 'Unknown error'));
                    }
                });
            });
        });
    </script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection
