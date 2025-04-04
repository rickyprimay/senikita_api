<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\Category;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'desc' => 'required|string',
            'address' => 'required|string',
            'city_id' => 'required|integer',
            'categories' => 'required|array',
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

        $invalidCategories = array_diff($request->categories, Category::pluck('id')->toArray());
        if (!empty($invalidCategories)) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Invalid category ID(s): ' . implode(', ', $invalidCategories),
                    'code' => 400,
                ],
                400,
            );
        }

        $user = Auth::user();

        if ($user->role == 1) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Admin cannot create shop',
                    'code' => 400,
                ],
                400,
            );
        }

        $existingShop = Shop::where('user_id', $user->id)->first();
        if ($existingShop) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'User already has a shop',
                    'code' => 400,
                ],
                400,
            );
        }

        $city = City::find($request->city_id);

        $shop = Shop::create([
            'name' => $request->name,
            'desc' => $request->desc,
            'address' => $request->address,
            'city_id' => $request->city_id,
            'province_id' => $city->province_id,
            'user_id' => $user->id,
        ]);

        $shop->categories()->attach($request->categories);

        $user->isHaveStore = 1;
        $user->save();

        $shop->load('city', 'province', 'categories');

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Shop created successfully',
                'shop' => $shop,
                'code' => 201,
            ],
            201,
        );
    }

    public function checkStatusShop()
    {
        $user = Auth::user();

        if ($user->role == 1) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Admin cannot create shop',
                    'code' => 400,
                ],
                400,
            );
        }

        $shop = Shop::where('user_id', $user->id)->first();
        $statusShop = $shop ? $shop->status : 0;

        if ($shop) {
            return response()->json([
                'status' => "success",
                'message' => "Shop status retrieved successfully",
                'status_shop' => $statusShop
            ], 200);
        } else {
            return response()->json([
                'status' => "success",
                'message' => "User does not have a shop",
                'status_shop' => $statusShop
            ], 200);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'desc' => 'sometimes|required|string',
            'address' => 'sometimes|required|string',
            'city_id' => 'sometimes|required|integer',
            'profile_picture' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:6144',
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

        $shop = Shop::where('user_id', Auth::id())->findOrFail($id);

        if ($request->hasFile('profile_picture')) {
            if ($shop->profile_picture) {
                Storage::delete(str_replace(asset('storage/'), '', $shop->profile_picture));
            }

            $path = $request->file('profile_picture')->store('shop/profile', 'public');
            $shop->profile_picture = asset('storage/' . $path);
        } else {
            Log::info('Profile picture not uploaded.');
        }

        if ($request->has('city_id')) {
            $city = City::find($request->city_id);
            if ($city) {
                $shop->city_id = $city->id;
                $shop->province_id = $city->province_id;
            }
        }

        $shop->fill($request->except(['profile_picture', 'province_id']));
        $shop->save();

        $shop->load('city', 'province', 'categories');

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Shop updated successfully',
                'shop' => $shop,
                'code' => 200,
            ],
            200,
        );
    }
}
