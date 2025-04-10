<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\EloquentUserProvider;

class PinAuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        Auth::provider('pin', function ($app, array $config) {
            return new class($app['hash'], $config['model']) extends EloquentUserProvider {
                public function retrieveByCredentials(array $credentials)
                {
                    if (empty($credentials['pin'])) {
                        return null;
                    }

                    $query = $this->createModel()->newQuery();
                    $query->where('pin', $credentials['pin']);
                    
                    return $query->first();
                }
            };
        });
    }
}
