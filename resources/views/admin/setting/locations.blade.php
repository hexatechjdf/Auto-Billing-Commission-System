@extends('layouts.app')
@push('style')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <style>
        div.dataTables_wrapper div.dataTables_length select {
            width: 60px !important;
        }
    </style>
@endpush
@section('content')
    <div class="container">
        @include('admin.components.banner', ['title' => 'Locations'])
        <div class="row">
            <div class="col-lg-12">
                <div class="card b-radius--10 appanedTable table-responsive p-3">
                    <table id="lookupDatatable" class="display" style="width:100%">
                        <thead>
                            <tr>
                                <th>Location Name</th>
                                <th>Location ID</th>
                                <th>Email</th>
                                <th>Location Id</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="loader-overlay" class="hidden">
        <button class="btn loader_btn waves-effect" type="button" disabled="">
            <span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>
            <span class="ms-25 align-middle">Loading...</span>
        </button>
    </div>
@endsection

@push('script')
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    @include('admin.components.dataTableScript')
    <script>
        var url = '{{ route('admin.locations') }}';
        var setLocationUrl = '{{ route('admin.locations.set') }}';
        let page = 1;
        $(document).ready(function() {
            getLocations();

            function getLocations() {
                $('#loader-overlay').removeClass('hidden');
                $.ajax({
                    type: 'GET',
                    url: url,
                    data: {
                        page: page
                    },
                    success: function(response) {
                        if (!response.status) {
                            toastr.error(response.message)
                        }
                        appendDataToTable(response.detail)
                        if (response.load) {
                            page += 1;
                            getLocations();
                        }

                        $('#loader-overlay').addClass('hidden');
                    },
                    error: function(xhr, status, error) {
                        toastr.error(error);
                    }
                });
            };

        });

        function appendDataToTable(data = null, type = null) {
            if (type == 'single') {
                table.row.add(data).draw(false);
                return true;
            }

            data.forEach(function(row) {
                table.row.add(row).draw(false);
            });
        }

        let row = '';

        function alertMsg(location_id, rowId) {

            swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, set this location!',
                cancelButtonText: 'No, cancel!',
                reverseButtons: true
            }).then(function(result) {
                if (result.value) {
                    let seturl = setLocationUrl;
                    $.ajax({
                        type: 'GET',
                        url: seturl,
                        data: {
                            location: location_id,
                        },
                        success: function(response) {
                            if (!response.status) {
                                toastr.error(response.message)
                                return false;
                            } else {
                                toastr.success(response.message);
                                
                                let text = $('.row-' + rowId).remove();
                                console.log(text);


                            }

                        },
                        error: function(xhr, status, error) {
                            toastr.error(error);
                        }
                    });
                }
            })
        }
    </script>
@endpush