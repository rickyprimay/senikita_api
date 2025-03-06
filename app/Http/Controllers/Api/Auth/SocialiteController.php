<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Google_Client;

class SocialiteController extends Controller
{
    public function googleLogin()
    {
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();

        return response()->json([
            'status' => 'success',
            'message' => 'Google login URL generated',
            'url' => $url,
        ], 200);
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'profile_picture' => $googleUser->getAvatar(),
                    'password' => bcrypt(Str::random(16)),
                ]);
            }

            $token = JWTAuth::fromUser($user);

            if ($request->query('response_type') === 'json') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Authentication successful',
                    'token' => $token,
                    'user' => $user,
                ], 200);
            }

            $redirectUrl = 'https://senikita.my.id/callback-google?jwt_token=' . urlencode($token);
            return redirect()->away($redirectUrl);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function verifyGoogleToken(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        try {
            $client = new Google_Client(['client_id' => env('GOOGLE_CLIENT_ID_iOS')]);
            $payload = $client->verifyIdToken($request->id_token);

            if (!$payload) {
                return response()->json(['status' => 'error', 'message' => 'Invalid ID token'], 401);
            }

            $email = $payload['email'];
            $name = $payload['name'] ?? 'User';
            $avatar = $payload['picture'] ?? null;

            $user = User::where('email', $email)->first();
            if (!$user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'profile_picture' => $avatar,
                    'password' => bcrypt(Str::random(16)),
                ]);
            }

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'status' => 'success',
                'message' => 'Authentication successful',
                'token' => $token,
                'user' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
