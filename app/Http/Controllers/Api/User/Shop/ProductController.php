<?php

namespace App\Http\Controllers\Api\User\Shop;

use App\Http\Controllers\Controller;
use App\Models\ImageProduct;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user->shop) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'User does not have a shop.',
                ],
                404,
            );
        }

        $products = Product::where('shop_id', $user->shop->id)->get();

        if ($products->isEmpty()) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'No products found for this shop.',
                ],
                404,
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'code' => 200,
                'products' => $products,
            ],
            200,
        );
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'desc' => 'required|string',
            'stock' => 'required|integer',
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif|max:5000',
            'category_id' => 'nullable|exists:category,id',
            'image_product.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ],
                400,
            );
        }

        $user = Auth::user();

        if (!$user->shop) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'User does not have a shop.',
                ],
                404,
            );
        }

        $shop_id = $user->shop->id;

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $fullPath = asset('storage/' . $path);
        } else {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'No thumbnail provided.',
                ],
                400,
            );
        }

        $product = Product::create([
            'name' => $request->name,
            'price' => $request->price,
            'desc' => $request->desc,
            'stock' => $request->stock,
            'thumbnail' => $fullPath,
            'category_id' => $request->category_id,
            'shop_id' => $shop_id,
        ]);

        $imageProducts = [];

        if ($request->hasFile('image_product')) {
            foreach ($request->file('image_product') as $image) {
                $imagePath = $image->store('product_images', 'public');
                $fullImagePath = asset('storage/' . $imagePath);

                $imageProduct = ImageProduct::create([
                    'product_id' => $product->id,
                    'picture' => $fullImagePath,
                ]);

                $imageProducts[] = $imageProduct;
            }
        }

        return response()->json(
            [
                'status' => 'success',
                'code' => 201,
                'message' => 'Product created successfully',
                'product' => $product,
                'image_product' => $imageProducts,
            ],
            201,
        );
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric',
            'desc' => 'sometimes|required|string',
            'stock' => 'sometimes|required|integer',
            'status' => 'sometimes|required|boolean',
            'thumbnail' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:5000',
            'category_id' => 'nullable|exists:category,id',
            'image_product.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ],
                400,
            );
        }

        $user = Auth::user();
        $product = Product::findOrFail($id);

        if ($product->shop_id !== $user->shop->id) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 403,
                    'message' => 'This product does not belong to your shop.',
                ],
                403,
            );
        }

        if ($request->hasFile('thumbnail')) {
            Storage::disk('public')->delete(str_replace(asset('storage/'), '', $product->thumbnail));
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $product->thumbnail = asset('storage/' . $path);
        }

        $product->update($request->only(['name', 'price', 'desc', 'stock', 'status', 'category_id']));

        $imageProducts = [];

        if ($request->hasFile('image_product')) {
            ImageProduct::where('product_id', $product->id)->delete();

            foreach ($request->file('image_product') as $image) {
                $imagePath = $image->store('product_images', 'public');
                $fullImagePath = asset('storage/' . $imagePath);

                $imageProduct = ImageProduct::create([
                    'product_id' => $product->id,
                    'picture' => $fullImagePath,
                ]);

                $imageProducts[] = $imageProduct;
            }
        } else {
            $imageProducts = ImageProduct::where('product_id', $product->id)->get();
        }

        return response()->json(
            [
                'status' => 'success',
                'code' => 200,
                'message' => 'Product updated successfully',
                'product' => $product,
                'image_product' => $imageProducts,
            ],
            200,
        );
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $product = Product::findOrFail($id);

        if ($product->shop_id !== $user->shop->id) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 403,
                    'message' => 'This product does not belong to your shop.',
                ],
                403,
            );
        }

        Storage::disk('public')->delete(str_replace(asset('storage/'), '', $product->thumbnail));

        $product->delete();

        return response()->json(
            [
                'status' => 'success',
                'code' => 200,
                'message' => 'Product deleted successfully',
            ],
            200,
        );
    }

    public function show($id)
    {
        $user = Auth::user();
        $product = Product::find($id);

        if (!$product) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Product not found.',
                ],
                404,
            );
        }

        if ($product->shop_id !== $user->shop->id) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 403,
                    'message' => 'This product does not belong to your shop.',
                ],
                403,
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'code' => 200,
                'product' => $product,
            ],
            200,
        );
    }
}
