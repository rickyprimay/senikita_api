<?php

namespace App\Http\Controllers\Api\User\Cart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Cart;

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

        $groupedItems = $items->groupBy(function ($item) {
            return $item->product->shop_id;
        });

        return response()->json([
            'status' => 'success',
            'data' => $groupedItems,
        ], 200);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:product,id',
            'qty' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = Auth::user();

        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        $cartItem = CartItem::where('cart_id', $cart->id)
                            ->where('product_id', $request->product_id)
                            ->first();

        if ($cartItem) {
            $cartItem->qty += $request->qty;
            $cartItem->save();
        } else {
            $cartItem = new CartItem();
            $cartItem->cart_id = $cart->id;
            $cartItem->product_id = $request->product_id;
            $cartItem->qty = $request->qty;
            $cartItem->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Item added to cart',
            'data' => $cartItem,
        ], 201);
    }
    public function updateQty(Request $request, $id)
    {
        $request->validate([
            'qty' => 'required|integer|min:1',
        ]);

        $user = Auth::user();

        $cartItem = CartItem::where('id', $id)
                            ->whereHas('cart', function ($query) use ($user) {
                                $query->where('user_id', $user->id);
                            })
                            ->first();

        if (!$cartItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'Item not found in your cart',
                'code' => 404,
            ], 404);
        }

        $cartItem->qty = $request->input('qty');
        $cartItem->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Item quantity updated successfully',
            'data' => $cartItem,
        ], 200);
    }
    public function destroy($id)
    {
        $user = Auth::user();

        $cartItem = CartItem::where('id', $id)
                            ->whereHas('cart', function ($query) use ($user) {
                                $query->where('user_id', $user->id);
                            })
                            ->first();

        if (!$cartItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'Item not found in your cart',
                'code' => 404,
            ], 404);
        }

        $cartItem->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Item removed from cart',
        ], 200);
    }
}
