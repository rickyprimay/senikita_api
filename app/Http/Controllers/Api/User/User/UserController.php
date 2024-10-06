<?php

namespace App\Http\Controllers\Api\User\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function edit(Request $request)
    {
        $user = Auth::user();
        // $user = Auth::user();

        // $user = User::find($id);

        if (!$user) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'User not found',
                    'code' => 404,
                ],
                404,
            );
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'call_number' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'birth_location' => 'nullable|string|max:255',
            'gender' => 'nullable|in:female,male',
            'profile_picture' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ],
                400,
            );
        }

        if ($request->hasFile('profile_picture')) {
            $profilePicturePath = $request->file('profile_picture')->store('profile_pictures', 'public');
            $fullPath = asset('storage/' . $profilePicturePath);
            $user->profile_picture = $fullPath;
        }

        $user->update($request->only([
            'username',
            'call_number',
            'birth_date',
            'birth_location',
            'gender',
            'name',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'User data updated successfully',
            'code' => 200,
            'data' => $user,
        ], 200);
    }

    public function updatePassword(Request $request)
    {
        // dd($request);
        $user = Auth::user();


        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
                'code' => 404,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string|min:8',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        if (!Hash::check($request->input('old_password'), $user->password)) {
            return response()->json([
                'status' => 'error',
                'code' => 401,
                'message' => 'The provided old password is incorrect',
            ], 401);
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Password updated successfully',
            'code' => 200,
        ], 200);
    }

    // get user data
    public function show()
    {
        $user = Auth::user();

        return response()->json([
            'status' => 'success',
            'message' => 'User data retrieved successfully',
            'code' => 200,
            'data' => $user,
        ], 200);
    }
}
