<?php

namespace App\Http\Controllers\Api\User\Bookmark;

use App\Http\Controllers\Controller;
use App\Models\BookmarkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookmarkServiceController extends Controller
{
    public function index ()
    {
        $userId = Auth::user()->id;

        $bookmarks = BookmarkService::where('user_id', $userId)->get();

        return response()->json([
            'status' => 'success',
            'data' => $bookmarks,
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:service,id',
        ]);

        $userId = Auth::user()->id;
        $serviceId = $request->input('service_id');

        $existingBookmark = BookmarkService::where('user_id', $userId)
            ->where('service_id', $serviceId)
            ->first();

        if ($existingBookmark) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bookmark already exists for this service.',
            ], 400);
        }

        $bookmark = BookmarkService::create([
            'user_id' => $userId,
            'service_id' => $serviceId,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $bookmark,
        ], 201);
    }

    public function destroy($id)
    {
        $userId = Auth::user()->id;

        $bookmark = BookmarkService::where('user_id', $userId)
                ->where('id', $id)
                ->firstOrFail();

            $bookmark->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Bookmark removed successfully',
        ], 200);
    }
}
