<?php

namespace App\Http\Controllers\Api\User\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ImageProduct;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImageProductController extends Controller
{
    public function index($productId)
    {
        $user = Auth::user();
        $product = Product::find($productId);

        if (!$product || $product->shop_id !== $user->shop->id) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'This product does not belong to your shop.'
            ], 403);
        }

        $images = ImageProduct::where('product_id', $productId)->get();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'images' => $images
        ], 200);
    }
    public function create(Request $request, $productId)
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

        $product = Product::find($productId);

        if (!$product || $product->shop_id !== $user->shop->id) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'This product does not belong to your shop.'
            ], 403);
        }

        if ($request->hasFile('picture')) {
            $path = $request->file('picture')->store('product_images', 'public');
            $fullPath = asset('storage/' . $path);
        } else {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'No picture provided.'
            ], 400);
        }

        $imageProduct = ImageProduct::create([
            'product_id' => $productId,
            'picture' => $fullPath,
        ]);

        return response()->json([
            'status' => 'success',
            'code' => 201,
            'message' => 'Image added successfully',
            'image' => $imageProduct,
        ], 201);
    }

    public function update(Request $request, $productId, $imageId)
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
        $imageProduct = ImageProduct::find($imageId);

        if (!$imageProduct) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Image not found.'
            ], 404);
        }

        $product = Product::find($productId);

        if (!$product || $product->shop_id !== $user->shop->id) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'This product does not belong to your shop.'
            ], 403);
        }

        if ($request->hasFile('picture')) {
            Storage::disk('public')->delete(str_replace(asset('storage/'), '', $imageProduct->picture));

            $path = $request->file('picture')->store('product_images', 'public');
            $imageProduct->picture = asset('storage/' . $path);
        }

        $imageProduct->save();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Image updated successfully',
            'image' => $imageProduct,
        ], 200);
    }

    public function destroy($productId, $imageId)
    {
        $user = Auth::user();
        $imageProduct = ImageProduct::find($imageId);

        if (!$imageProduct) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Image not found.'
            ], 404);
        }

        $product = Product::find($productId);

        if (!$product || $product->shop_id !== $user->shop->id) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'This product does not belong to your shop.'
            ], 403);
        }

        Storage::disk('public')->delete(str_replace(asset('storage/'), '', $imageProduct->picture));

        $imageProduct->delete();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Image deleted successfully',
        ], 200);
    }
}
