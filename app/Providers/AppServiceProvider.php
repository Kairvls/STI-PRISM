<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
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
    }

    public function boot(): void
    {
        if (request()->is('maintenance*')) {
            Paginator::defaultView('pagination.maintenance');
        }

        Event::listen(function (SocialiteWasCalled $event) {

            $event->extendSocialite(
                'microsoft',
                Provider::class
            );

        });
    }
}
