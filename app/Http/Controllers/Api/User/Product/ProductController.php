<?php

namespace App\Http\Controllers\Api\User\Product;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\RatingProduct;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('pag', 15);

        $search = $request->query('search', null);

        $productsQuery = Product::with(['category', 'shop.city.province']);

        if ($search) {
            $productsQuery->where('name', 'LIKE', '%' . $search . '%');
        }

        $products = $productsQuery->paginate($perPage);

        foreach ($products as $product) {
            $ratings = RatingProduct::where('product_id', $product->id)->get();
            $product->average_rating = $ratings->avg('rating') ?? 0;
            $product->rating_count = $ratings->count();

            $cityName = $product->shop && $product->shop->city ? $product->shop->city->name : null;
            $provinceName = $product->shop && $product->shop->city && $product->shop->city->province ? $product->shop->city->province->name : null;
            $region = $cityName && $provinceName ? $cityName . ', ' . $provinceName : 'Region not available';

            if ($product->shop) {
                $product->shop->region = $region;
            }
        }

        return response()->json(
            [
                'status' => 'success',
                'code' => 200,
                'message' => 'Products retrieved successfully',
                'data' => $products,
            ],
            200,
        );
    }

    public function randomProducts(Request $request)
    {
        $cityId = $request->query('city_id');
        $categoryId = $request->query('category_id');

        $query = Product::with(['category', 'shop.city.province']);

        if (!is_null($cityId)) {
            $query->whereHas('shop.city', function ($q) use ($cityId) {
                $q->where('id', $cityId);
            });
        }

        if (!is_null($categoryId)) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->inRandomOrder()
            ->limit(5)
            ->get()
            ->map(function ($product) {
                $ratings = RatingProduct::where('product_id', $product->id)->get();
                $product->average_rating = $ratings->avg('rating') ?? 0;
                $product->rating_count = $ratings->count();

                $cityName = $product->shop && $product->shop->city ? $product->shop->city->name : null;
                $provinceName = $product->shop && $product->shop->city && $product->shop->city->province ? $product->shop->city->province->name : null;
                $region = $cityName && $provinceName ? $cityName . ', ' . $provinceName : 'Region not available';

                if ($product->shop) {
                    $product->shop->region = $region;
                }

                return $product;
            });

        // if ($products->isEmpty()) {
        //     return response()->json([
        //         'status' => 'error',
        //         'code' => 404,
        //         'message' => 'No Product found.',
        //     ], 404);
        // }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Random products retrieved successfully',
            'data' => $products,
        ], 200);
    }


    public function show($id, Request $request)
    {
        $product = Product::with(['category', 'images', 'shop.city.province', 'bookmark', 'ratings', 'ratings.user', 'ratings.ratingImages'])->find($id);


        $token = $request->bearerToken();

        if ($token) {
            try {
                JWTAuth::setToken($token);
                $user = JWTAuth::parseToken()->authenticate();

                if ($user) {
                    $isBookmarked = $product->bookmark()->where('user_id', $user->id)->exists();
                    $product->is_bookmarked = $isBookmarked;
                }
            } catch (JWTException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token could not be parsed or is invalid',
                ], 401);
            }
        }

        if (!$product) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Product not found',
                ],
                404,
            );
        }

        $categoryName = $product->category ? $product->category->name : null;
        $product->category_name = $categoryName;

        $ratings = RatingProduct::with(['user', 'ratingImages'])
            ->where('product_id', $id)
            ->get();


        $cityName = $product->shop && $product->shop->city ? $product->shop->city->name : null;
        $provinceName = $product->shop && $product->shop->city && $product->shop->city->province ? $product->shop->city->province->name : null;
        $region = $cityName && $provinceName ? $cityName . ', ' . $provinceName : 'Region not available';

        if ($product->shop) {
            $product->shop->region = $region;
        }

        $averageRating = $ratings->avg('rating');
        $ratingCount = $ratings->count();

        $ratings = $ratings->map(function ($rating) {
            return [
                'id' => $rating->id,
                'rating' => $rating->rating,
                'comment' => $rating->comment,
                'user_name' => $rating->user ? $rating->user->name : 'Unknown',
                'images' => $rating->ratingImages->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'picture_rating_product' => $image->picture_rating_product,
                    ];
                }),
            ];
        });

        $product->ratings = $ratings;
        $product->average_rating = $averageRating;
        $product->rating_count = $ratingCount;

        if ($ratings->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Product found, but no ratings available',
                'product' => $product,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Show Product retrieved successfully',
            'product' => $product,
        ]);
    }
    public function topShops(Request $request)
    {
        $shops = Shop::with(['city.province', 'categories'])
            ->select(
                'shop.*',
                DB::raw('(SELECT SUM(sold) FROM product WHERE product.shop_id = shop.id) AS total_product_sold'),
                DB::raw('(SELECT SUM(sold) FROM service WHERE service.shop_id = shop.id) AS total_service_sold')
            )
            ->orderBy(DB::raw('total_product_sold + total_service_sold'), 'desc')
            ->limit(5)
            ->get();

        foreach ($shops as $shop) {
            $cityName = $shop->city ? $shop->city->name : null;
            $provinceName = $shop->city && $shop->city->province ? $shop->city->province->name : null;
            $shop->region = $cityName && $provinceName ? $cityName . ', ' . $provinceName : 'Region not available';

            $shop->total_product_sold = $shop->total_product_sold ?? 0;
            $shop->total_service_sold = $shop->total_service_sold ?? 0;
            $shop->total_sold = $shop->total_product_sold + $shop->total_service_sold;

            $shop->categories = $shop->categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
            });
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Top 5 shops retrieved successfully',
            'data' => $shops,
        ], 200);
    }
}
