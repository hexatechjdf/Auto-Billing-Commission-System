@extends('layouts.app')

@section('content')
    <div class="px-5">
        <h3>Integrations</h3>

        @php
            $baseConnect = 'oauth.connect.{base}';
        @endphp

        <div class="row mt-2">
            <div class="col-md-6">
                @include('location.integrations.baseCard', [
                    'hide1' => true,
                    'parent' => 'DocuSign',
                    'logo1' =>
                        'https://docucdn-a.akamaihd.net/olive/images/2.72.0/global-assets/ds-logo-default.svg',
                ])
            </div>

            <div class="col-md-6">
                @include('location.integrations.baseCard', [
                    'parent' => 'DropBox',
                    'logo1' =>
                        'https://cfl.dropboxstatic.com/static/images/logo_catalog/blue_dropbox_glyph_m1-vflZvZxbS.png',
                ])
            </div>
        </div>

    </div>
@endsection
@push('script')
    <script>
        let currentWindow = null;
        function initIntegrations() {

            window.addEventListener('message',({data})=>{
                if(data?.action=='connected'){
                    currentWindow.close();
                    location.reload();
                }
            });
            $('body').on('click','.integration-connect-button',function(e){
                let href = $(this).attr('data-href');
                let type = $(this).attr('data-type');
                let title = $(this).attr('data-title');
                currentWindow = PopFullWindow(href,title);
            });
        }
    </script>
@endpush
@push('ready_script')
    initIntegrations();
@endpush
