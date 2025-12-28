<?php

declare(strict_types=1);

namespace AD5jp\Vein;

use AD5jp\Vein\Navigation\NavigationManager;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View as FacadesView;
use Illuminate\Support\ServiceProvider as SupportServiceProvider;
use Illuminate\View\View;

class ServiceProvider extends SupportServiceProvider
{
    public function boot(): void
    {
        // routing
        $this->loadRoutesFrom(__DIR__ . '/../routes/vein.php');

        // views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'vein');

        FacadesView::composer('vein::*', function (View $view) {
            $manager = new NavigationManager();
            $navs = $manager->generate();
            $view->with('navs', $navs);
        });

        // guest redirect
        // TODO プロジェクト本体のカスタマイズと競合するかも
        Authenticate::redirectUsing(static function () {
            if (Route::is('vein.*')) {
                return route('vein.signin');
            }

            return '/';
        });

        // commands
        // $this->commands([
        //     MakeModelsFromSchema::class,
        //     ResetTables::class,
        //     AdminAdd::class,
        //     AdminList::class,
        // ]);

        // publish config & assets
        $this->publishes([
            __DIR__ . '/../config/vein.php' => config_path('vein.php'),
            __DIR__ . '/../assets/admin.css' => public_path('vein-assets/admin.css'),
            __DIR__ . '/../assets/bootstrap.js' => public_path('vein-assets/bootstrap.js'),
        ]);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/vein.php', 'vein');
    }
}
