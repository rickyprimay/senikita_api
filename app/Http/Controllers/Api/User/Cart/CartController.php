<?php

namespace App\Http\Controllers\Api\User\Cart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $carts = Cart::where('user_id', $user->id)->with('items')->get();

        if ($carts->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Cart is empty',
                'data' => [],
            ], 200);
        }

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
