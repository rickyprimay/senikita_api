<?php

namespace App\Http\Controllers\Api\User\User\Rating\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\RatingProduct;
use App\Models\RatingProductImage;
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

        return response()->json(
            [
                'status' => 'success',
                'ratings' => $ratings,
            ],
            200,
        );
    }

    public function store(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'required|string',
            'images_rating.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5000',
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

        $imageRatingProducts = [];

        if ($request->hasFile('images_rating')) {
            foreach ($request->file('images_rating') as $image) {
                $imagePath = $image->store('rating_images', 'public');
                $fullImagePath = asset('storage/' . $imagePath);

                $imageRatingProduct = RatingProductImage::create([
                    'rating_product_id' => $rating->id,
                    'picture_rating_product' => $fullImagePath,
                ]);

                $imageRatingProducts[] = $imageRatingProduct;
            }
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Rating and images uploaded successfully',
                'rating' => $rating,
                'images' => $imageRatingProducts,
            ],
            201,
        );
    }
}
