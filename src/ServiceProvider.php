<?php

declare(strict_types=1);

namespace AD5jp\Vein;

use AD5jp\Vein\Navigation\NavigationManager;
use Illuminate\Support\Facades\View as FacadesView;
use Illuminate\Support\ServiceProvider as SupportServiceProvider;
use Illuminate\View\View;

class ServiceProvider extends SupportServiceProvider
{
    public function boot(): void
    {
        // routing
        $this->loadRoutesFrom(__DIR__.'/../routes/vein.php');

        // views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'vein');

        FacadesView::composer('vein::*', function (View $view) {
            $manager = new NavigationManager;
            $navs = $manager->generate();
            $view->with('navs', $navs);
        });

        // 未認証時の飛び先は AD5jp\Vein\Http\Middleware\Authenticate が決める。
        // Authenticate::redirectUsing() はアプリ全体に効くグローバル上書きなので使わない。

        // commands
        // $this->commands([
        //     MakeModelsFromSchema::class,
        //     ResetTables::class,
        //     AdminAdd::class,
        //     AdminList::class,
        // ]);

        // publish config & assets
        $this->publishes([
            __DIR__.'/../config/vein.php' => config_path('vein.php'),
            // __DIR__ . '/../assets/admin.css' => public_path('vein-assets/admin.css'),
            // __DIR__ . '/../assets/bootstrap.js' => public_path('vein-assets/bootstrap.js'),
            __DIR__.'/../assets' => public_path('vein-assets'),
        ]);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/vein.php', 'vein');
    }
}
