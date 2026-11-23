<?php

namespace App\Providers;

use App\Support\CachedMySqlBuilder;
use App\Support\CachedMySqlConnection;
use App\Support\ProcurementPortal;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $compiledViewPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'prism_laravel_views';

        File::ensureDirectoryExists($compiledViewPath);

        config([
            'view.compiled' => $compiledViewPath,
        ]);

        if (! $this->app->runningInConsole()) {
            Connection::resolverFor('mysql', function ($connection, $database, $prefix, $config) {
                return new CachedMySqlConnection($connection, $database, $prefix, $config);
            });
        }
    }

    public function boot(): void
    {
        Event::listen(MigrationsEnded::class, function () {
            CachedMySqlBuilder::flush((string) config('database.connections.mysql.database'));
        });

        if (request()->is('maintenance*', 'admin*', 'receiving*', 'purchaser*', 'president*', 'accounting*')) {
            Paginator::defaultView('pagination.maintenance');
        }

        View::composer('*', function ($view) {
            $view->with('pp', ProcurementPortal::prefix());
            $view->with('procurementLayout', ProcurementPortal::layout());
        });

        Event::listen(function (SocialiteWasCalled $event) {

            $event->extendSocialite(
                'microsoft',
                Provider::class
            );

        });
    }
}
