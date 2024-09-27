<?php

namespace App\Http\Controllers\Api\User\Product;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\RatingProduct;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('pag', 15);

        $products = Product::with('category')->paginate($perPage);

        foreach ($products as $product) {
            $ratings = RatingProduct::where('product_id', $product->id)->get();
            $product->average_rating = $ratings->avg('rating') ?? 0;
            $product->rating_count = $ratings->count();
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

    public function randomProducts()
    {
        $products = Product::with('category', 'shop')
            ->inRandomOrder()
            ->limit(5)
            ->get()
            ->map(function ($product) {
                $ratings = RatingProduct::where('product_id', $product->id)->get();
                $product->average_rating = $ratings->avg('rating');
                $product->rating_count = $ratings->count();
                return $product;
            });

        

        return response()->json(
            [
                'status' => 'success',
                'code' => 200,
                'message' => 'Random products retrieved successfully',
                'data' => $products,
            ],
            200,
        );
    }

    public function show($id)
    {
        $product = Product::with(['category', 'images', 'shop'])->find($id);

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

        if ($ratings->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Product found, but no ratings available',
                'product' => $product,
                'images' => $product->images,
                'ratings' => [],
            ]);
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

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Show Product retrieved successfully',
            'product' => $product,
            'shop' => $product->shop,
            'ratings' => $ratings,
            'average_rating' => $averageRating,
            'rating_count' => $ratingCount,
        ]);
    }
}
