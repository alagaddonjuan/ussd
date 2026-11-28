<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Client\ApiKeyController;
use App\Http\Controllers\Client\BillingController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\ServiceController;
use App\Http\Controllers\Client\SettingsController;
use App\Http\Controllers\Client\TeamController;
use App\Http\Controllers\Client\UssdMenuController;
use App\Http\Controllers\UssdCallbackController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public USSD & Payment Gateway Callbacks (CSRF-exempt in VerifyCsrfToken)
|--------------------------------------------------------------------------
*/
Route::post('/ussd', [UssdCallbackController::class, 'handle'])->name('ussd.callback');
Route::post('/ussd/events', [UssdCallbackController::class, 'events'])->name('ussd.events');
Route::post('/ussd_callback', [UssdCallbackController::class, 'handle'])->name('ussd.callback.legacy');
Route::post('/billing/webhook', [WebhookController::class, 'paystack'])->name('paystack.webhook');

/*
|--------------------------------------------------------------------------
| Root & Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('landing');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');

    Route::get('/auth/2fa', [AuthController::class, 'showTwoFactor'])->name('2fa.challenge');
    Route::post('/auth/2fa', [AuthController::class, 'verifyTwoFactor'])->name('2fa.verify');

    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');

    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated Client Portal
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active.client'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/live', [DashboardController::class, 'live'])->name('dashboard.live');

    // Messaging & Airtime
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::post('/services/sms', [ServiceController::class, 'sendSms'])->name('services.sms.send');
    Route::post('/services/airtime', [ServiceController::class, 'sendAirtime'])->name('services.airtime.send');
    Route::get('/export/sms', [ServiceController::class, 'exportSms'])->name('export.sms');
    Route::get('/export/airtime', [ServiceController::class, 'exportAirtime'])->name('export.airtime');
    Route::get('/export/ussd', [ServiceController::class, 'exportUssd'])->name('export.ussd');

    // USSD Menus & Tree Builder
    Route::get('/ussd-menus', [UssdMenuController::class, 'index'])->name('ussd.menus.index');
    Route::get('/ussd-menus/logs/live', [UssdMenuController::class, 'liveLogs'])->name('ussd.logs.live');
    Route::post('/ussd-menus', [UssdMenuController::class, 'store'])->name('ussd.menus.store');
    Route::get('/ussd-menus/{menu}', [UssdMenuController::class, 'show'])->name('ussd.menus.show');
    Route::post('/ussd-menus/{menu}/activate', [UssdMenuController::class, 'setActive'])->name('ussd.menus.activate');
    Route::delete('/ussd-menus/{menu}', [UssdMenuController::class, 'archive'])->name('ussd.menus.archive');
    Route::post('/ussd-menus/{menu}/items', [UssdMenuController::class, 'storeItem'])->name('ussd.items.store');
    Route::post('/ussd-menus/{menu}/import-text', [UssdMenuController::class, 'importText'])->name('ussd.menus.import-text');
    Route::post('/ussd-menus/{menu}/versions', [UssdMenuController::class, 'storeSnapshot'])->name('ussd.menus.snapshot');
    Route::post('/ussd-menus/{menu}/versions/{version}/restore', [UssdMenuController::class, 'restoreVersion'])->name('ussd.menus.restore');
    Route::post('/ussd-menus/{menu}/test-api', [UssdMenuController::class, 'testApiNode'])->name('ussd.menus.test-api');
    Route::put('/ussd-items/{item}', [UssdMenuController::class, 'updateItem'])->name('ussd.items.update');
    Route::delete('/ussd-items/{item}', [UssdMenuController::class, 'destroyItem'])->name('ussd.items.destroy');
    Route::post('/ussd-menus/{menu}/simulate', [UssdMenuController::class, 'simulate'])->name('ussd.menus.simulate');

    // Billing & Wallet
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/transactions/{transaction}/receipt', [BillingController::class, 'receipt'])->name('billing.receipt');
    Route::post('/billing/paystack', [BillingController::class, 'initializePaystack'])->name('billing.paystack');
    Route::get('/billing/verify', [BillingController::class, 'verifyPaystack'])->name('billing.verify');

    // API Keys
    Route::get('/api-keys', [ApiKeyController::class, 'index'])->name('keys.index');
    Route::post('/api-keys', [ApiKeyController::class, 'store'])->name('keys.store');
    Route::put('/api-keys/{apiKey}', [ApiKeyController::class, 'update'])->name('keys.update');
    Route::delete('/api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->name('keys.destroy');

    // Team Management
    Route::get('/team', [TeamController::class, 'index'])->name('team.index');
    Route::post('/team/invite', [TeamController::class, 'invite'])->name('team.invite');
    Route::delete('/team/{member}', [TeamController::class, 'remove'])->name('team.remove');

    // Account Settings & 2FA
    Route::get('/settings/profile', [SettingsController::class, 'profile'])->name('settings.profile');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::post('/settings/profile/kyc', [SettingsController::class, 'uploadKyc'])->name('settings.kyc.upload');
    Route::get('/settings/security', [SettingsController::class, 'security'])->name('settings.security');
    Route::post('/settings/security/2fa/generate', [SettingsController::class, 'generateTwoFactor'])->name('settings.2fa.generate');
    Route::post('/settings/security/2fa/enable', [SettingsController::class, 'enableTwoFactor'])->name('settings.2fa.enable');
    Route::post('/settings/security/2fa/disable', [SettingsController::class, 'disableTwoFactor'])->name('settings.2fa.disable');

    /*
    |--------------------------------------------------------------------------
    | Admin Portal
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->as('admin.')->middleware('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/clients', [AdminController::class, 'clients'])->name('clients');
        Route::put('/clients/{client}', [AdminController::class, 'updateClient'])->name('clients.update');
        Route::delete('/clients/{client}', [AdminController::class, 'destroyClient'])->name('clients.destroy');
        Route::post('/clients/bulk-delete', [AdminController::class, 'bulkDestroyClients'])->name('clients.bulk-delete');
        Route::get('/clients/{client}/kyc', [AdminController::class, 'downloadKyc'])->name('clients.kyc.download');
        Route::post('/clients/{client}/perfex-invoice', [AdminController::class, 'pushPerfexInvoice'])->name('clients.perfex-invoice');
        Route::get('/perfex/invoices', [AdminController::class, 'perfexInvoices'])->name('perfex.invoices');
        Route::post('/clients/{client}/transaction', [AdminController::class, 'manualTransaction'])->name('clients.transaction');
        Route::post('/clients/bill-maintenance', [AdminController::class, 'chargeMaintenance'])->name('clients.bill-maintenance');

        Route::get('/pricing-tiers', [AdminController::class, 'pricingTiers'])->name('pricing-tiers');
        Route::post('/pricing-tiers', [AdminController::class, 'storeTier'])->name('pricing-tiers.store');
        Route::put('/pricing-tiers/{tier}', [AdminController::class, 'updateTierPrices'])->name('pricing-tiers.update');
        Route::delete('/pricing-tiers/{tier}', [AdminController::class, 'destroyTier'])->name('pricing-tiers.destroy');
        Route::post('/telco-rates', [AdminController::class, 'storeTelcoRate'])->name('telco-rates.store');

        Route::get('/logs', [AdminController::class, 'logs'])->name('logs');
        Route::get('/logs/live', [AdminController::class, 'liveLogs'])->name('logs.live');
        Route::get('/logs/at-balance', [AdminController::class, 'atBalance'])->name('logs.at-balance');
        Route::post('/logs/reconcile', [AdminController::class, 'reconcileAtCsv'])->name('logs.reconcile');
        Route::post('/logs/{ussdLog}/reconcile', [AdminController::class, 'reconcileSingleUssdLog'])->name('logs.reconcile-single');
        Route::post('/webhooks/{webhookLog}/replay', [AdminController::class, 'replayWebhook'])->name('webhooks.replay');
        Route::get('/reports/{type}', [AdminController::class, 'exportCsv'])->name('reports.export');

        Route::get('/ussd-menus', [AdminController::class, 'ussdMenus'])->name('ussd-menus');

        Route::get('/announcements', [AdminController::class, 'announcements'])->name('announcements');
        Route::post('/announcements', [AdminController::class, 'sendAnnouncement'])->name('announcements.send');
    });
});
