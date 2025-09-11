<?php
namespace App\Http\Controllers;

use App\Models\ClientTetherLocationSettings;
use App\Repositories\ClientTetherRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ClientTetherSettingsController extends Controller
{
    protected $clientTetherRepository;

    public function __construct(ClientTetherRepository $clientTetherRepository)
    {
        $this->clientTetherRepository = $clientTetherRepository;
    }

    public function settings()
    {
        $user       = loginUser();
        $locationId = $user->location_id;

        $cacheKey = locationSettingCacheKey($locationId);

        // if (Cache::has($cacheKey)) {
        //     $settings = Cache::get($cacheKey);
        // }

        $settings = getLocationSettings($locationId, true); // with createIfMissing

        return view('location.client_tether_settings', compact('locationId', 'settings'));
    }

    public function saveSettings(Request $request, string $locationId)
    {

        $data = $request->validate([
            'user_map'           => 'nullable|array',
            'user_map.*'         => 'nullable|integer|distinct',
            'default_user_id'    => 'required|integer',
            'default_event_type' => 'required|in:appointment,call,contact_reminder',
            'timezone'           => 'required|string|in:' . implode(',', array_keys(getTimezones())),
        ]);

        $settings = ClientTetherLocationSettings::updateOrCreate(
            ['location_id' => $locationId],
            [
                'user_map'           => array_filter($data['user_map'], fn($value) => ! is_null($value) && $value !== ''),
                'default_user_id'    => $data['default_user_id'],
                'default_event_type' => $data['default_event_type'],
                'timezone'           => $data['timezone'],
            ]
        );

        // Refresh the cache after update

        $cacheKey = locationSettingCacheKey($locationId);
        Cache::put($cacheKey, $settings, now()->addDays(2));

        return response()->json(['message' => 'Settings and mappings saved successfully']);
    }

}
