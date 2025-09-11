<!-- DocuSign Integration -->
<div class="card mb-4">
    <div class="card-header">
        <h3>DocuSign Integration</h3>
    </div>
    <div class="card-body">
        @if (session('docusign_account_name'))
            <p><strong>Account Name:</strong> {{ session('docusign_account_name') }}</p>
        @else
            <p><a href="{{ route(str_replace('{base}','docusign',$baseConnect)) }}" class="btn btn-primary">Connect to DocuSign</a></p>
        @endif
    </div>
</div>
