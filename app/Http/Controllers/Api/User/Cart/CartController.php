<?php

namespace App\Http\Controllers\Api\User\Cart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;
use App\Models\RatingProduct;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class CartController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $carts = Cart::where('user_id', $user->id)->with('items.product')->get();

        if ($carts->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Cart is empty',
                'data' => [],
            ], 200);
        }

        $carts = $carts->map(function ($cart) {
            $cart->items = $cart->items->map(function ($item) {
                $product = Product::with(['category', 'images', 'shop.city.province', 'bookmark'])
                    ->find($item->product_id);

                if ($product) {
                    $categoryName = $product->category ? $product->category->name : null;
                    $product->category_name = $categoryName;

                    $cityName = $product->shop && $product->shop->city ? $product->shop->city->name : null;
                    $provinceName = $product->shop && $product->shop->city && $product->shop->city->province ? $product->shop->city->province->name : null;
                    $region = $cityName && $provinceName ? $cityName . ', ' . $provinceName : 'Region not available';

                    if ($product->shop) {
                        $product->shop->region = $region;
                    }

                    $ratings = RatingProduct::with(['user', 'ratingImages'])
                        ->where('product_id', $product->id)
                        ->get();

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

                    $token = request()->bearerToken();
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

                    $item->product_details = $product;
                }

                return $item;
            });

            return $cart;
        });

        return response()->json([
            'status' => 'success',
            'data' => $carts,
        ], 200);
    }


    public function destroy($id)
    {
        $user = Auth::user();

        $cart = Cart::where('id', $id)->where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cart not found',
                'code' => 404,
            ], 404);
        }

        $cart->items()->delete();

        $cart->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Cart deleted successfully',
        ], 200);
    }
}
