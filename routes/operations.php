<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingDocumentController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\Management;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WompiWebhookController;
use App\Http\Middleware\RequirePrivilegedTwoFactor;
use Illuminate\Support\Facades\Route;

Route::get('explorar', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('servicios/{service}', [CatalogController::class, 'show'])->name('catalog.show');
Route::get('blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::post('webhooks/wompi/sandbox', WompiWebhookController::class)->name('webhooks.wompi');

Route::middleware(['auth', 'verified', RequirePrivilegedTwoFactor::class])->group(function () {
    Route::get('notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notificaciones/leer-todas', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('notificaciones/{notification}/leer', [NotificationController::class, 'read'])->whereUuid('notification')->name('notifications.read');
    Route::get('reservas/{booking}/comprobante', BookingDocumentController::class)->name('bookings.document');
    Route::post('reservas/{booking}/pago-prueba', [PaymentController::class, 'store'])->middleware('throttle:10,1')->name('payments.store');
    Route::get('pagos/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    Route::get('mis-reservas', [BookingController::class, 'index'])->name('bookings.index');
    Route::post('salidas/{departure}/reservar', [BookingController::class, 'store'])->middleware('throttle:10,1')->name('bookings.store');
    Route::patch('reservas/{booking}/cancelar', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('favoritos/{service}', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('favoritos/{service}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');
    Route::get('mis-solicitudes', [InquiryController::class, 'index'])->name('inquiries.index');
    Route::post('servicios/{service}/cotizar', [InquiryController::class, 'store'])->middleware('throttle:10,1')->name('inquiries.store');
    Route::patch('mis-solicitudes/{inquiry}/cancelar', [InquiryController::class, 'cancel'])->name('inquiries.cancel');

    Route::prefix('gestion')->name('management.')->group(function () {
        Route::get('integraciones', Management\IntegrationController::class)->middleware('can:settings.manage')->name('integrations.index');
        Route::get('pagos', [Management\PaymentController::class, 'index'])->middleware('can:payments.view')->name('payments.index');
        Route::middleware('can:providers.manage')->group(function () {
            Route::get('proveedores', [Management\ProviderController::class, 'index'])->name('providers.index');
            Route::post('proveedores', [Management\ProviderController::class, 'store'])->name('providers.store');
            Route::put('proveedores/{provider}', [Management\ProviderController::class, 'update'])->name('providers.update');
        });
        Route::get('reservas', [Management\BookingController::class, 'index'])->name('bookings.index');
        Route::patch('reservas/{booking}/confirmar', [Management\BookingController::class, 'confirm'])->name('bookings.confirm');
        Route::get('servicios/{service}/salidas', [Management\DepartureController::class, 'index'])->name('departures.index');
        Route::post('servicios/{service}/salidas', [Management\DepartureController::class, 'store'])->name('departures.store');
        Route::put('servicios/{service}/salidas/{departure}', [Management\DepartureController::class, 'update'])->name('departures.update');
        Route::get('mi-organizacion', [Management\OrganizationController::class, 'index'])->middleware('can:inventory.manage-own')->name('organization.index');
        Route::put('mi-organizacion/{provider}', [Management\OrganizationController::class, 'update'])->middleware('can:inventory.manage-own')->name('organization.update');
        Route::get('servicios', [Management\ServiceController::class, 'index'])->name('services.index');
        Route::post('servicios', [Management\ServiceController::class, 'store'])->name('services.store');
        Route::put('servicios/{service}', [Management\ServiceController::class, 'update'])->name('services.update');
        Route::middleware('can:settings.manage')->group(function () {
            Route::get('usuarios', [Management\UserController::class, 'index'])->name('users.index');
            Route::patch('usuarios/{user}', [Management\UserController::class, 'update'])->name('users.update');
        });
        Route::get('auditoria', Management\AuditController::class)->middleware('can:audit.view')->name('audit.index');
        Route::middleware('can:inquiries.manage')->group(function () {
            Route::get('solicitudes', [Management\InquiryController::class, 'index'])->name('inquiries.index');
            Route::patch('solicitudes/{inquiry}', [Management\InquiryController::class, 'update'])->name('inquiries.update');
        });
        Route::middleware('can:content.manage')->group(function () {
            Route::get('articulos', [Management\PostController::class, 'index'])->name('posts.index');
            Route::post('articulos', [Management\PostController::class, 'store'])->name('posts.store');
            Route::put('articulos/{post}', [Management\PostController::class, 'update'])->name('posts.update');
        });
    });
});
