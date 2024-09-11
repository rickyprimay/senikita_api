<?php

namespace App\Http\Controllers\Api\User\Order;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\Request;
use Xendit\Configuration;
use Xendit\Invoice\CreateInvoiceRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class OrderController extends Controller
{
    public function __construct()
    {
        Configuration::setXenditKey(env('XENDIT_SECRET_KEY'));
    }
    public function checkOngkir(Request $request){
        try {
            $response = Http::withOptions(['verify' => false,])->withHeaders([
                'key' => env('RAJAONGKIR_API_KEY')
            ])->post('https://api.rajaongkir.com/starter/cost',[
                'origin'        => $request->origin,
                'destination'   => $request->destination,
                'weight'        => $request->weight,
                'courier'       => $request->courier
            ])
            ->json()['rajaongkir']['results'][0]['costs'];

            return response()->json($response);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'data'    => []
            ]);
        }
    }
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'nullable|integer',
            'service_id' => 'nullable|integer',
            'city_id' => 'required|integer',
            'qty' => 'required|integer',
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

        $priceService = null;
        if ($request->filled('service_id')) {
            $priceService = Service::find($request->input('service_id'))->price ?? null;
        }

        $totalPriceProduct = $priceProduct * $request->input('qty');
    }
}
