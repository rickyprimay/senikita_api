<?php

namespace App\Http\Controllers\Api\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCodeMail;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        if (User::where('email', $request->email)->exists()) {
            return response()->json(
                [
                    'message' => 'Email already registered',
                    'code' => 409,
                ],
                409,
            );
        }

        $otp = User::generateOTP();

        $credentials = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'otp' => $otp,
            'otp_sent_at' => Carbon::now(),
        ]);

        $details = [
            'name' => $credentials->name,
            'otp' => $otp,
        ];

        Mail::to($credentials->email)->send(new VerificationCodeMail($details));

        return response()->json(
            [
                'message' => 'Register Success, check your email to verification OTP',
                'code' => 201,
                'user' => $credentials,
            ],
            201,
        );
    }

    public function verifyOTP(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|string|email|max:255',
            'otp' => 'required|string|min:6|max:6',
        ]);

        $credential = User::where('email', $request->email)->first();

        if (!$credential) {
            return response()->json(
                [
                    'message' => 'User not found',
                    'code' => 404,
                ],
                404,
            );
        }

        if ($credential->otp !== $request->otp) {
            return response()->json(
                [
                    'message' => 'Invalid OTP',
                    'code' => 400,
                ],
                400,
            );
        }

        $otpExpiryMinutes = 5;
        $otpSentAt = Carbon::parse($credential->otp_sent_at);
        if ($otpSentAt->addMinutes($otpExpiryMinutes)->isPast()) {
            return response()->json(
                [
                    'message' => 'OTP has expired',
                    'code' => 400,
                ],
                400,
            );
        }

        $token = JWTAuth::fromUser($credential);

        $credential->otp = null;
        $credential->otp_sent_at = null;
        $credential->email_verified_at = Carbon::now();  
        $credential->save();

        return response()->json(
            [
                'message' => 'OTP verified successfully',
                'code' => 200,
                'user' => $credential,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
            200,
        );
    }
    public function resendOTP(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|string|email|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
                'code' => 404,
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email is already verified',
                'code' => 400,
            ], 400);
        }

        $resendInterval = 1;
        if ($user->otp_sent_at && Carbon::parse($user->otp_sent_at)->diffInMinutes(Carbon::now()) < $resendInterval) {
            $remainingTime = 60 - Carbon::parse($user->otp_sent_at)->diffInSeconds(Carbon::now());
            return response()->json([
                'message' => "You can request a new OTP after {$remainingTime} seconds",
                'remaining_times' => $remainingTime,
                'code' => 429,
            ], 429);
        }

        $otp = User::generateOTP();

        $user->otp = $otp;
        $user->otp_sent_at = Carbon::now();
        $user->save();

        $details = [
            'name' => $user->name,
            'otp' => $otp
        ];

        Mail::to($user->email)->send(new VerificationCodeMail($details));

        return response()->json([
            'message' => 'OTP has been resent, please check your email',
            'code' => 200,
        ], 200);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!($token = JWTAuth::attempt($credentials))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $credentials = User::where('email', $request->email)->first();

        if (is_null($credentials->email_verified_at)) {
            $otp = User::generateOTP();

            $credentials->otp = User::generateOTP();
            $credentials->otp_sent_at = now();
            $credentials->save(); 

            $details = [
                'name' => $credentials->name,
                'otp' => $otp,
            ];
            Mail::to($credentials->email)->send(new VerificationCodeMail($details));

            return response()->json([
                'error' => 'Email not verified. Please check your email to verify your OTP.',
                'code' => 403,
            ], 403);
        }

        return $this->respondWithToken($token);
    }
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ]);
    }
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'message' => 'Successfully logged out',
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Token is invalid'], 401);
        }
    }
}
