<?php

namespace App\Http\Controllers\Api\User\Bookmark;

use App\Http\Controllers\Controller;
use App\Models\BookmarkService;
use App\Models\RatingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookmarkServiceController extends Controller
{
    public function index()
    {
        $userId = Auth::user()->id;

        $bookmarks = BookmarkService::where('user_id', $userId)
            ->with('service.shop.city.province', 'service.category')
            ->get();

        foreach ($bookmarks as $bookmark) {
            $service = $bookmark->service;

            if ($service) {
                $ratings = RatingService::where('service_id', $service->id)->get();

                $service->average_rating = $ratings->avg('rating') ?? 0;
                $service->rating_count = $ratings->count();

                $cityName = $service->shop && $service->shop->city ? $service->shop->city->name : null;
                $provinceName = $service->shop && $service->shop->city && $service->shop->city->province ? $service->shop->city->province->name : null;

                $region = $cityName && $provinceName ? $cityName . ', ' . $provinceName : 'Region not available';

                if ($service->shop) {
                    $service->shop->region = $region;
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

        // $bookmark = BookmarkService::where('service_id', $id)
        //     ->where('id', $id)
        //     ->first();

        $bookmarkUser = BookmarkService::where('user_id', $userId)
            ->where('service_id', $id)
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
