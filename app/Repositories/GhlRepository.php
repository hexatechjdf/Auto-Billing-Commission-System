<?php
namespace App\Repositories;

use App\Helper\CRM;

class GhlRepository
{
    // public function getGhlAccessToken(string $locationId): ?string
    // {
    //     return CrmToken::where('location_id', $locationId)->where('user_type', 'Location')->first()?->access_token;
    // }

    // public function getUser(string $userId, string $locationId): array
    // {
    //     $response = Http::withToken($this->getGhlAccessToken($locationId))
    //         ->get("https://services.leadconnectorhq.com/users/{$userId}");

    //     return $response->successful() ? $response->json() : [];
    // }

    public function getUserList(string $locationId): array
    {
        return CRM::searchUsers($locationId);
    }

    public function getContactById(string $locationId, string $contactId)
    {
        return CRM::getLocationContact($locationId, $contactId);
    }

    public function findContactByEmailOrPhone(?string $email, ?string $phone, string $locationId)
    {
        $filterConditions = [];

        if ($email && trim($email) != '') {
            $filterConditions[] = ["field" => "email", "operator" => "eq", "value" => $email];
        }

        if ($phone && trim($phone) != '') {
            $filterConditions[] = ["field" => "phone", "operator" => "eq", "value" => [$phone]];
        }

        // if (empty($filterConditions)) {
        //     return null; // No filterable data provided
        // }

        $filters = [
            [
                "group"   => "OR",
                "filters" => $filterConditions,
            ],
        ];

        $contacts = CRM::searchContact($locationId, $filters, null);

        return $contacts[0] ?? null;
    }

    public function createContact(array $payload, string $locationId)
    {
        return CRM::createContact($payload, $locationId);
    }

    public function updateContact(string $contactId, array $payload, string $locationId, $responseFull = false)
    {
        return CRM::updateContact($contactId, $payload, $locationId, null, $responseFull);
    }

    public function createContactNote(string $contactId, string $locationId, array $noteData, $responseFull = false)
    {
        return CRM::createContactNote($contactId, $locationId, null, $noteData, $responseFull);
    }

    public function getContactNotes(string $contactId, string $locationId, $responseFull = false)
    {
        return CRM::getContactNotes($contactId, $locationId, null, $responseFull);
    }

}
