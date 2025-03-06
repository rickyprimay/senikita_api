<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class SocialiteController extends Controller
{
    public function googleLogin(Request $request)
    {
        $isMobile = $request->has('redirect') && $request->redirect == 'mobile';
        
        $redirectUrl = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();

        return response()->json([
            'status' => 'success',
            'message' => 'Google login URL generated',
            'url' => $redirectUrl,
            'is_mobile' => $isMobile,
        ], 200);
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'profile_picture' => $googleUser->getAvatar(),
                    'password' => bcrypt(Str::random(16)),
                ]
            );

            $token = JWTAuth::fromUser($user);

            if ($request->has('redirect') && $request->redirect == 'mobile') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Login successful',
                    'token' => $token,
                    'user' => $user,
                ], 200);
            }

            return redirect()->away('https://senikita.my.id/callback-google?jwt_token=' . urlencode($token));
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}