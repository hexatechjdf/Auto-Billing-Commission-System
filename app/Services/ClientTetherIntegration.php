<?php
namespace App\Services;

use App\Jobs\SyncGhlToClientTetherAppointment;
use App\Jobs\UpdateClientTetherAppointment;
use App\Models\Log as LogModel;
use App\Repositories\ClientTetherRepository;
use App\Repositories\GhlRepository;

class ClientTetherIntegration
{
    protected $clientTetherRepository;
    protected $ghlRepository;

    public function __construct(
        ClientTetherRepository $clientTetherRepository,
        GhlRepository $ghlRepository
    ) {
        $this->clientTetherRepository = $clientTetherRepository;
        $this->ghlRepository          = $ghlRepository;
    }

    public function handleGhlAppointmentCreate($webhookType, object $payload, LogModel $log)
    {
        SyncGhlToClientTetherAppointment::dispatch($payload, $log, $this->clientTetherRepository, $this->ghlRepository);

        // $job = new SyncGhlToClientTetherAppointment($payload, $log, $this->clientTetherRepository, $this->ghlRepository);
        // $job->handle();

        return successJsonResponse();
    }

    public function handleGhlAppointmentUpdate($webhookType, object $payload, LogModel $log, $isDeleted = false)
    {

        UpdateClientTetherAppointment::dispatch($payload, $log, $this->clientTetherRepository, $isDeleted);

        // $job = new UpdateClientTetherAppointment($payload, $log, $this->clientTetherRepository, $isDeleted);
        // $job->handle();

        return successJsonResponse();
    }

    public function prepareContactPayload(array $data, $type = 'create'): array
    {
        $firstName = $data['firstName'] ?? '';
        $lastName  = $data['lastName'] ?? null;
        $email     = $data['email'] ?? null;
        $phone     = $data['phoneCell'] ?? null;

        if ($firstName) {
            $payload['firstName'] = $firstName;
        }

        if ($lastName) {
            $payload['lastName'] = $lastName;
        }

        if ($email) {
            $payload['email'] = $email;
        }

        if ($phone) {
            $payload['phone'] = $phone;
        }

        // Address fields
        foreach ([
            'address' => 'address1',
            'city'    => 'city',
            'state'   => 'state',
            'country' => 'country',
            'zip'     => 'postalCode',

        ] as $inputKey => $outputKey) {
            if (! empty($data[$inputKey])) {
                $payload[$outputKey] = $data[$inputKey];
            }
        }

        // Tags
        // if (! empty($data['opportunity_name'])) {
        //     $payload['tags'] = [$data['opportunity_name']];
        // }

        // Custom Fields
        // $customFieldsMap = [
        //     'whiteboard' => 'whiteboard_notes', // TODO: remove html from this notes
        //     'deal_size'  => 'deal_size',
        //     'days_count' => 'days_count',
        // ];

        // $customFields = [];

        // foreach ($customFieldsMap as $key => $customKey) {
        //     if (! empty($data[$key])) {
        //         $customFields[] = [
        //             'key'         => $customKey, // If your API requires `id`, replace this
        //             'field_value' => $data[$key],
        //         ];
        //     }
        // }

        // if ($customFields) {
        //     $payload['customFields'] = $customFields;
        // }

        // if ($type == 'create') {
        //     $payload['source'] = 'client tether webhook';
        // }

        return $payload;
    }

    public function prepareClientPayload(object $ghlContact): array
    {
        $customFields = collect($ghlContact->customFields ?? []);

        $payload = [
            'firstName'             => $ghlContact->firstName ?? null,
            'lastName'              => $ghlContact->lastName ?? null,
            'email'                 => $ghlContact->email ?? null,
            'phone'                 => $ghlContact->phone ?? optional($ghlContact->additionalPhones)[0] ?? null,

            'address'               => $ghlContact->address1 ?? null,
            'city'                  => $ghlContact->city ?? null,
            'state'                 => $ghlContact->state ?? null,
            'zip'                   => $ghlContact->postalCode ?? null,

            // 'deal_size'             => $this->getCustomFieldValue($customFields, 'UQcQdt5uRSX3JqmykJS7'),
            // 'referralurl'           => $this->getCustomFieldValue($customFields, 'BlXHF220E4tiysNxBuAH'),
            // 'project_type'          => $this->getCustomFieldValue($customFields, 'mcIpc1jMqR96qSR8pHRX'),

            // 'smsok'                 => '1', // 1 if okay to text, 0 if not.  defaults to 1 if not provided
            // 'howheard'              => 'Online',
            // 'brief_desc'            => 'Plumbing',
            // 'last_touch'            => 'Call_Center',
            // 'time_zone'             => config('clienttether.default_time_zone', 'America/New_York'),

            // 'lead_source_id'        => config('clienttether.lead_source_id'),
            // 'action_plan_id'        => config('clienttether.action_plan_id'),
            // 'external_id'           => $ghlContact->id ?? null,

            // tag => '',
            'whiteboard'            => '<p>Created via CRM webhook</p>',

            'new_lead_notification' => '2', // This determines if the Account Owner will receive notice that they have a “New Lead”.  The standard notices will be sent: Email, Text and Software Notification. 0= No Notifications (default) 1= Software Notification 2= All Three Notifications: Email, Text and Software
        ];

        // Filter out any keys with null or empty string values
        return array_filter($payload, function ($value) {
            return ! is_null($value) && $value !== '';
        });
    }

    protected function getCustomFieldValue($customFields, string $fieldId): ?string
    {
        return optional($customFields->firstWhere('id', $fieldId))->value;
    }

}
