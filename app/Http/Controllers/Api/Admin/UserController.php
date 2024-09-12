<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function getAllUsers(Request $request)
    {
        $perPage = $request->query('pag', 15);

        $credentials = User::paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'All users retrieved successfully',
            'code' => 200,
            'data' => $credentials
        ], 200);
    }

    public function getUser($id)
    {
        $credentials = User::find($id);

        if (!$credentials) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
                'code' => 404
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User retrieved successfully',
            'code' => 200,
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
            'status' => 'success',
            'message' => 'User created successfully',
            'code' => 201,
            'data' => $credentials
        ], 201);
    }

    public function updateUser(Request $request, $id)
    {
        $credentials = User::find($id);

        if (!$credentials) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
                'code' => 404
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,'.$credentials->id,
            'password' => 'nullable|string|min:8',
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

        $credentials->save();

        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully',
            'code' => 200,
            'data' => $credentials
        ], 200);
    }

    public function deleteUser($id)
    {
        $credentials = User::find($id);

        if (!$credentials) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
                'code' => 404
            ], 404);
        }

        $credentials->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully',
            'code' => 200
        ], 200);
    }
    public function search(Request $request)
{
    $query = $request->query('name');

    if (!$query) {
        return response()->json([
            'status' => 'error',
            'message' => 'Name query parameter is required',
            'code' => 400
        ], 400);
    }

    $users = User::where('name', 'LIKE', '%' . $query . '%')->get();

    if ($users->isEmpty()) {
        return response()->json([
            'status' => 'error',
            'message' => 'No users found',
            'code' => 404
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Users found',
        'code' => 200,
        'data' => $users
    ], 200);
}


}
