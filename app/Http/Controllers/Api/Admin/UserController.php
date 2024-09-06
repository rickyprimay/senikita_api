<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getAllUsers()
    {
        $users = User::all();

        return response()->json([
            'message' => 'All users retrieved successfully',
            'data' => $users
        ], 200);
    }
}
