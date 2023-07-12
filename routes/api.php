<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
 */

/** User/Auth routes */
Route::post('auth', [\App\Http\Controllers\AuthController::class, 'login']);
Route::post('login', [\App\Http\Controllers\AuthController::class, 'login']);
Route::get('logout', [\App\Http\Controllers\AuthController::class, 'logout']);
Route::post('forgot-password', [\App\Http\Controllers\AuthController::class, 'forgotPassword']);
Route::post('reset-password', [\App\Http\Controllers\AuthController::class, 'reset']);
Route::get('check-token/{token}', [\App\Http\Controllers\AuthController::class, 'checkToken']);
Route::post('register', [\App\Http\Controllers\AuthController::class, 'register']);
Route::post('validate-email', [App\Http\Controllers\User\UserController::class, 'validateEmail']);

/** Program setup routes */
Route::get('public-pages', [App\Http\Controllers\PageController::class, 'publicPages']);
Route::get('setting', [App\Http\Controllers\ConfigController::class, 'list']);
Route::get('initialise', [App\Http\Controllers\InitialiseController::class, 'load']);

/** File route */
Route::post('create-file', [\App\Http\Controllers\FileController::class, 'store']);

/** Program creation routes */
Route::get('createSite', [App\Http\Controllers\CreateSiteController::class, 'createSite']);

/** Additional function routes - zipping images (Amotto) and initial set up for PDF creation (TODO) */
Route::post('function/zipImages', [App\Http\Controllers\ZipImagesController::class, 'zipImages']);
Route::get('function/zipImages', [App\Http\Controllers\ZipImagesController::class, 'zipImages']);
Route::get('pdf', [PdfController::class, 'index']);

/** Calendar feed route */
Route::get('/get-feed', App\Http\Controllers\FeedController::class);

/** Public bookings */
Route::get('bookings', [App\Http\Controllers\BookingController::class, 'bookings']);
Route::get('bookings/{id}', [App\Http\Controllers\BookingController::class, 'show']);
Route::get('booking', [App\Http\Controllers\BookingController::class, 'index']);
Route::get('hirer', [App\Http\Controllers\HirerController::class, 'index']);
Route::get('user', [App\Http\Controllers\UserController::class, 'index']);
Route::post('pricing', [App\Http\Controllers\PricingController::class, 'getPricing']);
Route::post('check/clash', [App\Http\Controllers\CheckController::class, 'clash']);
Route::get('d/asset-type', function () {
    return (new App\Http\Controllers\CollectionController)->collections('asset-type');
});

/** Cron job routes */
Route::get('cron/run', [App\Http\Controllers\CronController::class, 'run']);

/*
|--------------------------------------------------------------------------
| Private (authorised) API Routes
|--------------------------------------------------------------------------
 */

