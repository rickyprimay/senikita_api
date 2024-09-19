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

        $products = Product::paginate($perPage);

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
        $products = Product::inRandomOrder()->limit(5)->get();

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
        $product = Product::with(['category', 'images'])->find($id);

        if (!$product) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Product not found',
                ],
                404
            );
        }

        $categoryName = $product->category ? $product->category->name : null;
        $product->category_name = $categoryName;

        $ratings = RatingProduct::where('product_id', $id)->get();

        if ($ratings->isEmpty()) {
            return response()->json(
                [
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Product found, but no ratings available',
                    'product' => $product,
                    'images' => $product->images,
                    'ratings' => [],
                ]
            );
        }

        $averageRating = $ratings->avg('rating');
        $ratingCount = $ratings->count();

        return response()->json(
            [
                'status' => 'success',
                'code' => 200,
                'message' => 'Show Product retrieved successfully',
                'product' => $product,
                // 'images' => $product->images,
                'ratings' => $ratings,
                'average_rating' => $averageRating,
                'rating_count' => $ratingCount,
            ]
        );
    }
}
