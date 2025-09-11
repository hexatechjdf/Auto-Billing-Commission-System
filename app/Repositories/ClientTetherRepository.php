<?php
namespace App\Repositories;

use App\Models\ClientTetherCredentials;
use App\Models\ClientTetherLocationSettings;
use Illuminate\Support\Facades\Http;

class ClientTetherRepository
{
    protected $baseUrl = 'https://api.clienttether.com/v2/api/';

    public function getAuthHeaders(string $locationId): array
    {
        $credentials = ClientTetherCredentials::where('location_id', $locationId)->first();

        return [
            'X-Access-Token' => $credentials->x_access_token ?? "",
            'X-Web-Key'      => $credentials->x_web_token ?? "",
        ];
    }

    protected function makeApiRequest(string $method, string $endpoint, string $locationId, array $data = []): array
    {
        //dd($data);
        $response = Http::withHeaders($this->getAuthHeaders($locationId))
            ->{$method}("{$this->baseUrl}{$endpoint}", $data);

        if (! $response->successful()) {
            \Log::info("Client Tether API request failed: {$endpoint}", [
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);
            return [];
        }

        return $response->json();
    }

    public function getUserList(string $locationId): array
    {
        return $this->makeApiRequest('get', 'read_user_list', $locationId);
    }

    public function findClientByEmail(string $email, string $locationId): ?array
    {
        $response = $this->makeApiRequest('get', 'read_client_exist', $locationId, ['email' => $email]);
        return $response ?: null;
    }

    public function createClient(array $data, string $locationId): array
    {
        return $this->makeApiRequest('post', 'create_client', $locationId, $data);
    }

    public function createAppointment(array $data, string $locationId): array
    {
        return $this->makeApiRequest('post', 'create_client_event', $locationId, $data);
    }

    public function updateAppointment(string $eventId, array $data, string $locationId): array
    {
        return $this->makeApiRequest('post', 'update_client_event', $locationId, array_merge(
            ['event_id' => $eventId],
            $data
        ));
    }

    public function deleteAppointment(string $eventId, string $locationId): array
    {
        return $this->makeApiRequest('DELETE', 'delete_client_event?event_id=' . $eventId, $locationId);
    }

    // public function readAllEvent(string $locationId): ?array
    // {
    //     $from_date = date('Y-m-d H:i:s', strtotime('2025-07-25 10:30:00'));
    //     $response = $this->makeApiRequest('get', 'read_all_events', $locationId, ['from_date' => $from_date, 'end_date' => $from_date, 'event_type' => 'appt']);
    //     dd($response);
    //     return $response ?: null;
    // }

    public function readClientById(string $locationId, int $clientId): ?array
    {
        $from_date = date('Y-m-d H:i:s', strtotime('2025-07-25 10:30:00'));
        $response  = $this->makeApiRequest('get', 'read_client_by_id/' . $clientId, $locationId);
        dd($response);
        return $response ?: null;
    }

    public function getMappedUserId(string $ghlUserId, string $locationId): ?string
    {
        $settings = ClientTetherLocationSettings::where('location_id', $locationId)->first();
        if ($settings && isset($settings->user_map[$ghlUserId])) {
            return $settings->user_map[$ghlUserId];
        }

        return null;
    }

    public function getDefaultUserId(string $locationId): ?string
    {
        $settings = ClientTetherLocationSettings::where('location_id', $locationId)->first();
        return $settings?->default_user_id;
    }

    public function getMappedOrDefaultUserId(string $ghlUserId, string $locationId)
    {
        $settings = ClientTetherLocationSettings::where('location_id', $locationId)->first();

        if ($ghlUserId && $settings && isset($settings->user_map[$ghlUserId])) {
            $userId = $settings->user_map[$ghlUserId];
        } else {
            $userId = $settings?->default_user_id;
        }

        return $userId;
    }
}
