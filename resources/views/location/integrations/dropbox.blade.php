<!-- DocuSign Integration -->
<div class="card mb-4">
    @php
        $parent = 'dropbox';
        $connected = $connections[$parent] ?? null;
        $title = 'Connect';
        $uc_title = ucwords($parent);
        $account_id = $connected['account_id'] ?? '';
        $account_name = $connected['account_name'] ?? '';
    @endphp
    <div class="card-header">
        <h3>{{ $uc_title }} Integration</h3>
    </div>

    <div class="card-body">
        @if ($connected)
            @php
                $title = 'Reconnect/Change Connection';
            @endphp
            <p><strong>Account ID:</strong> {{ $account_id }}</p>
            @if (!empty($account_name))
                <p><strong>Account Name:</strong> {{ $account_name }}</p>
            @endif
        @endif
        <p><a href="{{ route(str_replace('{base}', $parent, $baseConnect)) }}"
                class="btn btn-primary">{{ $title }}</a></p>
        @endif
    </div>
</div>
