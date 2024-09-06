<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function getAllUsers()
    {
        $credentials = User::all();

        return response()->json([
            'message' => 'All users retrieved successfully',
            'data' => $credentials
        ], 200);
    }

    public function getUser($id)
    {
        $credentials = User::find($id);

        if (!$credentials) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'message' => 'User retrieved successfully',
            'data' => $credentials
        ], 200);
    }

    public function createUser(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $credentials = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'data' => $credentials
        ], 201);
    }

    public function updateUser(Request $request, $id)
    {
        $credentials = User::find($id);

        if (!$credentials) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$credentials->id,
            'password' => 'sometimes|string|min:8',
        ]);

        if (isset($validatedData['name'])) {
            $credentials->name = $validatedData['name'];
        }

        if (isset($validatedData['email'])) {
            $credentials->email = $validatedData['email'];
        }

        if (isset($validatedData['password'])) {
            $credentials->password = Hash::make($validatedData['password']);
        }

        $credentials->save();

        return response()->json([
            'message' => 'User updated successfully',
            'data' => $credentials
        ], 200);
    }

    public function deleteUser($id)
    {
        $credentials = User::find($id);

        if (!$credentials) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        $credentials->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ], 200);
    }
}
