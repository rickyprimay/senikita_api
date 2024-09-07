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
    public function create(Request $request, $id)
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

        $product = Product::find($id);

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
            'product_id' => $id,
            'picture' => $fullPath,
        ]);

        return response()->json([
            'status' => 'success',
            'code' => 201,
            'message' => 'Image added successfully',
            'image' => $imageProduct,
        ], 201);
    }
}
