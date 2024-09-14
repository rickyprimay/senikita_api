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

        $search = $request->query('search');

        $query = User::query();

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $users = $query->paginate($perPage);

        return response()->json(
            [
                'status' => 'success',
                'message' => 'All users retrieved successfully',
                'code' => 200,
                'data' => $users,
            ],
            200,
        );
    }

    public function getUser($id)
    {
        $credentials = User::find($id);

        if (!$credentials) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'User not found',
                    'code' => 404,
                ],
                404,
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'User retrieved successfully',
                'code' => 200,
                'data' => $credentials,
            ],
            200,
        );
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

        return response()->json(
            [
                'status' => 'success',
                'message' => 'User created successfully',
                'code' => 201,
                'data' => $credentials,
            ],
            201,
        );
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::find($id);

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
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $user->id,
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

        $user->name = $request->input('name', $user->name);
        $user->email = $request->input('email', $user->email);

        if ($request->filled('password')) {
            $user->password = bcrypt($request->input('password'));
        }

        $user->save();

        return response()->json(
            [
                'status' => 'success',
                'message' => 'User updated successfully',
                'code' => 200,
                'data' => $user,
            ],
            200,
        );
    }

    public function deleteUser($id)
    {
        $credentials = User::find($id);

        if (!$credentials) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'User not found',
                    'code' => 404,
                ],
                404,
            );
        }

        $credentials->delete();

        return response()->json(
            [
                'status' => 'success',
                'message' => 'User deleted successfully',
                'code' => 200,
            ],
            200,
        );
    }
}
