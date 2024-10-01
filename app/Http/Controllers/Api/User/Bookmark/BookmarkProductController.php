<?php

namespace App\Http\Controllers\Api\User\Bookmark;

use App\Http\Controllers\Controller;
use App\Models\BookmarkProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookmarkProductController extends Controller
{
    public function index()
    {
        $userId = Auth::user()->id;

        $bookmarks = BookmarkProduct::where('user_id', $userId)
                        ->with('product')
                        ->get();

        return response()->json([
            'status' => 'success',
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

        $bookmark = BookmarkProduct::where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if ($bookmark) {
            $bookmark->delete();

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
