@php

    $base = $base ?? "setting[oauth][{$parent}]";

    $client = ($clientsSetting[$parent] ?? [])[0] ?? [];
    $client_id = $client['client_id'] ?? '';
    $client_secret = $client['client_secret'] ?? '';
@endphp

<form action="{{ $saveRoot }}" class="submitForm" method="POST">
                    @csrf

<div class="card mt-2">
    <div class="card-header">
        @include($integrationBase . 'oauth-title')
    </div>
    <div class="card-body">
        <div class="row">

            @include($integrationBase . 'oauth-redirect')
        </div>

        @if (isset($app_scopes))
            <div class="row">
                <div class="col-md-12">
                    <h6>Scopes - select while creating app</h6>
                    <p class="h6"> {{ $app_scopes }} </p>
                </div>
            </div>
        @endif

        @if (isset($note))
            <div class="row">
                <div class="col-md-12">
                    <h6>* Note - {!!$note!!}</h6>
                </div>
            </div>
        @endif


        <div class="row mt-2">

            @foreach ($fields ?? [] as $field)
                @php
                    $field_id = $field['id'];
                @endphp
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="{{ $parent }}_{{ $field_id }}"
                            class="form-label">{{ $field['title'] }}</label>
                        <input type="text" class="form-control " value="{{ $client[$field_id] ?? '' }}"
                            id="{{ $parent }}_{{ $field_id }}" name="{{ $base }}[{{$field_id}}]"
                            aria-describedby="{{ $field_id }}">
                    </div>
                </div>
            @endforeach



            @include($integrationBase . 'oauth-credentials')


        </div>
        <div class="row">
            <div class="col-md-12 m-2">
                <button id="form_submit" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>

</form>
