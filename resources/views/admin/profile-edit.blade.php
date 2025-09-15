@extends('layouts.app')

@section('content')
    <div class="container-fluied">
        <h3>Update Profile</h3>

        <form id="profileForm">
            @csrf
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" value="{{ $user->name }}" class="form-control">
                <div class="invalid-feedback" id="error-name"></div>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="{{ $user->email }}" class="form-control">
                <div class="invalid-feedback" id="error-email"></div>
            </div>

            <hr>
            <h5>Security</h5>
            <div class="mb-3">
                <label class="form-label">Current Password <span class="text-danger">*</span></label>
                <input type="password" name="current_password" class="form-control">
                <div class="invalid-feedback" id="error-current_password"></div>
            </div>

            <h5>Change Password (optional)</h5>
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-control">
                <div class="invalid-feedback" id="error-password"></div>
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="password_confirmation" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            $('#profileForm').on('submit', function(e) {
                e.preventDefault();

                // clear errors
                $('.form-control').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                $.ajax({
                    url: "{{ route('admin.profile.update') }}",
                    method: "POST",
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Updated!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            $('#profileForm')[0].reset();
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                let input = $('[name="' + key + '"]');
                                input.addClass('is-invalid');
                                $('#error-' + key).text(value[0]);
                            });
                        } else {
                            Swal.fire('Error', 'Something went wrong.', 'error');
                        }
                    }
                });
            });
        });
    </script>
@endpush
