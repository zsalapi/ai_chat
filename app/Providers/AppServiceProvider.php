<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;
use Illuminate\Auth\Notifications\ResetPassword;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.url').'/password-reset?token='.$token.'&email='.$notifiable->getEmailForPasswordReset();
        });

        if ($this->app->environment('production') || true) { // Forcing for this task as requested
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
        
         Response::macro('success', function ($message = '', $data = null, $statusCode = 200) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data
            ], $statusCode);
        });

        
        Response::macro('error', function ($message, $statusCode = 400) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $statusCode);
        });
    }
}
