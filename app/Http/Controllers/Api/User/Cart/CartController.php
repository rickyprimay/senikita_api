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

    $carts = Cart::where('user_id', $user->id)
        ->with(['items.product.category', 'items.product.images', 'items.product.shop.city.province'])
        ->get();

    if ($carts->isEmpty()) {
        return response()->json([
            'status' => 'success',
            'message' => 'Cart is empty',
            'data' => [],
        ], 200);
    }

    // Token validation and user authentication
    $token = request()->bearerToken();
    $isAuthenticatedUser = null;

    if ($token) {
        try {
            JWTAuth::setToken($token);
            $isAuthenticatedUser = JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token could not be parsed or is invalid',
            ], 401);
        }
    }

    // Process carts and format the response
    $response = [];

    $carts->each(function ($cart) use (&$response, $isAuthenticatedUser) {
        $cart->items->each(function ($item) use (&$response, $isAuthenticatedUser) {
            $product = $item->product;

            // Add product and store details in the desired format
            $response[] = [
                'storeName' => $product->shop ? $product->shop->name : 'Unknown Store',
                'storeAvatar' => $product->shop ? $product->shop->avatar : 'https://via.placeholder.com/100',
                'storeLocation' => $product->shop && $product->shop->city ? $product->shop->city->name : 'Unknown Location',
                'productName' => $product->name,
                'productThumbnail' => $product->images->first() ? $product->images->first()->url : 'https://via.placeholder.com/150',
                'productPrice' => $product->price,
                'qty' => $item->qty, // Menambahkan quantity
                'shop_id' => $product->shop_id, // Menambahkan shop_id
                'product_id' => $product->id, // Menambahkan product_id
            ];
        });
    });

    return response()->json([
        'status' => 'success',
        'data' => $response,
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
