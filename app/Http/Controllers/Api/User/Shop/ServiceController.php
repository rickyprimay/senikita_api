<?php

namespace App\Http\Controllers\Api\User\Shop;

use App\Http\Controllers\Controller;
use App\Models\ImageService;
use App\Models\Order;
use App\Models\OrderService;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Xendit;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\InvoiceItem;
use Xendit\Configuration;

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
            'sold' => 0,
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
            'name',
            'price',
            'desc',
            'type',
            'status',
            'person_amount',
            'category_id'
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

    public function getOrderServiceByShop()
    {
        $user = Auth::user();

        $services = Service::where('shop_id', $user->shop->id)
            ->with(['orderService'])
            ->get();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Order service retrieved successfully',
            'data' => $services,
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

        $services = Service::where('shop_id', $shop_id)->pluck('id')->toArray();

        if (empty($services)) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'No Service found for this shop.',
            ], 404);
        }

        $orders = OrderService::whereHas('service', function ($query) use ($services) {
            $query->whereIn('service_id', $services);
        })->with('service', 'service.shop', 'city', 'province')->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'No orders service found for this shop.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'orders' => $orders,
        ], 200);
    }

    public function __construct()
    {
        Configuration::setXenditKey(env('XENDIT_SECRET_KEY'));
    }

    public function setStatusConfirmed($orderServiceId)
    {
        $orderService = OrderService::find($orderServiceId);

        if ($orderService->status == "confirmed") {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Order already confirmed',
            ], 400);
        }

        if (!$orderService) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Order not found',
            ], 404);
        }

        if ($orderService->status_order === 'confirmed') {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Order already confirmed',
            ], 400);
        }

        try {

            $price = $orderService->price;
            $feeAdmin = min($price * (5 / 100), 5000);
            $totalPrice = $price + $feeAdmin;

            $items = new InvoiceItem([
                'name' => $orderService->name,
                'price' => $price,
                'quantity' => 1
            ]);

            $fees = [
                [
                    'type' => 'Admin Fee',
                    'value' => $feeAdmin,
                ],
            ];

            $invoice = new CreateInvoiceRequest([
                'external_id' => $orderService->no_transaction,
                'amount' => $totalPrice,
                'invoice_duration' => 172800 / 2,
                'customer_email' => $orderService->email,
                'description' => 'Payment for ' . $orderService->activity_name,
                'fees' => $fees,
            ]);

            $apiInstance = new InvoiceApi();
            $generateInvoice = $apiInstance->createInvoice($invoice);
            $invoiceUrl = $generateInvoice['invoice_url'];

            $orderService->status_order = 'confirmed';
            $orderService->status = 'waiting for payment';
            $orderService->invoice_url = $invoiceUrl;
            $orderService->save();

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Order status updated to confirmed and invoice URL generated',
                'data' => [
                    'invoice_url' => $invoiceUrl,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to generate invoice URL',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function setStatusRejected($orderServiceId)
    {
        $orderService = OrderService::find($orderServiceId);

        if (!$orderService) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Order not found',
            ], 404);
        }

        if ($orderService->status_order === 'rejected') {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Order already rejected',
            ], 400);
        }

        try {
            $orderService->status_order = 'rejected';
            $orderService->save();

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Order status updated to rejected',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to update order status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function countSoldServices()
    {
        $user = Auth::user();

        if (!$user->shop) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'User does not have a shop.'
            ], 404);
        }

        $shop_id = $user->shop->id;

        $soldCount = OrderService::whereHas('service', function ($query) use ($shop_id) {
            $query->where('shop_id', $shop_id);
        })
            ->where('status', 'DONE')
            ->count();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'sold_count' => $soldCount,
        ], 200);
    }

    public function getPendingOrderServiceByShop()
    {
        $user = Auth::user();

        if (!$user->shop) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'User does not have a shop.'
            ], 404);
        }

        $pendingOrders = OrderService::whereHas('service', function ($query) use ($user) {
            $query->where('shop_id', $user->shop->id);
        })
            ->where('status', 'pending')
            ->where('status_order', 'pending')
            ->with(['service', 'user'])
            ->get();

        if ($pendingOrders->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'No pending orders found.'
            ], 404);
        }

        $formattedOrders = $pendingOrders->map(function ($order) {
            return [
                'id' => $order->id,
                'service' => [
                    'name' => $order->service->name,
                    'price' => $order->service->price,
                    'thumbnail' => $order->service->thumbnail,
                ],
                'customer' => $order->user->name,
                'no_transaction' => $order->no_transaction,
                'created_at' => $order->created_at->toISOString(),
                'payment_status' => $order->status,
                'shipping_status' => $order->status_order,
            ];
        });

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Pending orders retrieved successfully',
            'data' => $formattedOrders,
        ], 200);
    }

    public function getRevenueFromService() {
        $user = Auth::user();

        if (!$user->shop) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'User does not have a shop.'
            ], 404);
        }

        $shop_id = $user->shop->id;

        $services = Service::where('shop_id', $shop_id)->pluck('id')->toArray();

        if (empty($services)) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'No Service found for this shop.',
            ], 404);
        }

        $orders = OrderService::whereHas('service', function ($query) use ($services) {
            $query->whereIn('service_id', $services);
        })->where('status', 'DONE')->get();

        $revenue = $orders->sum('price');

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'revenue' => $revenue,
        ], 200);
    }

    public function getRevenueFromProduct()
    {
        $user = Auth::user();

        if (!$user->shop) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'User does not have a shop.'
            ], 404);
        }

        $shop_id = $user->shop->id;

        $products = Product::where('shop_id', $shop_id)->pluck('id')->toArray();

        if (empty($products)) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'No Product found for this shop.',
            ], 404);
        }

        $orders = Order::whereHas('product', function ($query) use ($products) {
            $query->whereIn('product_id', $products);
        })->where('status', 'DONE')->get();

        $revenue = $orders->sum('total_price');

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'revenue' => $revenue,
        ], 200);
    }
    public function getRevenue()
    {
        $serviceRevenue = $this->getRevenueFromService();
        $productRevenue = $this->getRevenueFromProduct();

        $totalRevenue = $serviceRevenue->original['revenue'] + $productRevenue->original['revenue'];

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'service_revenue' => $serviceRevenue->original['revenue'],
            'product_revenue' => $productRevenue->original['revenue'],
            'revenue' => $totalRevenue,
        ], 200);
    }
}
