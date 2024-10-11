<?php

namespace App\Http\Controllers\Api\User\Shop;

use App\Http\Controllers\Controller;
use App\Models\RatingProduct;
use App\Models\RatingService;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{
    public function show($id)
    {
        $shop = Shop::with(['services', 'products', 'categories', 'products.category', 'services.category', 'products.shop', 'services.shop'])->find($id);

        if (!$shop) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Shop not found.'
            ], 404);
        }

        $services = $shop->services->map(function ($service) {
            $ratings = RatingService::where('service_id', $service->id)->get();
            $service->average_rating = $ratings->avg('rating') ?? 0;
            $service->rating_count = $ratings->count();
            return $service;
        });

        $products = $shop->products->map(function ($product) {
            $ratings = RatingProduct::where('product_id', $product->id)->get();
            $product->average_rating = $ratings->avg('rating') ?? 0;
            $product->rating_count = $ratings->count();
            return $product;
        });

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Shop retrieved successfully',
            'data' => $shop,
        ], 200);
    }
    public function cashOutBalance(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'balance' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        if ($request->balance < 100000) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Minimum balance to cash out is 100000.'
            ], 400);
        }

        if ($request->balance > Auth::user()->shop->balance) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Insufficient balance.'
            ], 400);
        }

        $user = Auth::user();

        if (!$user->shop) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'User does not have a shop.'
            ], 404);
        }

        $shop = $user->shop;

        $shop->balance -= $request->balance;
        $shop->save();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Balance cashed out successfully',
            'balance_cash_out' => $request->balance,
            'balance' => $shop->balance,
        ], 200);
    }
}
