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

        return response()->json([
            'status' => 'success',
            'data' => $carts,
        ], 200);
    }
}
