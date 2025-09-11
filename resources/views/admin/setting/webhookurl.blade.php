<div class="row mt-2">

    <div class="col-md-12 mt-2">
        <div class="card">
            <div class="card-header">
                <h4 class="h4">App Webhook URL{{ $parent }}</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-danger">
                </div>
                <div class="copy-container">
                    <input type="text" class="form-control code_url" value="{{ route('oauth.webhook.' . $parent) }}"
                        readonly>
                    <div class="row my-2">
                        <div class="col-md-12" style="text-align: left !important">
                            <button type="button" class="btn btn-primary copy_url" data-message="Link Copied"
                                id="kt_account_profile_details_submit">Copy URL</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
