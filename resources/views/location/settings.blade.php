@extends('layouts.app')

@push('style')
<style>
    .nav-item .nav-link{
        color: rgba(0, 0, 0, .55);
    }
</style>
@endpush

@section('content')
    <div class="container-fluid px-3">
        <h2 class="mb-3">Location Settings for ServiceTitan</h2>
        {{-- <p>Location ID: {{ $locationId ?? 'Not Set' }}</p> --}}

        <form method="POST" action="{{ route('location.settings') }}" id="locationSettingsForm">
            @csrf
            <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="business-units-tab" data-bs-toggle="tab" href="#business-units" role="tab" aria-controls="business-units" aria-selected="true">Business Units</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="job-types-tab" data-bs-toggle="tab" href="#job-types" role="tab" aria-controls="job-types" aria-selected="false">Job Types</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="campaigns-tab" data-bs-toggle="tab" href="#campaigns" role="tab" aria-controls="campaigns" aria-selected="false">Campaigns</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="priority-tab" data-bs-toggle="tab" href="#priority" role="tab" aria-controls="priority" aria-selected="false">Priority</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="technicians-tab" data-bs-toggle="tab" href="#technicians" role="tab" aria-controls="technicians" aria-selected="false">Technicians</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="ghl-calendars-tab" data-bs-toggle="tab" href="#ghl-calendars" role="tab" aria-controls="ghl-calendars" aria-selected="false">CRM Calendars</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="timezone-tab" data-bs-toggle="tab" href="#timezone" role="tab" aria-controls="timezone" aria-selected="false">Timezone</a>
                </li>
            </ul>




            <div class="tab-content mt-3" id="settingsTabContent">
                <div class="tab-pane fade show active" id="business-units" role="tabpanel" aria-labelledby="business-units-tab">
                    <x-datatable id="business-units-table" route="{{ route('location.st-business-units') }}" />
                </div>
                <div class="tab-pane fade" id="job-types" role="tabpanel" aria-labelledby="job-types-tab">
                    <x-datatable id="job-types-table" route="{{ route('location.st-job-types') }}" />
                    @error('job_type_id')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="tab-pane fade" id="campaigns" role="tabpanel" aria-labelledby="campaigns-tab">
                    <x-datatable id="campaigns-table" route="{{ route('location.st-campaigns') }}" />
                </div>
                <div class="tab-pane fade" id="priority" role="tabpanel" aria-labelledby="priority-tab">
                    <div class="form-group">
                        <label>Priority</label><br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="priority" value="0" {{ $settings?->priority == 'Normal' ? 'checked' : '' }}>
                            <label class="form-check-label">Normal</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="priority" value="1" {{ $settings?->priority == 'High' ? 'checked' : '' }}>
                            <label class="form-check-label">High</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="priority" value="2" {{ $settings?->priority == 'Low' ? 'checked' : '' }}>
                            <label class="form-check-label">Low</label>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="technicians" role="tabpanel" aria-labelledby="technicians-tab">
                    <x-datatable id="technicians-table" route="{{ route('location.st-technicians') }}" />
                </div>
                <div class="tab-pane fade" id="ghl-calendars" role="tabpanel" aria-labelledby="ghl-calendars-tab">
                    <x-datatable id="ghl-calendars-table" route="{{ route('location.ghl-calendars') }}" />
                    @error('ghl_calendar_id')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="tab-pane fade" id="timezone" role="tabpanel" aria-labelledby="timezone-tab">
                    <div class="form-group">
                        <label for="timezone">Default Timezone</label>
                        <select name="timezone" id="timezone" class="form-control">
                            <option value="">Select a Timezone</option>
                            @foreach (timezone_identifiers_list() as $timezone)
                                <option value="{{ $timezone }}" {{ $settings?->timezone === $timezone ? 'selected' : '' }}>
                                    {{ $timezone }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Save Settings</button>
        </form>
    </div>
@endsection

@push('script')
<script>
    $(document).on('click', '.copy-btn', function() {
        const id = $(this).data('id');
        navigator.clipboard.writeText(id).then(() => {
            toastr.success('ID copied to clipboard: ' + id);
        });
    });

    $(document).ready(function() {
        // handleAjaxForm('settingsForm', function(formData, $form) {
        //     // Append radio button selections
        //     const settings = {
        //         business_unit_id: $('input[name="business_unit_id"]:checked').val() || '',
        //         job_type_id: $('input[name="job_type_id"]:checked').val() || '',
        //         campaign_id: $('input[name="campaign_id"]:checked').val() || '',
        //         priority: $('input[name="priority"]:checked').val() || '',
        //         technician_id: $('input[name="technician_id"]:checked').val() || '',
        //         ghl_calendar_id: $('input[name="ghl_calendar_id"]:checked').val() || '',
        //          timezone: $('#timezone').val() || ''
        //     };

        //     $.each(settings, function(key, value) {
        //         if (value) {
        //             formData.set(key, value);
        //         }
        //     });
        // });
        handleAjaxForm('locationSettingsForm');
    });
</script>
@endpush
