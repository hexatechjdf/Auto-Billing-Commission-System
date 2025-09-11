<div class="col-md-12">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">CRM Custom Menu Link for Auto Login</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-danger">
                <ul>
                    <li>Add below url to custom menu link enabled only for locations as an iframe.</li>
                </ul>
            </div>
            <div class="copy-container">
                <input type="text" class="form-control code_url" value="{{ getAuthUrl($type ?? '') }}" readonly>
                <div class="row my-2">
                    <div class="col-md-12" style="text-align: left !important">
                        <button type="button" class="btn btn-primary script_code copy_url" data-message="Link Copied"
                            id="kt_account_profile_details_submit">Copy URL</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
