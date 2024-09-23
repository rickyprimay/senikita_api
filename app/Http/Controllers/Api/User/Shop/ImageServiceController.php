<?php

namespace App\Http\Controllers\Api\User\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ImageService;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImageServiceController extends Controller
{
    public function index($serviceId)
    {
        $user = Auth::user();
        $service = Service::find($serviceId);

        if (!$service || $service->shop_id !== $user->shop->id) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'This service does not belong to your shop.'
            ], 403);
        }

        $images = ImageService::where('service_id', $serviceId)->get();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'images' => $images
        ], 200);
    }
    public function create(Request $request, $serviceId)
    {
        $validator = Validator::make($request->all(), [
            'picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = Auth::user();

        if (!$user->shop) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'User does not have a shop.'
            ], 404);
        }

        $service = Service::find($serviceId);

        if (!$service || $service->shop_id !== $user->shop->id) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'This service does not belong to your shop.'
            ], 403);
        }

        if ($request->hasFile('picture')) {
            $path = $request->file('picture')->store('service_images', 'public');
            $fullPath = asset('storage/' . $path);
        } else {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'No picture provided.'
            ], 400);
        }

        $imageService = ImageService::create([
            'service_id' => $serviceId,
            'picture' => $fullPath,
        ]);

        return response()->json([
            'status' => 'success',
            'code' => 201,
            'message' => 'Image added successfully',
            'image' => $imageService,
        ], 201);
    }

    public function update(Request $request, $serviceId, $imageId)
    {
        $validator = Validator::make($request->all(), [
            'picture' => 'sometimes|required|image|mimes:jpeg,png,jpg,gif|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = Auth::user();
        $imageService = ImageService::find($imageId);

        if (!$imageService) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Image not found.'
            ], 404);
        }

        $service = Service::find($serviceId);

        if (!$service || $service->shop_id !== $user->shop->id) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'This service does not belong to your shop.'
            ], 403);
        }

        if ($request->hasFile('picture')) {
            Storage::disk('public')->delete(str_replace(asset('storage/'), '', $imageService->picture));

            $path = $request->file('picture')->store('service_images', 'public');
            $imageService->picture = asset('storage/' . $path);
        }

        $imageService->save();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Image updated successfully',
            'image' => $imageService,
        ], 200);
    }

    public function destroy($serviceId, $imageId)
    {
        $user = Auth::user();
        $imageService = ImageService::find($imageId);

        if (!$imageService) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Image not found.'
            ], 404);
        }

        $service = Service::find($serviceId);

        if (!$service || $service->shop_id !== $user->shop->id) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'This service does not belong to your shop.'
            ], 403);
        }

        Storage::disk('public')->delete(str_replace(asset('storage/'), '', $imageService->picture));

        $imageService->delete();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Image deleted successfully',
        ], 200);
    }
}
