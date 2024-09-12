<?php

namespace App\Http\Controllers\Api\User\Order;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\Request;
use Xendit\Configuration;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\InvoiceItem;
use Xendit\Invoice\InvoiceApi;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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
                ->json()['rajaongkir']['results'][0]['costs'];

            // Log response data for debugging
            Log::info('Ongkir Response Data:', ['response' => $response]);

            return response()->json($response);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'data' => [],
            ]);
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'nullable|integer|exists:product,id',
            'city_id' => 'required|integer|exists:cities,id',
            'province_id' => 'required|integer|exists:provinces,id',
            'address' => 'required|string',
            'name' => 'required|string',
            'qty' => 'required|integer|min:1',
            'courier' => 'required|string',
            'service' => 'required|string',
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
        $priceProduct = null;
        if ($request->filled('product_id')) {
            $priceProduct = Product::find($request->input('product_id'))->price ?? null;
        }

        $weight = 1000;
        $courier = $request->input('courier');
        $selectedService = $request->input('service');

        $product = Product::find($request->input('product_id'));
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
                'destination' => $request->input('city_id'),
                'weight' => $weight,
                'courier' => $courier,
            ]),
        );

        $ongkirData = $ongkirResponse->original;

        Log::info('Ongkir Data:', ['ongkirData' => $ongkirData]);

        $ongkirCost = 0;
        $estimation = null;

        foreach ($ongkirData as $service) {
            if ($service['service'] === $selectedService) {
                $ongkirCost = $service['cost'][0]['value'] ?? 0;
                $estimation = $service['cost'][0]['etd'] ?? null; 
                break;
            }
        }

        $no_transaction = 'Inv-' . (string) Str::uuid();

        Log::info('Selected Ongkir Cost:', ['ongkirCost' => $ongkirCost]);

        $totalPriceProduct = ($priceProduct ?? 0) * $request->input('qty');
        $totalPrice = $totalPriceProduct + $ongkirCost;

        Log::info('Total Price Calculation:', [
            'totalPriceProduct' => $totalPriceProduct,
            'ongkirCost' => $ongkirCost,
            'totalPrice' => $totalPrice,
        ]);

        $product = Product::find($request->input('product_id'));
        $productName = $product ? $product->name : 'Unknown Product';

        $fees = [
            [
                'type' => 'Ongkir Fee',
                'value' => $ongkirCost,
            ],
        ];


        $items = new InvoiceItem([
            'name' => $productName,
            'price' => $priceProduct,
            'quantity' => $request->input('qty'),
        ]);

        $invoice = new CreateInvoiceRequest([
            'external_id' => $no_transaction,
            'amount' => $totalPrice,
            'invoice_duration' => 172800,
            'customer_email' => $user->email,
            'items' => [$items],
            'fees' => $fees,
        ]);

        try {
            $apiInstance = new InvoiceApi();
            $generateInvoice = $apiInstance->createInvoice($invoice);
            $invoiceUrl = $generateInvoice['invoice_url'];

            $city = City::find($request->input('city_id'));
            $province_id = $city ? $city->province_id : null;

            $order = Order::create([
                'user_id' => $user->id,
                'product_id' => $request->input('product_id'),
                'city_id' => $request->input('city_id'),
                'no_transaction' => $no_transaction,
                'email' => $user->email,
                'name' => $request->input('name'),
                'address' => $request->input('address'),
                'province_id' => $province_id,
                'qty' => $request->input('qty'),
                'price' => $priceProduct,
                'ongkir' => $ongkirCost,
                'total_price' => $totalPrice,
                'invoice_url' => $invoiceUrl,
                'courir' => $request->input('courier'),
                'service' => $selectedService,
                'estimation' => $estimation,
                'status' => 'pending'
            ]);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Order created successfully',
                'data' => [
                    'order' => $order,
                    'invoice_url' => $invoiceUrl,
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
}
