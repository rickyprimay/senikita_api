<?php

namespace App\Http\Controllers\Api\User\Shop;

use App\Http\Controllers\Controller;
use App\Models\RatingProduct;
use App\Models\RatingService;
use App\Models\Shop;
use Illuminate\Http\Request;

class DetailShopControler extends Controller
{
    public function getShopDetails($shopId)
    {
        $shop = Shop::with(['categories','products.ratings', 'products.category', 'services.ratings', 'services.category'])->find($shopId);

        if (!$shop) {
            return response()->json([
                'status' => 'error',
                'message' => 'Shop not found',
            ], 404);
        }

        $totalProductRatings = 0;
        $totalProductCount = 0;
        $totalProductsSold = 0;
        $productsData = [];

        foreach ($shop->products as $product) {
            $productRatings = $product->ratings;
            if ($productRatings->isNotEmpty()) {
                $totalProductRatings += $productRatings->sum('rating');
                $totalProductCount += $productRatings->count();
            }
            $totalProductsSold += $product->sold;

            $averageRating = $productRatings->avg('rating') ?? 0;
            $ratingCount = $productRatings->count();
            
            $productsData[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'thumbnail' => $product->thumbnail,
                'price' => $product->price,
                'stock' => $product->stock,
                'description' => $product->desc, 
                'status' => $product->status,    
                'category_id' => $product->category_id,
                'category_name' => $product->category ? $product->category->name : null,
                'sold' => $product->sold,        
                'average_rating' => $averageRating, 
                'rating_count' => $ratingCount,
            ];
        }

        $totalServiceRatings = 0;
        $totalServiceCount = 0;
        $totalServicesSold = 0;
        $servicesData = [];

        foreach ($shop->services as $service) {
            $serviceRatings = $service->ratings;
        
            if ($serviceRatings->isNotEmpty()) {
                $totalServiceRatings += $serviceRatings->sum('rating');
                $totalServiceCount += $serviceRatings->count();
            }
            $totalServicesSold += $service->sold;
        
            $averageRating = $serviceRatings->avg('rating') ?? 0;
            $ratingCount = $serviceRatings->count();
        
            $servicesData[] = [
                'id' => $service->id,
                'name' => $service->name,
                'price' => $service->price,
                'description' => $service->desc,
                'status' => $service->status, 
                'sold' => $service->sold, 
                'average_rating' => $averageRating, 
                'rating_count' => $ratingCount, 
                'thumbnail' => $service->thumbnail, 
                'person_amount' => $service->person_amount,
                'category_name' => $service->category ? $service->category->name : null,
            ];
        }

        $averageProductRating = $totalProductCount > 0 ? $totalProductRatings / $totalProductCount : 0;
        $averageServiceRating = $totalServiceCount > 0 ? $totalServiceRatings / $totalServiceCount : 0;
        $averageShop = ($averageProductRating + $averageServiceRating) / 2;

        $fullLocation = ($shop->city ? $shop->city->name : 'Unknown') . ', ' . 
                ($shop->city && $shop->city->province ? $shop->city->province->name : 'Unknown');

        return response()->json([
            'status' => 'success',
            'data' => [
                'shop' => [
                    'id' => $shop->id,
                    'name' => $shop->name,
                    'description' => $shop->desc,
                    'location' => $fullLocation,
                    'average_shop_rating' => $averageShop,
                    'total_products_sold' => $totalProductsSold,
                    'total_services_sold' => $totalServicesSold,
                    'categories' => $shop->categories,
                    'products' => $productsData, 
                    'services' => $servicesData, 
                ],
            ],
        ], 200);
    }

}
