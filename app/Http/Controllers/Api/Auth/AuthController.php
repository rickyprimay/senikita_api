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
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
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

        if (User::where('email', $request->email)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email already registered',
                'code' => 409,
            ], 409);
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

        return response()->json([
            'status' => 'success',
            'message' => 'Register Success, check your email to verify OTP',
            'code' => 201,
            'user' => $credentials,
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

        $credential = User::where('email', $request->email)->first();

        if (!$credential) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
                'code' => 404,
            ], 404);
        }

        if ($credential->otp !== $request->otp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid OTP',
                'code' => 400,
            ], 400);
        }

        $otpExpiryMinutes = 5;
        $otpSentAt = Carbon::parse($credential->otp_sent_at);
        if ($otpSentAt->addMinutes($otpExpiryMinutes)->isPast()) {
            return response()->json([
                'status' => 'error',
                'message' => 'OTP has expired',
                'code' => 400,
            ], 400);
        }

        $token = JWTAuth::fromUser($credential);

        $credential->otp = null;
        $credential->otp_sent_at = null;
        $credential->email_verified_at = Carbon::now();
        $credential->save();

        return response()->json([
            'status' => 'success',
            'message' => 'OTP verified successfully',
            'code' => 200,
            'user' => $credential,
            'access_token' => $token,
            'token_type' => 'Bearer',
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

        $credentials = User::where('email', $request->email)->first();

        if (!$credentials) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
                'code' => 404,
            ], 404);
        }

        if ($credentials->email_verified_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email is already verified',
                'code' => 400,
            ], 400);
        }

        $resendInterval = 1;
        if ($credentials->otp_sent_at && Carbon::parse($credentials->otp_sent_at)->diffInMinutes(Carbon::now()) < $resendInterval) {
            $remainingTime = 60 - Carbon::parse($credentials->otp_sent_at)->diffInSeconds(Carbon::now());
            return response()->json([
                'status' => 'error',
                'message' => "You can request a new OTP after {$remainingTime} seconds",
                'remaining_times' => $remainingTime,
                'code' => 429,
            ], 429);
        }

        $otp = User::generateOTP();

        $credentials->otp = $otp;
        $credentials->otp_sent_at = Carbon::now();
        $credentials->save();

        $details = [
            'name' => $credentials->name,
            'otp' => $otp,
        ];

        Mail::to($credentials->email)->send(new VerificationCodeMail($details));

        return response()->json([
            'status' => 'success',
            'message' => 'OTP has been resent, please check your email',
            'code' => 200,
        ], 200);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!($token = JWTAuth::attempt($credentials))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'code' => 401,
            ], 401);
        }

        $credentials = User::where('email', $request->email)->first();

        if (is_null($credentials->email_verified_at)) {
            $otp = User::generateOTP();

            $credentials->otp = $otp;
            $credentials->otp_sent_at = now();
            $credentials->save();

            $details = [
                'name' => $credentials->name,
                'otp' => $otp,
            ];
            Mail::to($credentials->email)->send(new VerificationCodeMail($details));

            return response()->json([
                'status' => 'error',
                'message' => 'Email not verified. Please check your email to verify your OTP.',
                'code' => 403,
            ], 403);
        }

        $shop = Shop::where('user_id', $credentials->id)->first();

        if ($shop) {
            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'code' => 200,
                'token' => $token,
                'shop' => $shop,
            ], 200);
        }

        return $this->respondWithToken($token);
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

            return response()->json([
                'status' => 'success',
                'access_token' => $newToken,
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
}
