<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShopController extends Controller
{
    public function create(Request $request)
    {
        $validateData = $request->validate([
            'name' => 'required|string|max:255',
            'desc' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'province' => 'required|string',
        ]);

        $user = Auth::user();

        if ($user->role == 1) {
            return response()->json([
                'message' => 'Admin cannot create shop',
            ], 400);
        }

        $shop = Shop::create([
            'name' => $request->name,
            'desc' => $request->desc,
            'address' => $request->address,
            'city' => $request->city,
            'province' => $request->province,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Shop created successfully',
            'shop' => $shop,
        ], 201);
    }
}
