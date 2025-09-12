<?php

// use App\Jobs\ClearWorkflowJob;
// use App\Jobs\ProcessOAuthRefreshToken;
use App\Http\Controllers\WebhookController;
use App\Jobs\ProcessMonthlyCommissionsJob;
use App\Jobs\ProcessPendingOrdersJob;
use App\Jobs\ProcessRefreshToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
 */

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('cron-jobs')->name('cron.')->group(function () {

    Route::get('process_refresh_token', function () {
        dispatch((new ProcessRefreshToken())->onQueue(config('queue.type.refresh')));
        return 'queued';
    })->name('refresh');

    Route::post('/process-orders', function (Request $request) {
        ProcessPendingOrdersJob::dispatch();
        return response()->json(['message' => 'Orders processing job dispatched successfully.']);
    })->name('orders.process');

    Route::post('/process-monthly-cm', function (Request $request) {

        //TODO: uncomment below code
        if (Carbon::now()->day === 1) {

        ProcessMonthlyCommissionsJob::dispatchSync(); // dispatch
        return response()->json(['message' => 'Monthly commissions job dispatched']);
        } else {
            return response()->json(['message' => 'Not the first day of the month, skipping']);
        }
    })->name('orders.process');

    Route::post('/check-pause', function (Request $request) {
        Artisan::call('subaccounts:check-pause');
        return response()->json(['message' => 'subaccounts check-pause processing in background jobs.']);
    })->name('check-pause');

});

Route::get('opt_clear_cache', function () {
    \Artisan::call('optimize:clear');
    if (request()->has('queue')) {
        \Artisan::call('queue:restart');
    }
    return 'queued';
})->name('opt_clear_cache');

Route::post('/webhooks/crm', [WebhookController::class, 'handleGhlWebhook'])->name('webhooks.crm');
Route::post('/webhooks/stripe', [WebhookController::class, 'handleStripeWebhook'])->name('webhooks.stripe');

// API routes (protected by auth and api middleware)
//Route::middleware(['auth:api', 'isAdmin'])->group(function () { //['auth:api', 'isAdmin'];
// Route::middleware('role:1')->group(function () {

// });

// Route::post('/logs/delete-old', function (Illuminate\Http\Request $request) {

//     $token = $request->header('X-API-Token');
//     if ($token !== env('API_TOKEN', 'secret-token')) {
//         return response()->json(['error' => 'Unauthorized'], 401);
//     }

//     $exitCode = Artisan::call('logs:delete-old');

//     return response()->json([
//         'success' => $exitCode === 0,
//         'message' => $exitCode === 0 ? 'Old logs deleted successfully' : 'Failed to delete old logs',
//     ], $exitCode === 0 ? 200 : 500);
// })->name('logs.delete-old');
