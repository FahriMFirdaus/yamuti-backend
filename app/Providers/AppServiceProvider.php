<?php

namespace App\Providers;

use App\Events\PengadaanBarangDibuat;
use App\Listeners\CatatDraftPengeluaranKas;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        // API Umum
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Form Publik (Donasi, Kunjungan)
        RateLimiter::for('public-forms', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Keamanan Autentikasi (Mencegah Brute Force Login)
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Keamanan Email Spam (Lupa Password)
        RateLimiter::for('reset-password', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        // Keamanan Broadcast WA (Mencegah Spam WA)
        RateLimiter::for('broadcast', function (Request $request) {
            return Limit::perMinute(2)->by($request->user()?->id ?: $request->ip());
        });

        Event::listen(
            PengadaanBarangDibuat::class,
            CatatDraftPengeluaranKas::class
        );

        // Kustomisasi Link Reset Password untuk Frontend (SPA)
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

            return $frontendUrl."/reset-password?token={$token}&email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
