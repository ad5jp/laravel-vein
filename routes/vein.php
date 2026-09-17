<?php

declare(strict_types=1);

use AD5jp\Vein\Auth\AdminGuard;
use AD5jp\Vein\Http\Controllers\AddController;
use AD5jp\Vein\Http\Controllers\EditController;
use AD5jp\Vein\Http\Controllers\HomeController;
use AD5jp\Vein\Http\Controllers\ListController;
use AD5jp\Vein\Http\Controllers\PasswordController;
use AD5jp\Vein\Http\Controllers\SigninController;
use AD5jp\Vein\Http\Controllers\UploadController;
use AD5jp\Vein\Http\Middleware\Authenticate;
use AD5jp\Vein\Http\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;

$admin_uri = config('vein.admin_uri');

Route::group(['middleware' => ['web'], 'prefix' => $admin_uri], static function (): void {
    Route::get('/signin', [SigninController::class, 'init'])->name('vein.signin');
    Route::post('/signin', [SigninController::class, 'signin'])->middleware('throttle:5,1');

    $guard = AdminGuard::name();

    // AuthenticateSession は Authenticate より後ろ。既定のガードが差し替わってから
    // 効かせる（@see AuthenticateSession のコメント）
    Route::group(['middleware' => [Authenticate::class.":{$guard}", AuthenticateSession::class]], static function (): void {
        Route::get('/', [HomeController::class, 'init'])->name('vein.home');

        // /{node} が 1 セグメントを総取りするので、その前に置く
        Route::get('/password', [PasswordController::class, 'init'])->name('vein.password');
        Route::post('/password', [PasswordController::class, 'update']);

        Route::get('/{node}', [ListController::class, 'init'])->name('vein.list');
        Route::get('/page/{node}', [EditController::class, 'init'])->name('vein.page');
        Route::post('/page/{node}', [EditController::class, 'save']);
        Route::get('/{node}/add', [AddController::class, 'init'])->name('vein.add');
        Route::post('/{node}/add', [AddController::class, 'save']);
        Route::post('/{node}/sort', [ListController::class, 'sort'])->name('vein.sort');
        Route::get('/{node}/{id}', [EditController::class, 'init'])->name('vein.edit');
        Route::post('/{node}/{id}', [EditController::class, 'save']);
        Route::post('/{node}/{id}/delete', [EditController::class, 'delete'])->name('vein.delete');

        Route::post('/upload', [UploadController::class, 'uploadSingle'])->name('vein.upload');

        Route::post('/signout', [SigninController::class, 'signout'])->name('vein.signout');
    });
});
