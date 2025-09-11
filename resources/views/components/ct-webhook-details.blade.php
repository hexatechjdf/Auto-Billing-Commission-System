<div class="col-md-12 mt-3">
    <div class="card shadow-sm">
        <div class="card-header bg-gradient-primary text-white">
            <h4 class="h4">Client Tether Webhook Url</h4>
        </div>
        <div class="card-body">
            <div class="alert">

                <div class="alert alert-warning">
                    <p>Use this url in Client Tether to send Webhooks from Client Tether</p>
                </div>

            </div>
            <div class="copy-container">
                <input type="text" class="form-control code_url"
                    value="{{ route('ct.webhook.contact', ['locationId' => $locationId]) }}" readonly>
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
