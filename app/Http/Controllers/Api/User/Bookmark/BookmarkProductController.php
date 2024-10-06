<?php

namespace App\Http\Controllers\Api\User\Bookmark;

use App\Http\Controllers\Controller;
use App\Models\BookmarkProduct;
use App\Models\RatingProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookmarkProductController extends Controller
{
    public function index()
    {
        $userId = Auth::user()->id;

        $bookmarks = BookmarkProduct::where('user_id', $userId)
            ->with('product.shop.city.province', 'product.category')
            ->get();

        foreach ($bookmarks as $bookmark) {
            $product = $bookmark->product;

            if ($product) {
                $ratings = RatingProduct::where('product_id', $product->id)->get();

                $product->average_rating = $ratings->avg('rating') ?? 0;
                $product->rating_count = $ratings->count();

                $cityName = $product->shop && $product->shop->city ? $product->shop->city->name : null;
                $provinceName = $product->shop && $product->shop->city && $product->shop->city->province ? $product->shop->city->province->name : null;

                $region = $cityName && $provinceName ? $cityName . ', ' . $provinceName : 'Region not available';

                if ($product->shop) {
                    $product->shop->region = $region;
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Bookmarks retrieved successfully',
            'data' => $bookmarks,
        ], 200);
    }


    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:product,id',
        ]);

        $userId = Auth::user()->id;
        $productId = $request->input('product_id');

        $existingBookmark = BookmarkProduct::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($existingBookmark) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bookmark already exists for this product.',
            ], 400);
        }

        $bookmark = BookmarkProduct::create([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $bookmark,
        ], 201);
    }

    public function destroy($id)
    {

        $userId = Auth::user()->id;

        // $bookmark = BookmarkProduct::where('product_id', $id)
        //     ->where('id', $id)
        //     ->first();

        $bookmarkUser = BookmarkProduct::where('user_id', $userId)
            ->where('product_id', $id)
            ->first();

        if (!$bookmarkUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'This bookmark is not yours',
            ], 404);
        }

        if ($bookmarkUser) {
            $bookmarkUser->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Bookmark removed successfully',
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Bookmark not found',
            ], 404);
        }
    }
}
