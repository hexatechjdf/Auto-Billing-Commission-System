@extends('autoauth.public')
@section('title', 'Connecting')
@section('js')
    <script>
        var parentWindow = window.parent;
        window.addEventListener("message", (e) => {
            var data = e.data;

            if (data.type == 'location') {
                checkForauth(data);
            }
        });

        $(document).ready(function() {

            Swal.fire({
                title: "Authenticating...",
                timerProgressBar: true,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            let params = new URLSearchParams(location.search);
            let dt = {
                location: params.get('location_id') || "",
                token: params.get('sessionkey') || params.get('sessionKey') || "",
            }

            if ((dt.token ?? "") != "" && (dt.location ?? "") != "") {
                checkForauth(dt);
            }
        });

        function checkForauth(dt) {


            var url = "{{ route('autoauth.checking') }}";
            $.ajax({
                url: url,
                type: 'POST',

                data: {
                    location: dt.location,
                    token: dt.token,
                    _token: "{{ csrf_token() }}"
                },
                success: function(data) {

                    if (data.is_crm == true) {
                        Swal.close();
                        localStorage.setItem('token-id', data.token_id);
                        //toastr.success("Location connected successfully!");
                        location.href = data.route + "?v=" + new Date().getTime();
                    } else {
                        Swal.fire({
                            title: "Unable to auth user"
                        })
                    }

                },
                error: function(data) {

                    Swal.fire({
                        title: "Unable to auth user"
                    })

                },
                complete: function(data) {
                    console.log("completion : " + data);

                }
            });
        }
    </script>
@endsection
