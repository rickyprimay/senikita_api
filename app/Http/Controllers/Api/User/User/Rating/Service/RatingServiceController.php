<?php

namespace App\Http\Controllers\Api\User\User\Rating\Service;

use App\Http\Controllers\Controller;
use App\Models\RatingService;
use App\Models\RatingServiceImage;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingServiceController extends Controller
{
    public function index() {

    }
    public function store(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'required|string',
            'images_rating.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5000',
        ]);

        $service = Service::find($id);

        if (!$service) {
            return response()->json(['message' => 'Service Not Found'], 404);
        }

        $user_id = Auth::user()->id;

        $rating = RatingService::create([
            'user_id' => $user_id,
            'service_id' => $id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        $ratingServiceImage = [];

        if ($request->hasFile('images_rating')) {
            foreach ($request->file('images_rating') as $image) {
                $imagePath = $image->store('rating_service_images', 'public');
                $fullImagePath = asset('storage/' . $imagePath);

                $ratingSerivceImage = RatingServiceImage::create([
                    'rating_service_id' => $rating->id,
                    'picture_rating_service' => $fullImagePath,
                ]);
                $ratingServiceImage[] = $ratingSerivceImage;
            }
        }


        return response()->json([
            'status' => 'success',
            'message' => 'Rating and images uploaded successfully',
            'rating' => $rating,
            'images' => $ratingServiceImage,
        ], 201);
    }
}
