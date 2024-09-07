<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
        ]);

        $invalidCategories = array_diff($request->categories, Category::pluck('id')->toArray());
        if (!empty($invalidCategories)) {
            return response()->json([
                'message' => 'Invalid category ID(s): ' . implode(', ', $invalidCategories),
            ], 400);
        }

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
    
    public function update(Request $request, $id)
    {
        $validateData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'desc' => 'sometimes|required|string',
            'address' => 'sometimes|required|string',
            'city' => 'sometimes|required|string',
            'province' => 'sometimes|required|string',
            'profile_picture' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:6144',
        ]);

        $shop = Shop::where('user_id', Auth::id())->findOrFail($id);

        if ($request->hasFile('profile_picture')) {
            if ($shop->profile_picture) {
                Storage::delete(str_replace(asset('storage/'), '', $shop->profile_picture));
            }
        
            $path = $request->file('profile_picture')->store('shop/profile', 'public');
        
            $shop->profile_picture = asset('storage/' . $path);
        } else {
            Log::info("cannot u[ploaded");
        }

        $shop->fill($request->except(['lat', 'lng', 'profile_picture']));

        $shop->save();

        return response()->json([
            'message' => 'Shop updated successfully',
            'shop' => $shop,
        ], 200);
    }
}
