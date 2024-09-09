<?php

namespace App\Http\Controllers\Api\User\Cart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;

class CartItemController extends Controller
{
    public function index($cart_id)
    {
        $items = CartItem::where('cart_id', $cart_id)->with('product')->get();

        if ($items->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No items in the cart',
                'data' => [],
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ], 200);
    }
}
