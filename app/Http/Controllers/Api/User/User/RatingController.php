<?php

namespace App\Http\Controllers\Api\User\User;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\RatingProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    public function index($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $ratings = RatingProduct::where('product_id', $id)->get();

        return response()->json([
            'status' => 'success',
            'ratings' => $ratings,
        ], 200);
    }

    public function store(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'required|string',
        ]);

        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $rating = RatingProduct::create([
            'user_id' => Auth::user()->id,
            'product_id' => $id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Rating added successfully',
            'rating' => $rating,
        ], 201);
    }
}
