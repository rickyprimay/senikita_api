<?php

namespace App\Http\Controllers\Api\User\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user->shop) {
            return response()->json(['message' => 'User does not have a shop.'], 404);
        }

        $products = Product::where('shop_id', $user->shop->id)->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No products found for this shop.'], 404);
        }

        return response()->json($products, 200);
    }

    public function create(Request $request)
    {
        $validateData = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'desc' => 'required|string',
            'stock' => 'required|integer',
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'category_id' => 'nullable|exists:category,id',
        ]);

        $user = Auth::user();

        if (!$user->shop) {
            return response()->json(['message' => 'User does not have a shop.'], 404);
        }

        $shop_id = $user->shop->id;

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('thumbnails', 'public');

            $fullPath = asset('storage/' . $path);
        } else {
            return response()->json(['message' => 'No thumbnail provided.'], 400);
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

        return response()->json(
            [
                'message' => 'Product created successfully',
                'product' => $product,
            ],
            201,
        );
    }

    public function update(Request $request, $id)
    {
        $validateData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric',
            'desc' => 'sometimes|required|string',
            'stock' => 'sometimes|required|integer',
            'status' => 'sometimes|required|boolean',
            'thumbnail' => 'sometimes|image',
            'category_id' => 'nullable|exists:category,id',
        ]);

        $product = Product::findOrFail($id);

        if ($request->hasFile('thumbnail')) {
            Storage::disk('public')->delete($product->thumbnail);

            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $product->thumbnail = $path;
        }

        $product->update($request->only(['name', 'price', 'desc', 'stock', 'status', 'category_id']));

        return response()->json(
            [
                'message' => 'Product updated successfully',
                'product' => $product,
            ],
            200,
        );
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        Storage::disk('public')->delete($product->thumbnail);

        $product->delete();

        return response()->json(
            [
                'message' => 'Product deleted successfully',
            ],
            200,
        );
    }
}
