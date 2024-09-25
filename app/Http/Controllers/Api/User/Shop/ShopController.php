<?php

namespace App\Http\Controllers\Api\User\Shop;

use App\Http\Controllers\Controller;
use App\Models\RatingProduct;
use App\Models\RatingService;
use App\Models\Shop;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function show($id)
    {
        $shop = Shop::with(['services', 'products'])->find($id);

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
}
