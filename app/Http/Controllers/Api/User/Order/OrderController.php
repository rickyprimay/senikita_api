<?php

namespace App\Http\Controllers\Api\User\Order;

use App\Http\Controllers\Controller;
use App\Mail\InvoicePayment;
use App\Mail\ReminderPayments;
use App\Models\Address;
use App\Models\City;
use App\Models\Order;
use App\Models\OrderService;
use App\Models\Product;
use App\Models\Service;
use App\Models\Shop;
use Illuminate\Http\Request;
use Xendit\Configuration;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\InvoiceItem;
use Xendit\Invoice\InvoiceApi;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct()
    {
        Configuration::setXenditKey(env('XENDIT_SECRET_KEY'));
    }

    public function checkOngkir(Request $request)
    {
        try {
            $response = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'key' => env('RAJAONGKIR_API_KEY'),
                ])
                ->post('https://api.rajaongkir.com/starter/cost', [
                    'origin' => $request->origin,
                    'destination' => $request->destination,
                    'weight' => $request->weight,
                    'courier' => $request->courier,
                ])
                ->json();

            // dd(env('RAJAONGKIR_API_KEY'));

            if (isset($response['rajaongkir']['status']['code']) && $response['rajaongkir']['status']['code'] != 200) {
                $statusCode = $response['rajaongkir']['status']['code'];
                $statusDescription = $response['rajaongkir']['status']['description'];

                if ($statusCode == 403 || str_contains($statusDescription, 'expired')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'API key has expired. Please contact the administrator to update the Raja Ongkir API key.',
                        'data' => [],
                    ], 403);
                }

                return response()->json([
                    'success' => false,
                    'message' => $statusDescription,
                    'data' => [],
                ], $statusCode);
            }

            $costs = $response['rajaongkir']['results'][0]['costs'];
            return response()->json([
                'success' => true,
                'message' => 'Shipping cost retrieved successfully',
                'data' => $costs,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching the shipping cost: ' . $th->getMessage(),
                'data' => [],
            ], 500);
        }
    }


    public function create(Request $request)
    {
        // dd(env('XENDIT_SECRET_KEY'));
        $validator = Validator::make($request->all(), [
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer|exists:product,id',
            'qtys' => 'required|array',
            'qtys.*' => 'integer|min:1',
            'courier' => 'required|string',
            'service' => 'required|string',
            'address_id' => 'required|exists:address,id',
            'note' => 'nullable|string',
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
        $productIds = $request->input('product_ids');
        $qtys = $request->input('qtys');

        $address = Address::where('user_id', $user->id)->find($request->input('address_id'));

        if (!$address) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Address not found or does not belong to this user.',
            ], 400);
        }

        $totalPriceProduct = 0;
        $items = [];
        $fees = [];

        foreach ($productIds as $index => $productId) {
            $product = Product::find($productId);
            if (!$product) {
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 400,
                        'message' => "Product with ID $productId not found.",
                    ],
                    400,
                );
            }

            $priceProduct = $product->price;
            $qty = $qtys[$index] ?? 1;
            $totalPriceProduct += $priceProduct * $qty;

            $items[] = new InvoiceItem([
                'name' => $product->name,
                'price' => $priceProduct,
                'quantity' => $qty,
            ]);
        }

        $weight = 1000;
        $courier = $request->input('courier');
        $selectedService = $request->input('service');

        $shop = $product ? $product->shop : null;
        $origin = $shop ? $shop->city_id : null;

        if (!$origin) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Origin city ID is missing for the selected product.',
                ],
                400,
            );
        }

        $ongkirResponse = $this->checkOngkir(
            new Request([
                'origin' => $origin,
                'destination' => $address->city_id,
                'weight' => $weight,
                'courier' => $courier,
            ]),
        );

        $ongkirJson = $ongkirResponse->getData(true); 
        $ongkirData = $ongkirJson['data'] ?? [];

        // dd($ongkirData);
        $ongkirCost = 0;
        $estimation = "4-7";

        // dd($selectedService);

        foreach ($ongkirData as $service) {
            if ($service['service'] === $selectedService) {
                $ongkirCost = $service['cost'][0]['value'] ?? 0;
                $estimation = $service['cost'][0]['etd'] ?? "4-7";
                break;
            }
        }

        $no_transaction = 'Inv-' . rand();
        $totalPrices = $totalPriceProduct + $ongkirCost;
        $feeAdmin = min($totalPrices * (5 / 100), 5000);
        $totalPrice = $totalPrices + $feeAdmin;

        $fees = [
            [
                'type' => 'Ongkir Fee',
                'value' => $ongkirCost,
            ],
            [
                'type' => 'Admin Fee',
                'value' => $feeAdmin,
            ],
        ];

        $invoice = new CreateInvoiceRequest([
            'external_id' => $no_transaction,
            'amount' => $totalPrice,
            'invoice_duration' => 86400,
            'customer_email' => $user->email,
            'items' => $items,
            'fees' => $fees,
        ]);

        try {
            $apiInstance = new InvoiceApi();
            $generateInvoice = $apiInstance->createInvoice($invoice);
            $invoiceUrl = $generateInvoice['invoice_url'];
            $products = Product::whereIn('id', $productIds)->get();

            $order = Order::create([
                'user_id' => $user->id,
                'no_transaction' => $no_transaction,
                'email' => $user->email,
                'address_id' => $request->input('address_id'),
                'price' => $totalPriceProduct,
                'ongkir' => $ongkirCost,
                'total_price' => $totalPrice,
                'invoice_url' => $invoiceUrl,
                'courier' => $courier,
                'service' => $selectedService,
                'estimation' => $estimation,
                'status' => 'pending',
                'status_order' => 'waiting',
                'note' => $request->input('note'),
            ]);

            foreach ($productIds as $index => $productId) {
                $order->product()->attach($productId, ['qty' => $qtys[$index]]);
            }

            DB::table('transaction')->insert([
                'order_id' => $order->id,
                'payment_status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $details = [
                'name' => $user->name,
                'price' => $totalPrice,
                'invoice_number' => $no_transaction,
                'product_names' => implode(', ', array_map(fn($id) => Product::find($id)->name ?? 'Unknown', $productIds)),
                'due_date' => '48 Hours',
                'invoice_url' => $invoiceUrl,
                'sender_name' => 'SeniKita Team',
            ];

            Mail::to($user->email)->send(new ReminderPayments($details));

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Order created successfully',
                'data' => [
                    'order' => $order,
                    'invoice_url' => $invoiceUrl,
                    'address' => $address,
                    'product' => $products,
                ],

            ]);
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 500,
                    'message' => 'Failed to create invoice',
                    'errors' => $th->getMessage(),
                ],
                500,
            );
        }
    }


    public function notificationCallback(Request $request)
    {
        $getToken = $request->headers->get('x-callback-token');
        $callbackToken = env('XENDIT_CALLBACK_TOKEN');

        try {
            if (!$callbackToken || $callbackToken !== $getToken) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Invalid callback token',
                    ],
                    403,
                );
            }

            $order = Order::where('no_transaction', $request->external_id)->first();
            $orderService = OrderService::where('no_transaction', $request->external_id)->first();

            if ($order) {
                if ($request->status == 'PAID') {
                    $order->status = 'Success';
                    $order->status_order = 'process';
                    $order->save();

                    DB::table('transaction')
                        ->where('order_id', $order->id)
                        ->update([
                            'payment_status' => 'PAID',
                            'payment_date' => now(),
                            'updated_at' => now(),
                        ]);

                    $products = $order->product()->get();

                    foreach ($products as $product) {
                        $product->sold += $product->pivot->qty;
                        $product->save();
                    }

                    $productItems = $products->map(function ($product) {
                        // dd('error');
                        return [
                            'name' => $product->name,
                            'price' => $product->price,
                            'qty' => $product->pivot->qty,
                        ];
                    })->toArray();

                    $feeAdmin = min($order->price * (5 / 100), 5000);
                    $ongkirFee = $order->ongkir;

                    $details = [
                        'name' => $order->name,
                        'ongkir' => $ongkirFee,
                        'price' => $order->price,
                        'total_price' => $order->total_price,
                        'invoice_number' => $order->no_transaction,
                        'due_date' => '48 Hours',
                        'invoice_url' => $order->invoice_url,
                        'sender_name' => 'SeniKita Team',
                        'products' => $productItems,
                        'feeAdmin' => $feeAdmin
                    ];

                    Mail::to('rickyprima30@gmail.com')->send(new InvoicePayment($details));
                } else {
                    $order->status = 'Failed';
                    $order->save();

                    DB::table('transaction')
                        ->where('order_id', $order->id)
                        ->update([
                            'payment_status' => 'FAILED',
                            'updated_at' => now(),
                        ]);
                }
            }

            if ($orderService) {
                if ($request->status === 'PAID') {
                    $orderService->status = 'Success';
                    $orderService->save();

                    DB::table('transaction_service')
                        ->where('order_service_id', $orderService->id)
                        ->update([
                            'payment_status' => 'PAID',
                            'payment_date' => now(),
                            'updated_at' => now(),
                        ]);
                } else {
                    $orderService->status = 'Failed';
                    $orderService->save();
                }
            }

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Callback processed',
                ],
                200,
            );
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Failed to process callback',
                    'error' => $th->getMessage()
                ],
                500,
            );
        }
    }

    public function transactionHistory()
    {
        try {
            $user = Auth::user();

            $orders = Order::where('user_id', $user->id)
                ->with(['address', 'product', 'transaction', 'product.shop'])
                ->orderBy('created_at', 'desc')
                ->get();

            if ($orders->isEmpty()) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'No transaction history found',
                    ],
                    404,
                );
            }

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Transaction history retrieved successfully',
                    'data' => $orders,
                ],
                200,
            );
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => $th->getMessage(),
                ],
                500,
            );
        }
    }

    public function transactionDetail($orderId)
    {
        try {
            $user = Auth::user();

            $order = Order::where('id', $orderId)
                ->where('user_id', $user->id)
                ->with(['address', 'product', 'transaction', 'address.city', 'address.province'])
                ->first();

            if (!$order) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Order not found',
                    ],
                    404,
                );
            }

            $productDetails = $order->product->map(function ($product) {
                return [
                    'name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $product->pivot->qty,
                ];
            });

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Transaction details retrieved successfully',
                'data' => [
                    'order' => $order,
                ],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Failed to retrieve transaction details',
                    'error' => $th->getMessage(),
                ],
                500,
            );
        }
    }

    public function updatePaymentStatus($orderId)
    {

        DB::beginTransaction();

        try {
            $order = Order::find($orderId);

            if (!$order) {
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 404,
                        'message' => 'Order not found',
                    ],
                    404,
                );
            }

            if ($order->status_order === 'DONE' || $order->status === 'DONE') {
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'Order already marked as done',
                    ],
                    400,
                );
            }

            $order->status_order = 'DONE';
            $order->status = 'DONE';
            $order->save();

            DB::table('transaction')
                ->where('order_id', $orderId)
                ->update([
                    'payment_status' => 'DONE',
                    'updated_at' => now(),
                ]);

            $products = DB::table('product')->join('order_product', 'product.id', '=', 'order_product.product_id')->where('order_product.order_id', $orderId)->select('product.id as product_id', 'product.price', 'order_product.qty')->get();

            foreach ($products as $product) {
                $shop = Shop::whereHas('products', function ($query) use ($product) {
                    $query->where('id', $product->product_id);
                })->first();

                if ($shop) {
                    $shop->balance += $product->price * $product->qty;
                    $shop->save();
                }
            }

            DB::commit();

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Payment status updated and shop balance adjusted successfully',
                ],
                200,
            );
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Failed to update payment status and adjust shop balance',
                    'error' => $th->getMessage(),
                ],
                500,
            );
        }
    }
    public function getDataOrderProductByStatus(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,Success,failed,DONE,delivered',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $status = $request->status;

            if ($status === 'delivered') {
                $orders = Order::where('user_id', $user->id)
                    ->where('status_order', 'delivered')
                    ->with(['product', 'transaction', 'product.shop'])
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else if ($status === 'Success') {
                $orders = Order::where('user_id', $user->id)
                    ->where('status_order', 'process')
                    ->with(['product', 'transaction', 'product.shop'])
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else {
                $orders = Order::where('user_id', $user->id)
                    ->where('status', $status)
                    ->with(['product', 'transaction', 'product.shop'])
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            if ($orders->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No orders found with the specified status',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Orders retrieved successfully',
                'data' => $orders,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve orders',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function getOrderProductCountByStatus()
    {
        try {
            $user = Auth::user();

            $statuses = ['pending', 'Success', 'failed', 'DONE', 'delivered'];

            $counts = [];
            foreach ($statuses as $status) {
                $query = Order::where('user_id', $user->id);

                if ($status === 'delivered') {
                    $query->where('status_order', 'delivered');
                } elseif ($status === 'Success') {
                    $query->where('status_order', 'process');
                } else {
                    $query->where('status', $status);
                }

                $counts[$status] = $query->count();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Order counts retrieved successfully',
                'data' => $counts,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve order counts',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}
