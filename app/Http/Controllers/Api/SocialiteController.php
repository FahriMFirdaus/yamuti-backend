<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;
use App\Traits\ApiResponse;

class SocialiteController extends Controller
{
    use ApiResponse;

    /**
     * Dapatkan URL Redirect ke Google
     */
    public function redirect(): JsonResponse
    {
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();

        return $this->successResponse(['url' => $url], 'Redirect URL generated');
    }

    /**
     * Tangani Callback dari Google
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                ]
            );

            if ($user->wasRecentlyCreated && !$user->hasRole('donatur')) {
                $user->assignRole('donatur');
            }

            $user->tokens()->delete();

            $token = $user->createToken('auth_token')->plainTextToken;
            
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            return redirect()->away($frontendUrl . '/auth/callback?token=' . $token);

        } catch (\Exception $e) {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            return redirect()->away($frontendUrl . '/auth/login?error=google_auth_failed');
        }
    }
}
