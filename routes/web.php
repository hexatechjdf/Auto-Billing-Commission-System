<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Auth\AutoAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PlanMappingController;
use App\Http\Controllers\SubaccountController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
 */
// Route::any('dashboard', [SettingController::class, 'index'])->name('dashboard');
Route::get('/', function () {
    return redirect('/login');
})->name('root');

Route::get('/logout', function () {
    \Auth::logout();
    return redirect()->route('home');
})->name('logout');

Route::prefix('authorization')->name('crm.')->group(function () {
    Route::get('/crm/oauth/callback', [OAuthController::class, 'crmCallback'])->name('oauth_callback');
});

Route::group(['middleware' => ['auth', 'prevent-back-history']], function () { // middleware ['auth','isAdmin'];

    Route::get('home', [HomeController::class, 'home'])->name('home');

    Route::group(['as' => 'admin.', 'prefix' => 'admin'], function () {

        Route::middleware('role:1')->group(function () {
            // Route::any('dashboard', [SettingController::class, 'index'])->name('setting.index');
            // Route::get('setting', [SettingController::class, 'index'])->name('setting');
            // Route::post('/setting/save', [SettingController::class, 'save'])->name('setting.save');

            // Route::get('/locations', [SettingController::class, 'locations'])->name('locations');
            // Route::get('/locations/set', [SettingController::class, 'locationsSet'])->name('locations.set');

            // Dashboard
            Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

            Route::get('/subaccounts', [SubaccountController::class, 'index'])->name('subaccounts');
            Route::get('/subaccounts/data', [SubaccountController::class, 'subaccountsData'])->name('subaccounts.data');
            Route::get('/subaccounts/{id}/edit', [SubaccountController::class, 'editSubaccount'])->name('subaccounts.edit');
            Route::put('/subaccounts/update', [SubaccountController::class, 'updateSubaccount'])->name('subaccounts.update');
            Route::put('/subaccounts/toggle', [SubaccountController::class, 'toggleSubaccount'])->name('subaccounts.toggle');

            Route::post('/subaccounts/sync', [SubaccountController::class, 'syncSubaccounts'])->name('subaccounts.sync');
            Route::get('/subaccounts/plan-mappings', [SubaccountController::class, 'getPlanMappings'])->name('subaccounts.plan_mappings');
            Route::post('/subaccounts/assign-plans', [SubaccountController::class, 'assignPlans'])->name('subaccounts.assign_plans');

            // Settings
            Route::get('/settings', [SettingController::class, 'index'])->name('settings');
            Route::post('/settings', [SettingController::class, 'save'])->name('settings.save');
            Route::get('/settings/locations', [SettingController::class, 'locations'])->name('setting.locations');
            Route::post('/settings/locations', [SettingController::class, 'locationsSet'])->name('setting.locations.set');

            //Profile
            // Route::put('/settings/user/profile/{id}', [SettingController::class, 'userProfile'])->name('setting.user.profile');
            Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

            // Subaccounts
            Route::get('/settings/subaccounts', [SettingController::class, 'subAccounts'])->name('settings.subaccounts');
            Route::post('/settings/subaccounts/set-primary', [SettingController::class, 'setPrimary'])->name('settings.subaccounts.set-primary');
            Route::get('/settings/subaccounts/primary', [SettingController::class, 'getPrimary'])->name('settings.subaccounts.primary');

            // Plan Mappings
            Route::get('/plan-mappings/index', [PlanMappingController::class, 'index'])->name('plan-mappings.index');
            Route::get('/plan-mappings', [PlanMappingController::class, 'planMappings']);
            Route::post('/plan-mappings/sync', [PlanMappingController::class, 'syncPrices']);
            Route::put('/plan-mappings/{id}', [PlanMappingController::class, 'updateMapping']);
            // Route::get('/plan-mappings/products', [PlanMappingController::class, 'fetchProducts']);

            // Orders
            Route::get('/orders', [OrderController::class, 'index'])->name('orders');
            Route::get('/orders/data', [OrderController::class, 'data'])->name('orders.data');
            Route::get('/orders/details/{id}', [OrderController::class, 'details'])->name('orders.details');

            // // transctions
            // Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions');
            // Route::get('/transactions/data', [TransactionController::class, 'data'])->name('transactions.data');
            // Route::get('/transactions/orders-data', [TransactionController::class, 'ordersData'])->name('transactions.orders_data');
            // // Route::get('/transactions/order-details', [TransactionController::class, 'orderDetails'])->name('transactions.order_details');

        });

        // transctions
        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions');
        Route::get('/transactions/data', [TransactionController::class, 'data'])->name('transactions.data');
        Route::get('/transactions/orders-data', [TransactionController::class, 'ordersData'])->name('transactions.orders_data');
        Route::get('/transactions/order-details', [TransactionController::class, 'orderDetails'])->name('transactions.order_details');

    });

    Route::group(['as' => 'location.', 'prefix' => 'location'], function () {
        Route::middleware('role:2')->group(function () {

            Route::get('/home', function () {
                return redirect()->route('admin.transactions'); // if need then make routes  location.transactions
            })->name('home');

        });
    });
});

Route::prefix('check/auth')->name('autoauth.')->group(function () {
    Route::get('/', [AutoAuthController::class, 'connect'])->name('check');
    Route::get('error', [AutoAuthController::class, 'authError'])->name('error');
    Route::post('checking', [AutoAuthController::class, 'authChecking'])->name('checking');
});

Route::post('/decrypt-sso', function (Request $request) {

    $ghlSsoToken = $request->key;

    if (! $ghlSsoToken) {
        return response()->json(['error' => 'CRM SSO token missing'], 401);
    }

    $validSSO = \App\Helper\CRM::decryptSSO($ghlSsoToken);

    if (! $validSSO) {
        return response()->json(['error' => 'Invalid CRM SSO token'], 401);
    }

    return response()->json(['success' => true, 'validSSO' => $validSSO]);
});

Route::get('/cache/clear', function () {
    \Artisan::call('optimize:clear');
    \Artisan::call('queue:restart');

    return response()->json(['success' => true, 'message' => 'cache cleared']);
});

require __DIR__ . '/auth.php';
