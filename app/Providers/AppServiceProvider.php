<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;

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
        Event::listen(function (SocialiteWasCalled $event) {

            $event->extendSocialite(
                'microsoft',
                Provider::class
            );

        });
    }
}
