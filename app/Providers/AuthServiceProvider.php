<?php

namespace App\Providers;

use App\Models\Vendor;
use App\Policies\VendorPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Vendor::class => VendorPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Passport::routes();
        /*Passport::tokensExpireIn(now()->addDays(2));
        Passport::refreshTokensExpireIn(now()->addDays(2));
        Passport::personalAccessTokensExpireIn(now()->addDays(2));*/
    }
}
