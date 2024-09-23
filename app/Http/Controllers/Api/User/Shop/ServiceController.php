<?php

namespace App\Http\Controllers\Api\User\Shop;

use App\Http\Controllers\Controller;
use App\Models\ImageService;
use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user->shop) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'User does not have a shop.'
            ], 404);
        }

        $services = Service::with(['category', 'images', 'bookmarkService'])
            ->withCount(['bookmarkService'])
            ->where('shop_id', $user->shop->id)
            ->get();

        if ($services->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'No services found for this shop.'
            ], 404);
        }

        $services->each(function ($service) {
            $service->category_name = $service->category ? $service->category->name : null;
        });

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'services' => $services,
        ], 200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'desc' => 'required|string',
            'type' => 'required',
            'status' => 'nullable|integer',
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif|max:5000',
            'person_amount' => 'nullable|integer',
            'category_id' => 'nullable|exists:category,id',
            'service_image.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5000',
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

        $shop_id = $user->shop->id;

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('service_thumbnails', 'public');
            $fullPath = asset('storage/' . $path);
        } else {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'No thumbnail provided.'
            ], 400);
        }

        $service = Service::create([
            'name' => $request->name,
            'price' => $request->price,
            'desc' => $request->desc,
            'type' => $request->type,
            'status' => $request->status ?? 0,
            'thumbnail' => $fullPath,
            'person_amount' => $request->person_amount,
            'category_id' => $request->category_id,
            'shop_id' => $shop_id,
        ]);

        $serviceImages = [];

        if ($request->hasFile('service_image')) {
            foreach ($request->file('service_image') as $image) {
                $imagePath = $image->store('service_images', 'public');
                $fullImagePath = asset('storage/' . $imagePath);

                $serviceImage = ImageService::create([
                    'service_id' => $service->id,
                    'picture' => $fullImagePath,
                ]);

                $serviceImages[] = $serviceImage;
            }
        }

        return response()->json([
            'status' => 'success',
            'code' => 201,
            'message' => 'Service created successfully',
            'service' => $service,
            'service_images' => $serviceImages,
        ], 201);
    }

    public function show($id)
    {
        $user = Auth::user();
        $service = Service::with(['category', 'images', 'bookmarkService'])
            ->withCount(['bookmarkService'])
            ->find($id);

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Service not found.'
            ], 404);
        }

        if ($service->shop_id !== $user->shop->id) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'This service does not belong to your shop.'
            ], 403);
        }

        $service->category_name = $service->category ? $service->category->name : null;

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'service' => $service,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric',
            'desc' => 'sometimes|required|string',
            'type' => 'sometimes|required',
            'status' => 'sometimes|integer',
            'thumbnail' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:5000',
            'person_amount' => 'sometimes|nullable|integer',
            'category_id' => 'sometimes|nullable|exists:category,id',
            'service_image.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5000',
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
        $service = Service::find($id);

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Service not found.'
            ], 404);
        }

        if ($service->shop_id !== $user->shop->id) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'This service does not belong to your shop.'
            ], 403);
        }

        if ($request->hasFile('thumbnail')) {
            Storage::disk('public')->delete(str_replace(asset('storage/'), '', $service->thumbnail));

            $path = $request->file('thumbnail')->store('service_thumbnails', 'public');
            $service->thumbnail = asset('storage/' . $path);
        }

        $service->update($request->only([
            'name', 'price', 'desc', 'type', 'status', 'person_amount', 'category_id'
        ]));

        $serviceImages = [];

        if ($request->hasFile('service_image')) {
            ImageService::where('service_id', $service->id)->delete();

            foreach ($request->file('service_image') as $image) {
                $imagePath = $image->store('service_images', 'public');
                $fullImagePath = asset('storage/' . $imagePath);

                $serviceImage = ImageService::create([
                    'service_id' => $service->id,
                    'picture' => $fullImagePath,
                ]);

                $serviceImages[] = $serviceImage;
            }
        } else {
            $serviceImages = ImageService::where('service_id', $service->id)->get();
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Service updated successfully',
            'service' => $service,
            'service_images' => $serviceImages,
        ], 200);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $service = Service::find($id);

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Service not found.'
            ], 404);
        }

        if ($service->shop_id !== $user->shop->id) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'This service does not belong to your shop.'
            ], 403);
        }

        Storage::disk('public')->delete(str_replace(asset('storage/'), '', $service->thumbnail));

        $serviceImages = ImageService::where('service_id', $service->id)->get();
        foreach ($serviceImages as $serviceImage) {
            Storage::disk('public')->delete(str_replace(asset('storage/'), '', $serviceImage->picture));
            $serviceImage->delete();
        }

        $service->delete();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Service deleted successfully',
        ], 200);
    }
}