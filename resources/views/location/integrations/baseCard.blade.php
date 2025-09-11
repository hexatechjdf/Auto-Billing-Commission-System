<!-- DocuSign Integration -->
<div class="card mb-4">
    @php
        $uc_title = str_replace(' ', '', ucwords($parent));
        $parent = str_replace(' ', '', strtolower($parent));
        $connected = ($connections[$parent] ?? [])[0] ?? null;
        $title = 'Connect';

        $account_id = $connected['account_id'] ?? '';
        $account_name = $connected['account_name'] ?? '';
        $logo = isset($logo) ? $logo : '';

        $isCenter = isset($hide)?'justify-content-center':'';
        $url=route(str_replace('{base}', $parent, $baseConnect));
    @endphp
    <div class="card-header">

        <div class="d-flex align-items-center {{$isCenter}}">
            @if (!empty($logo))
                <img src="{{ $logo }}" alt="{{ $uc_title ?? 'Logo' }}" width="70" height="50" class="mr-2">
            @endif
            @if (!isset($hide))
                <h4 class="pl-2">{{ $uc_title }}</h4>
            @endif
        </div>
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
        <p><a data-href="{{$url}}" data-type="{{$parent}}" data-title="{{$uc_title}} OAuth Connection"
                class="btn btn-primary integration-connect-button">{{ $title }}</a></p>

    </div>
</div>
