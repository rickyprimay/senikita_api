<?php

namespace App\Http\Controllers\Api\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCodeMail;
use App\Models\Shop;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    public function profile(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                    'code' => 404,
                ], 404);
            }

            $shop = Shop::where('user_id', $user->id)->first();

            $response = [
                'status' => 'success',
                'message' => 'Profile retrieved successfully',
                'code' => 200,
                'user' => $user
            ];

            if ($shop) {
                $response['shop'] = $shop;
            }

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized or invalid token',
                'code' => 401,
            ], 401);
        }
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if ($user) {
            if ($user->email_verified_at === null) {
                $otp = User::generateOTP();
                $user->update([
                    'otp' => $otp,
                    'otp_sent_at' => Carbon::now(),
                ]);

                $details = [
                    'name' => $user->name,
                    'otp' => $otp,
                ];

                Mail::to($user->email)->send(new VerificationCodeMail($details));

                return response()->json([
                    'status' => 'success',
                    'message' => 'Email already registered but not verified. OTP has been resent.',
                    'code' => 200,
                    'user' => $user,
                ], 200);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Email already registered',
                'code' => 409,
            ], 409);
        }

        $otp = User::generateOTP();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'otp' => $otp,
            'otp_sent_at' => Carbon::now(),
        ]);

        $details = [
            'name' => $user->name,
            'otp' => $otp,
        ];

        Mail::to($user->email)->send(new VerificationCodeMail($details));

        return response()->json([
            'status' => 'success',
            'message' => 'Register Success, check your email to verify OTP',
            'code' => 201,
            'user' => $user,
        ], 201);
    }


    public function verifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'otp' => 'required|string|min:6|max:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
                'code' => 404,
            ], 404);
        }

        if ($user->otp !== $request->otp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid OTP',
                'code' => 400,
            ], 400);
        }

        $otpExpiryMinutes = 5;
        $otpSentAt = Carbon::parse($user->otp_sent_at);
        if ($otpSentAt->addMinutes($otpExpiryMinutes)->isPast()) {
            return response()->json([
                'status' => 'error',
                'message' => 'OTP has expired',
                'code' => 400,
            ], 400);
        }

        $token = JWTAuth::fromUser($user);

        $user->otp = null;
        $user->otp_sent_at = null;
        $user->email_verified_at = Carbon::now();
        $user->save();
        $user->token = $token;
        $user->token_type = 'bearer';

        return response()->json([
            'status' => 'success',
            'message' => 'OTP verified successfully',
            'code' => 200,
            'user' => $user,
            'expires_in' => config('jwt.ttl') * 60,
        ], 200);
    }

    public function resendOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
                'code' => 404,
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email is already verified',
                'code' => 400,
            ], 400);
        }

        $resendInterval = 1;
        if ($user->otp_sent_at && Carbon::parse($user->otp_sent_at)->diffInMinutes(Carbon::now()) < $resendInterval) {
            $remainingTime = 60 - Carbon::parse($user->otp_sent_at)->diffInSeconds(Carbon::now());
            return response()->json([
                'status' => 'error',
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
            'otp' => $otp,
        ];

        Mail::to($user->email)->send(new VerificationCodeMail($details));

        return response()->json([
            'status' => 'success',
            'message' => 'OTP has been resent, please check your email',
            'code' => 200,
        ], 200);
    }

    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email not found',
                'code' => 404,
            ], 404);
        }

        $credentials = $request->only('email', 'password');

        if (!($token = JWTAuth::attempt($credentials))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Incorrect password',
                'code' => 401,
            ], 401);
        }

        if (is_null($user->email_verified_at)) {
            $otp = User::generateOTP();
            $user->otp = $otp;
            $user->otp_sent_at = now();
            $user->save();

            $details = [
                'name' => $user->name,
                'otp' => $otp,
            ];
            Mail::to($user->email)->send(new VerificationCodeMail($details));

            return response()->json([
                'status' => 'error',
                'message' => 'Email not verified. Please check your email to verify your OTP.',
                'code' => 403,
            ], 403);
        }

        $shop = Shop::where('user_id', $user->id)->first();

        $user->token = $token;
        $user->token_type = 'bearer';

        $response = [
            'status' => 'success',
            'message' => 'Login successful',
            'code' => 200,
            'user' => $user,
        ];

        if ($shop) {
            $response['shop'] = $shop;
        }

        return response()->json($response, 200);
    }


    protected function respondWithToken($token)
    {
        return response()->json([
            'status' => 'success',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'code' => 200,
        ], 200);
    }

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully logged out',
                'code' => 200,
            ], 200);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token is invalid',
                'code' => 401,
            ], 401);
        }
    }

    public function refreshToken()
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            $user = Auth::user();

            return response()->json([
                'status' => 'success',
                'access_token' => $newToken,
                'user' => $user,
                'token_type' => 'Bearer',
                'expires_in' => config('jwt.ttl') * 60,
                'code' => 200,
            ], 200);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token is invalid',
                'code' => 401,
            ], 401);
        }
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['status' => 'success', 'message' => 'Password reset link sent'], 200)
            : response()->json(['status' => 'error', 'message' => 'Unable to send reset link'], 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'token', 'password', 'password_confirmation'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['status' => 'success', 'message' => 'Password reset successfully'], 200)
            : response()->json(['status' => 'error', 'message' => 'Invalid token or email'], 400);
    }
}
