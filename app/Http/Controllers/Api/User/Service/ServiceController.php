<?php

namespace App\Http\Controllers\Api\User\Service;

use App\Http\Controllers\Controller;
use App\Models\RatingService;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('pag', 15);

        $services = Service::with('category')->paginate($perPage);

        foreach ($services as $service) {
            $ratings = RatingService::where('service_id', $service->id)->get();
            $service->average_rating = $ratings->avg('rating') ?? 0;
            $service->rating_count = $ratings->count();
        }

        return response()->json(
            [
                'status' => 'success',
                'code' => 200,
                'message' => 'Service retrieved successfully',
                'data' => $services,
            ],
            200,
        );
    }

    public function show($id)
    {
        $service = Service::with(['images', 'ratings', 'category', 'shop'])->find($id);

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Service not found.'
            ], 404);
        }

        $categoryName = $service->category ? $service->category->name : null;
        $service->category_name = $categoryName;

        $ratings = RatingService::with(['user', 'ratingImages'])
            ->where('service_id', $service->id)
            ->get();

        $averageRating = $ratings->avg('rating');
        $ratingCount = $ratings->count();

        $ratings = $ratings->map(function ($rating) {
            return [
                'id' => $rating->id,
                'rating' => $rating->rating,
                'comment' => $rating->comment,
                'user_name' => $rating->user ? $rating->user->name : 'Unknown',
                'images' => $rating->ratingImages->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'picture_rating_service' => $image->picture_rating_service,
                    ];
                }),
            ];
        });

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Service retrieved successfully',
            'service' => $service,
            'shop' => $service->shop,
            'ratings' => $ratings,
            'average_rating' => $averageRating,
            'rating_count' => $ratingCount,
        ], 200);
    }
    
    public function randomServices()
    {
        $services = Service::with('images')->inRandomOrder()->limit(5)->get();

        if ($services->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'No services found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Random services retrieved successfully',
            'data' => $services,
        ], 200);
    }
}
