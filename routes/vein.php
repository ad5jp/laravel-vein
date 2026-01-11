<?php

declare(strict_types=1);

use AD5jp\Vein\Http\Controllers\AddController;
use AD5jp\Vein\Http\Controllers\EditController;
use AD5jp\Vein\Http\Controllers\HomeController;
use AD5jp\Vein\Http\Controllers\ListController;
use AD5jp\Vein\Http\Controllers\SigninController;
use AD5jp\Vein\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

$admin_uri = config('vein.admin_uri');

Route::group(['middleware' => ['web'], 'prefix' => $admin_uri], static function (): void {
    Route::get('/signin', [SigninController::class, 'init'])->name('vein.signin');
    Route::post('/signin', [SigninController::class, 'signin']);

    $guard = config('vein.admin_guard') ?? config('auth.defaults.guard');

    Route::group(['middleware' => ["auth:{$guard}"]], static function (): void {
        Route::get('/', [HomeController::class, 'init'])->name('vein.home');
        Route::get('/{node}', [ListController::class, 'init'])->name('vein.list');
        Route::get('/page/{node}', [EditController::class, 'init'])->name('vein.page');
        Route::post('/page/{node}', [EditController::class, 'save']);
        Route::get('/{node}/add', [AddController::class, 'init'])->name('vein.add');
        Route::post('/{node}/add', [AddController::class, 'save']);
        Route::get('/{node}/{id}', [EditController::class, 'init'])->name('vein.edit');
        Route::post('/{node}/{id}', [EditController::class, 'save']);
        Route::post('/{node}/{id}/delete', [EditController::class, 'delete'])->name('vein.delete');

        Route::post('/upload', [UploadController::class, 'uploadSingle'])->name('vein.upload');

        Route::post('/signout', [SigninController::class, 'signout'])->name('vein.signout');
    });
});
