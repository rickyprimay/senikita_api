<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\Category;
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
            'categories' => 'required|array',
            'categories.*' => 'exists:category,id'
        ]);

        $user = Auth::user();

        if ($user->role == 1) {
            return response()->json([
                'message' => 'Admin cannot create shop',
            ], 400);
        }

        $existingShop = Shop::where('user_id', $user->id)->first();
        if ($existingShop) {
            return response()->json([
                'message' => 'User already has a shop',
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

        $shop->categories()->attach($request->categories);

        $shop->load('categories');

        return response()->json([
            'message' => 'Shop created successfully',
            'shop' => $shop,
        ], 201);
    }
}
