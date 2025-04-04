<?php

namespace App\Http\Controllers\Api\User\Shop;

use App\Http\Controllers\Controller;
use App\Models\ImageProduct;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderService;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
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

        $products = Product::with(['category', 'images', 'bookmark', 'cart'])
            ->withCount(['bookmark', 'cartItems'])
            ->where('shop_id', $user->shop->id)
            ->get();

        if ($products->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'No products found for this shop.'
            ], 404);
        }

        $products->each(function ($product) {
            $product->category_name = $product->category ? $product->category->name : null;

            $product->rating_average = DB::table('rating_product')
                ->where('product_id', $product->id)
                ->avg('rating') ?: 0;

            $product->ratings = DB::table('rating_product')
                ->where('product_id', $product->id)
                ->get();
        });

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'products' => $products,
        ], 200);
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
            'product_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5000',
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

        if ($request->hasFile('product_images')) {
            foreach ($request->file('product_images') as $image) {
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
                'product_images' => $imageProducts,
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
            'product_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5000',
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

        if ($request->hasFile('product_images')) {
            ImageProduct::where('product_id', $product->id)->delete();

            foreach ($request->file('product_images') as $image) {
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
                'product_images' => $imageProducts,
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
        $product = Product::with(['category', 'images', 'bookmark', 'cart'])
            ->withCount(['bookmark', 'cartItems'])
            ->find($id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Product not found.'
            ], 404);
        }

        if ($product->shop_id !== $user->shop->id) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'This product does not belong to your shop.'
            ], 403);
        }

        $product->category_name = $product->category ? $product->category->name : null;

        $product->rating_average = DB::table('rating_product')
            ->where('product_id', $product->id)
            ->avg('rating') ?: 0;

        $product->ratings = DB::table('rating_product')
            ->where('product_id', $product->id)
            ->get();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'product' => $product,
        ], 200);
    }


    public function setStatus($id)
    {
        $order = Order::findorFail($id);

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Order not found.'
            ], 404);
        }

        if ($order->status_order == 'delivered' || $order->status_order == 'rejected') {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Cannot update the status because it is already delivered or rejected.'
            ], 400);
        }

        $order->status_order = "delivered";
        $order->save();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Order status updated successfully',
            'order' => $order,
        ], 200);
    }

    public function setStatusReject($id)
    {
        $order = Order::findorFail($id);

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Order not found.'
            ], 404);
        }

        if ($order->status_order == 'delivered' || $order->status_order == 'rejected') {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Cannot update the status because it is already delivered or rejected.'
            ], 400);
        }

        $order->status_order = "rejected";
        $order->save();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Order status updated successfully',
            'order' => $order,
        ], 200);
    }

    public function getOrdersByShop()
    {
        $user = Auth::user();

        if (!$user->shop) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'User does not have a shop.',
            ], 404);
        }

        $shop_id = $user->shop->id;

        $products = Product::where('shop_id', $shop_id)->pluck('id')->toArray();

        if (empty($products)) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'No products found for this shop.',
            ], 404);
        }

        $orders = Order::whereHas('product', function ($query) use ($products) {
            $query->whereIn('product_id', $products);
        })->with('product', 'address', 'address.city', 'address.province')->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'No orders found for this shop.',
            ], 404);
        }

        $orders = $orders->map(function ($order) {
            $order->resi_code = "JNE" . rand(100000000, 999999999);
            return $order;
        });

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'orders' => $orders,
        ], 200);
    }

    public function getPendingDeliveries()
    {
        $user = Auth::user();

        if (!$user->shop) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'User does not have a shop.',
            ], 404);
        }

        $shop_id = $user->shop->id;

        $products = Product::where('shop_id', $shop_id)->pluck('id')->toArray();

        if (empty($products)) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'No products found for this shop.',
            ], 404);
        }

        $orders = Order::whereHas('product', function ($query) use ($products) {
            $query->whereIn('product_id', $products);
        })
            ->where('status', 'Success')
            ->where('status_order', 'process')
            ->with(['product' => function ($query) {
                $query->withPivot('qty'); // Ambil qty dari tabel pivot
            }, 'address', 'transaction'])
            ->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'No pending deliveries found for this shop.',
            ], 404);
        }

        // Format ulang data order sesuai kebutuhan
        $formattedOrders = $orders->map(function ($order) {
            return $order->product->map(function ($product) use ($order) {
                return [
                    'id' => $order->id,
                    'product' => [
                        'name' => $product->name,
                        'price' => $product->price,
                        'thumbnail' => $product->thumbnail,
                    ],
                    'quantity' => $product->pivot->qty,
                    'customer' => $order->address->name,
                    'no_transaction' => $order->no_transaction,
                    'created_at' => $order->created_at->toIso8601String(),
                    'payment_status' => $order->status,
                    'shipping_status' => $order->status_order,
                ];
            });
        })->flatten(1);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'orders' => $formattedOrders,
        ], 200);
    }


    public function getLowStockProducts()
    {
        $user = Auth::user();

        if (!$user->shop) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'User does not have a shop.',
            ], 404);
        }

        $shop_id = $user->shop->id;

        $lowStockProducts = Product::with(['category', 'images'])
            ->where('shop_id', $shop_id)
            ->where('stock', '<=', 10)
            ->get();

        if ($lowStockProducts->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'No low stock products found for this shop.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'low_stock_products' => $lowStockProducts,
        ], 200);
    }

    public function getSoldProducts()
    {
        $user = Auth::user();

        if (!$user->shop) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'User does not have a shop.',
            ], 404);
        }

        $shop_id = $user->shop->id;

        $soldProducts = Product::with(['category', 'images', 'orders' => function ($query) {
            $query->where('status', 'DONE');
        }])
            ->where('shop_id', $shop_id)
            ->get()
            ->filter(function ($product) {
                return $product->orders->isNotEmpty();
            });

        $soldProductsCount = $soldProducts->count();

        if ($soldProductsCount === 0) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'No sold products found with status DONE for this shop.',
            ], 404);
        }


        return response()->json([
            'status' => 'success',
            'code' => 200,
            'sold_products_count' => $soldProductsCount,
        ], 200);
    }

    public function getTotalSoldItems()
    {
        $user = Auth::user();

        if (!$user->shop) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'User does not have a shop.',
            ], 404);
        }

        $shop_id = $user->shop->id;

        $soldCount = OrderService::whereHas('service', function ($query) use ($shop_id) {
            $query->where('shop_id', $shop_id);
        })
            ->where('status', 'DONE')
            ->count();

        $soldProducts = Product::with(['orders' => function ($query) {
            $query->where('status', 'DONE');
        }])
            ->where('shop_id', $shop_id)
            ->get()
            ->filter(function ($product) {
                return $product->orders->isNotEmpty();
            });

        $soldProductsCount = $soldProducts->count();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'sold_count_services' => $soldCount,
            'sold_count_products' => $soldProductsCount,
            'total_sold_count' => $soldCount + $soldProductsCount,
        ], 200);
    }

    public function getSalesDataByYear(Request $request)
    {
        $user = Auth::user();

        if (!$user->shop) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'User does not have a shop.',
            ], 404);
        }

        $shop_id = $user->shop->id;
        $year = $request->input('year', now()->year);

        $productSales = DB::table('order_product')
            ->join('product', 'order_product.product_id', '=', 'product.id')
            ->join('order', 'order_product.order_id', '=', 'order.id')
            ->selectRaw('MONTH(order.created_at) as month, COUNT(order_product.id) as productSales')
            ->where('product.shop_id', $shop_id)
            ->where('order.status', 'DONE')
            ->whereYear('order.created_at', $year)
            ->groupBy('month')
            ->pluck('productSales', 'month');

        $serviceSales = DB::table('order_service')
            ->join('service', 'order_service.service_id', '=', 'service.id')
            ->selectRaw('MONTH(order_service.created_at) as month, COUNT(order_service.id) as serviceSales')
            ->where('service.shop_id', $shop_id)
            ->where('order_service.status', 'DONE')
            ->whereYear('order_service.created_at', $year)
            ->groupBy('month')
            ->pluck('serviceSales', 'month');

        $sold_count_products = DB::table('order_product')
            ->join('product', 'order_product.product_id', '=', 'product.id')
            ->join('order', 'order_product.order_id', '=', 'order.id')
            ->where('product.shop_id', $shop_id)
            ->where('order.status', 'DONE')
            ->count();

        $sold_count_services = DB::table('order_service')
            ->join('service', 'order_service.service_id', '=', 'service.id')
            ->where('service.shop_id', $shop_id)
            ->where('order_service.status', 'DONE')
            ->count();

        $salesData = collect(range(1, 12))->map(function ($month) use ($productSales, $serviceSales) {
            return [
                'month' => date('M', mktime(0,  0, 0, $month, 1)),
                'Penjualan Produk' => $productSales->get($month, 0),
                'Penjualan Jasa' => $serviceSales->get($month, 0),
            ];
        });

        $total_sold_count = $sold_count_products + $sold_count_services;

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'summary' => [
                'year' => (int) $year,
                'salesData' => $salesData,
            ],
        ], 200);
    }
}
