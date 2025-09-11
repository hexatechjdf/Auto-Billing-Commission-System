@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3">
        <h2 class="fw-semibold px-3">Client Tether Credentials Setup</h2>
        <div class="col-md-12 mt-3">
            <div class="card shadow-sm">
                <div class="card-header bg-gradient-primary text-white">
                    <h4 class="h4">Authentication Credentials</h4>
                </div>
                <div class="card-body">
                    <form id="credentials-form">
                        @csrf
                        <div class="mb-3">
                            <label for="x_access_token" class="form-label fw-medium">X-Access-Token</label>
                            <input type="text" class="form-control" id="x_access_token" name="x_access_token"
                                value="{{ $credentials->x_access_token ?? '' }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="x_web_token" class="form-label fw-medium">X-Web-Token</label>
                            <input type="text" class="form-control" id="x_web_token" name="x_web_token"
                                value="{{ $credentials->x_web_token ?? '' }}" required>
                        </div>
                        <button type="submit" class="btn btn-primary fw-medium">Save Credentials</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- @include('components.crm-webhook-details') --}}
        @include('components.ct-webhook-details')
    </div>




    @push('script')
        <script>
            $(document).ready(function() {
                $('#credentials-form').submit(function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: '{{ route('location.ct-credentials.save', $locationId) }}',
                        method: 'POST',
                        data: $(this).serialize(),
                        success: function(response) {
                            toastr.success('Credentials saved successfully!');
                        },
                        error: function(xhr) {
                            toastr.error('Error saving credentials: ' + (xhr.responseJSON
                                ?.message || 'Unknown error'));
                        }
                    });
                });
            });
        </script>
        @include('components.copyUrlScript')
    @endpush

    @push('style')
        <style>
            .form-control {
                border-radius: 5px;
                border: 1px solid #ced4da;
                transition: border-color 0.3s ease;
            }

            .form-control:focus {
                border-color: #007bff;
                box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
            }

            .form-label {
                font-weight: 500;
                color: #343a40;
            }

            .btn-primary {
                border-radius: 5px;
                padding: 8px 20px;
                transition: background-color 0.3s ease;
            }
        </style>
    @endpush
@endsection
