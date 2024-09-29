<?php

namespace App\Http\Controllers\Api\User\Service;

use App\Http\Controllers\Controller;
use App\Models\RatingService;
use App\Models\Service;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('pag', 15);

        $search = $request->query('search', null);

        $servicesQuery = Service::with(['category', 'shop.city.province']);

        if ($search) {
            $servicesQuery->where('name', 'LIKE', '%' . $search . '%');
        }

        $services = $servicesQuery->paginate($perPage);

        foreach ($services as $service) {
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

        return response()->json(
            [
                'status' => 'success',
                'code' => 200,
                'message' => 'Services retrieved successfully',
                'data' => $services,
            ],
            200,
        );
    }

    public function show($id, Request $request)
    {
        $service = Service::with(['images', 'ratings', 'category', 'shop.city.province', 'bookmarkService'])->find($id);

        $token = $request->bearerToken();

        if ($token) {
            try {
                JWTAuth::setToken($token);
                $user = JWTAuth::parseToken()->authenticate();
                
                if ($user) {
                    $isBookmarked = $service->bookmark()->where('user_id', $user->id)->exists();
                    $service->is_bookmarked = $isBookmarked;
                }
            } catch (JWTException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token could not be parsed or is invalid',
                ], 401);
            }
        }

        if (!$service) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Service not found.',
                ],
                404,
            );
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

        $cityName = $service->shop && $service->shop->city ? $service->shop->city->name : null;
        $provinceName = $service->shop && $service->shop->city && $service->shop->city->province ? $service->shop->city->province->name : null;
        $region = $cityName && $provinceName ? $cityName . ', ' . $provinceName : 'Region not available';

        if ($service->shop) {
            $service->shop->region = $region;
        }

        $service->ratings = $ratings;
        $service->average_rating = $averageRating;
        $service->rating_count = $ratingCount;

        return response()->json(
            [
                'status' => 'success',
                'code' => 200,
                'message' => 'Service retrieved successfully',
                'service' => $service,
            ],
            200,
        );
    }

    public function randomServices()
    {
        $services = Service::with(['category', 'shop.city.province', 'images'])
            ->inRandomOrder()
            ->limit(5)
            ->get()
            ->map(function ($service) {
                $ratings = RatingService::where('service_id', $service->id)->get();
                $service->average_rating = $ratings->avg('rating') ?? 0;
                $service->rating_count = $ratings->count();

                $cityName = $service->shop && $service->shop->city ? $service->shop->city->name : null;
                $provinceName = $service->shop && $service->shop->city && $service->shop->city->province ? $service->shop->city->province->name : null;
                $region = $cityName && $provinceName ? $cityName . ', ' . $provinceName : 'Region not available';

                if ($service->shop) {
                    $service->shop->region = $region;
                }

                return $service;
            });

        if ($services->isEmpty()) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'No services found.',
                ],
                404,
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'code' => 200,
                'message' => 'Random services retrieved successfully',
                'data' => $services,
            ],
            200,
        );
    }
}
