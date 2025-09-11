<div class="col-md-12">
    <div class="mb-3">
        <label for="{{ $parent }}_client_id" class="form-label">{{ $client_title ?? 'Client ID' }}</label>
        <input type="text" class="form-control " value="{{ $client_id }}" id="{{ $parent }}_client_id"
            name="{{ $base }}[client_id]" aria-describedby="clientID" required>
    </div>
</div>
<div class="col-md-12">
    <label for="{{ $parent }}_secret_id" class="form-label"> {{ $secret_title ?? 'Client Secret' }}</label>
    <input type="text" class="form-control " value="{{ $client_secret }}" id="{{ $parent }}_secret_id"
        name="{{ $base }}[client_secret]" aria-describedby="secretID" required>
</div>