Route::middleware(['auth:sanctum'])->group(function () {

    /** Dashboard statistics/counts */
    Route::get('dashboard/{type}', [App\Http\Controllers\DashboardController::class, 'run']);

    /** Models - mapping {variables} to their model files */
    Route::model('page', 'App\Models\Page');
    Route::model('form', 'App\Models\Form');
    Route::model('asset', 'App\Models\Asset');
    Route::model('collection', 'App\Models\Collection');
    Route::model('dataset', 'App\Models\Dataset');

    /** Page routes */
    Route::resource('page', App\Http\Controllers\PageController::class);
    Route::get('page/{page}/components', [App\Http\Controllers\PageController::class, 'pageComponents']);
    Route::resource('page-component', App\Http\Controllers\PageComponentController::class);

    /** Settings/Config routes */
    Route::resource('settings', App\Http\Controllers\ConfigController::class);

    /** User/Role routes */
    Route::resource('user', App\Http\Controllers\User\UserController::class);
    Route::resource('user-role', App\Http\Controllers\User\UserRoleController::class);
    Route::resource('role', App\Http\Controllers\User\RoleController::class);

    Route::resource('user-asset', App\Http\Controllers\UserAssetController::class);
    Route::resource('user-email', App\Http\Controllers\UserEmailController::class);
    Route::resource('user-permission', App\Http\Controllers\UserPermissionController::class);
    Route::resource('role-email', App\Http\Controllers\RoleEmailController::class);
    Route::resource('role-permission', App\Http\Controllers\RolePermissionController::class);
    Route::get('user-restore/{id}', [App\Http\Controllers\User\UserController::class, 'restore']);

    /** Email routes */
    Route::resource('email', App\Http\Controllers\EmailController::class);
    Route::post('send-email', [App\Http\Controllers\EmailController::class, 'sendEmail']);

    /** Form Routes */
    Route::get('f/{form}', [App\Http\Controllers\Form\FormController::class, 'data']);
    Route::resource('d/form', App\Http\Controllers\Form\FormController::class);
    Route::resource('form', App\Http\Controllers\Form\FormController::class);
    Route::resource('d/form.question', App\Http\Controllers\Form\FormQuestionController::class);
    Route::resource('form.question', App\Http\Controllers\Form\FormQuestionController::class);
    Route::resource('form-question', App\Http\Controllers\Form\FormQuestionController::class);
    Route::resource('permission', App\Http\Controllers\PermissionController::class);

    /** Collection variable override routes (pointing d/{slug} to a different place) */
    Route::resource('d/asset', App\Http\Controllers\AssetController::class);
    Route::resource('d/hirer', App\Http\Controllers\HirerController::class);

    /** Calendar routes */
    Route::get('d/calendar', [App\Http\Controllers\CalendarController::class, 'calendar']);
    Route::resource('calendar', App\Http\Controllers\CalendarController::class);
    Route::get('d/calendar/{id}', [App\Http\Controllers\CalendarController::class, 'show']);
    Route::post('calendar/allow-multiple', [App\Http\Controllers\CalendarController::class, 'allowMultiple']);

    /** Collection routes (dynamic) */
    Route::get('collection', [App\Http\Controllers\CollectionController::class, 'index']);
    Route::get('collection/{reference}', [App\Http\Controllers\CollectionController::class, 'show']);
    Route::get('collection/{reference}/{id}', [App\Http\Controllers\CollectionController::class, 'single']);
    Route::post('collection', [App\Http\Controllers\CollectionController::class, 'store']);
    Route::put('collection/{collection}', [App\Http\Controllers\CollectionController::class, 'update']);
    Route::get('d/{reference}', [App\Http\Controllers\CollectionController::class, 'collections']);
    Route::get('d/{reference}/{id}', [App\Http\Controllers\CollectionController::class, 'collections']);
    Route::post('d/{reference}', [App\Http\Controllers\CollectionController::class, 'store']);
    Route::post('d', [App\Http\Controllers\CollectionController::class, 'store']);
    Route::put('d/{reference}/{collection}', [App\Http\Controllers\CollectionController::class, 'updatePath']);
    Route::put('d/{collection}', [App\Http\Controllers\CollectionController::class, 'update']);
    Route::delete('d/{collection}', [App\Http\Controllers\CollectionController::class, 'destroy']);
    Route::delete('deleteMultiple/{reference}/{id}', [App\Http\Controllers\CollectionController::class, 'deleteMultiple']);
    Route::resource('collection-field', App\Http\Controllers\CollectionFieldController::class);

    /** History routes */
    Route::get('history/{id}/dates', [App\Http\Controllers\CollectionHistoryController::class, 'getDates']);
    Route::get('history/{id}/log', [App\Http\Controllers\CollectionHistoryController::class, 'getLog']);
    Route::resource('history', App\Http\Controllers\CollectionHistoryController::class);

    /** File routes */
    Route::resource('file', App\Http\Controllers\FileController::class);
    Route::post('upload-file', [App\Http\Controllers\FileController::class, 'upload']);

    /** Clash routes */
    Route::get('clashes', [App\Http\Controllers\ClashController::class, 'data']);
    Route::get('check/clash', [App\Http\Controllers\CheckController::class, 'clash']);
    Route::get('check/availability', [App\Http\Controllers\CheckController::class, 'availability']);

    /** Asset routes */
    Route::resource('asset', App\Http\Controllers\AssetController::class);
    Route::get('asset-inspection', [App\Http\Controllers\AssetController::class, 'inspection']);
    Route::get('asset-bookable', [App\Http\Controllers\AssetController::class, 'bookable']);
    Route::get('venue-list', [App\Http\Controllers\AssetController::class, 'venueList']);
    Route::get('asset-list', [App\Http\Controllers\CollectionAssetController::class, 'assetList']);
    Route::get('asset-hirer', [App\Http\Controllers\CollectionHirerController::class, 'assetHirer']);
    Route::get('asset-idlist', [App\Http\Controllers\CollectionAssetController::class, 'assetIdList']);
    Route::get('assets', [App\Http\Controllers\CollectionAssetController::class, 'assets']);
    Route::get('asset-singlelist', [App\Http\Controllers\CollectionAssetController::class, 'assetSingleList']);
    Route::resource('asset-form', App\Http\Controllers\AssetFormController::class);
    Route::get('asset-booking/{id}', [App\Http\Controllers\BookingController::class, 'assetBookings']);
    Route::get('asset-equipments/{parent_id}', [App\Http\Controllers\AssetController::class, 'assetEquipments']);

    /** Booking routes */
    Route::get('booking/dashboard', [App\Http\Controllers\BookingController::class, 'dashboard']);
    Route::get('booking/listing', [App\Http\Controllers\BookingController::class, 'bookingList']);
    Route::resource('booking', App\Http\Controllers\BookingController::class);

    /** Usage routes */
    Route::resource('usage', App\Http\Controllers\UsageController::class);
    Route::get('usage/fees/{id}', [App\Http\Controllers\UsageController::class, 'bookingUsageFees']);
    Route::get('buildCalendarUsage/{id}', [App\Http\Controllers\CalendarController::class, 'buildCalendarUsage']);

    /** Chart routes */
    Route::get('chart/{type}', [App\Http\Controllers\ChartController::class, 'data']);

    /** Hirer routes */
    Route::get('hirer-list', [App\Http\Controllers\HirerController::class, 'simple']);
    Route::get('hirer-type', [App\Http\Controllers\HirerController::class, 'hirerType']);
    Route::get('hirer-memberdetails', [App\Http\Controllers\HirerController::class, 'hirerMemberDetails']);
    Route::resource('hirer', App\Http\Controllers\HirerController::class);
    Route::resource('hirer-asset', App\Http\Controllers\HirerAssetController::class);
    Route::resource('hirer-user', App\Http\Controllers\HirerUserController::class);
    Route::get('hirer-equipment', [App\Http\Controllers\CollectionAssetEquipmentController::class, 'hirerEquipment']);
    Route::get('hirer-key', [App\Http\Controllers\CollectionAssetKeyRegisterController::class, 'hirerKey']);
    Route::get('hirer-storage', [App\Http\Controllers\CollectionAssetStorageController::class, 'hirerStorage']);
    Route::delete('fee-deletebooking/{id}', [App\Http\Controllers\FeeController::class, 'destroyByBookingId']);
    Route::resource('fee', App\Http\Controllers\FeeController::class);

    /** Key/Equipment/Storage routes */
    Route::get('equipment-list', [App\Http\Controllers\CollectionAssetEquipmentController::class, 'equipmentItems']);
    Route::get('key-register', [App\Http\Controllers\CollectionAssetKeyRegisterController::class, 'keyRegisters']);
    Route::get('storage-list', [App\Http\Controllers\CollectionAssetStorageController::class, 'storageItems']);

    /** Migration routes */
    Route::get('migrations', [App\Http\Controllers\MigrationController::class, 'run']);
    Route::get('client-migrations', [App\Http\Controllers\MigrationController::class, 'runMany']);

    /** Payment routes */
    Route::post('payment/pay', [App\Http\Controllers\PaymentController::class, 'pay']);

    /** AI route (posts to OpenAI and returns response) */
    Route::post('ai', [App\Http\Controllers\AiController::class, 'run']);

    /** TODO: Search routes */
    Route::post('search-application', [App\Http\Controllers\Application\ApplicationController::class, 'searchApplication']);

    /** Dataset routes */
    Route::resource('dataset', App\Http\Controllers\DatasetController::class);
    Route::get('dataset/{dataset}/data', [App\Http\Controllers\DatasetController::class, 'data']);
    Route::get('data/{dataset}', [App\Http\Controllers\DatasetController::class, 'data']);
    Route::get('{dataset}', [App\Http\Controllers\DatasetController::class, 'data']);
});
