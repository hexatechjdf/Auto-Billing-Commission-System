@extends('layouts.app')
@push('style')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
@endpush
@section('content')
    @php
        $integrationBase = 'admin.setting.component.integration.';
        $saveRoot = route('admin.setting.save');
    @endphp
    <div class="container ">

        <div class="row mt-2">

            @include($integrationBase . 'autologin', ['type' => 'sso'])

            <div class="col-md-12">
                <form action="{{ $saveRoot }}" class="submitForm" method="POST">
                    @csrf
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <div class="card ">
                                <div class="card-header">
                                    <h4 class="h4">CRM OAuth Information</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h6>Redirect URI - add while creating app</h6>
                                            <p class="h6"> {{ route('crm.oauth_callback') }} </p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h6>Scopes - select while creating app</h6>
                                            <p class="h6"> {{ $scopes }} </p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h6>* Note - App distribution Agency and Subaccount both !</h6>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label for="clientID" class="form-label"> Client ID</label>
                                                <input type="text" class="form-control "
                                                    value="{{ $settings['crm_client_id'] ?? '' }}" id="crm_client_id"
                                                    name="setting[crm_client_id]" aria-describedby="clientID" required>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <label for="clientID" class="form-label"> Client secret</label>
                                            <input type="text" class="form-control "
                                                value="{{ $settings['crm_client_secret'] ?? '' }}" id="crm_secret_id"
                                                name="setting[crm_client_secret]" aria-describedby="secretID" required>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12 m-2">
                                            <button id="form_submit" class="btn btn-primary">Save</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="h4">CRM OAuth Connectivity</h4>
                                </div>
                                <div class="card-body">
                                    <div class="ml-2">
                                        <p class="mb-1 text-muted">Connectivity</p>
                                        @if ($company_name && $company_id)
                                            <p>company : <span style="font-weight:bold;">{{ $company_name }}</span></p>
                                            <p>companyId : <span style="font-weight:bold;">{{ $company_id }}</span></p>
                                        @endif

                                        @php($connect = @$company_name ? 'Reconnect' : 'Connect')
                                        <p style="font-weight:bold; font-size:22px"><a class="btn btn-primary"
                                                href="{{ $connecturl }}">{{ $connect }} with
                                                Agency</a></p>
                                    </div>
                                </div>
                                <!--end card-body-->
                            </div>
                        </div>

                    </div>
                </form>
            </div>

            {{--
            <form action="{{ route('admin.update.password') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card mt-4">
                    <div class="card-header">
                        <h4>Update Admin Password</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>

                        <div class="mb-3">
                            <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="new_password_confirmation" required>
                        </div>

                        <button type="submit" class="btn btn-success">Update Password</button>
                    </div>
                </div>
            </form>
            --}}

            @include('components.crm-webhook-details')

        </div>
    </div>
@endsection

@push('script')
    <script>
        $("body").on('click', '.copy_url', function(e) {
            e.preventDefault();
            let msg = $(this).data('message') ?? 'Copied';
            let url = $(this).data('href') ?? "";

            if (url == '') {
                url = $(this).closest('.copy-container').find('.code_url').val();
            }
            try {
                if (url) {
                    navigator.clipboard.writeText(url).then(function() {
                        dispMessage(false, msg);
                    }, function() {
                        dispMessage(true, 'Error while Copy');
                    }).catch(p => {
                        dispMessage(true, 'Request denied');
                    });
                } else {
                    dispMessage(true, "No data found to copy");
                }
            } catch (error) {
                alert('Unable to copy');
            }
        });
    </script>

    <script>
        let formData = null;
        $(document).ready(function() {

            $('body').on('submit', '.submitForm', function(e) {
                e.preventDefault();
                var data = $(this).serialize();
                var url = $(this).attr('action');
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: data,
                    success: function(response) {
                        if (response.view) {
                            $('.appendBody').html(response.view)
                        }
                        try {
                            toastr.success('Saved');
                        } catch (error) {
                            alert('Saved');
                        }
                        $('#sourceModal').modal('hide');
                        console.log('Data saved successfully:', response);
                    },
                    error: function(xhr, status, error) {

                        console.error('Error saving data:', error);
                    }
                });
            });
        });
    </script>
@endpush
