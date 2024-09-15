<?php

namespace App\Http\Controllers\Api\User\Order;

use App\Http\Controllers\Controller;
use App\Models\OrderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Xendit\Configuration;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\InvoiceItem;
use Xendit\Invoice\InvoiceApi;

class OrderServiceController extends Controller
{
    public function __construct()
    {
        Configuration::setXenditKey(env('XENDIT_SECRET_KEY'));
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:service,id',
            'name' => 'required|string|max:255',
            'price' => 'required|integer',
            'address' => 'required|string|max:255',
            'optional_document' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ],
                400
            );
        }

        $no_transaction = 'Inv-' . rand();
        $user = Auth::user();

        try {
            $invoiceApi = new InvoiceApi();
            $invoiceItems = [
                new InvoiceItem([
                    'name' => 'Service Payment',
                    'quantity' => 1,
                    'price' => $request->price,
                ]),
            ];

            $invoiceRequest = new CreateInvoiceRequest([
                'external_id' => $no_transaction,
                'payer_email' => $request->email,
                'description' => 'Payment for service',
                'amount' => $request->price,
                'items' => $invoiceItems,
            ]);

            $invoice = $invoiceApi->createInvoice($invoiceRequest);

            $order = OrderService::create([
                'user_id' => $user->id,
                'service_id' => $request->service_id,
                'name' => $request->name,
                'email' => $user->email,
                'no_transaction' => $no_transaction,
                'price' => $request->price,
                'address' => $request->address,
                'optional_document' => $request->optional_document,
                'invoice_url' => $invoice->getInvoiceUrl(),
            ]);

            return response()->json(
                [
                    'status' => 'success',
                    'code' => 201,
                    'message' => 'Order created successfully',
                    'data' => [
                        'order' => $order,
                        'invoice_url' => $invoice->getInvoiceUrl(),
                    ],
                ],
                201
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 500,
                    'message' => 'Failed to create order and invoice',
                    'error' => $e->getMessage(),
                ],
                500
            );
        }
    }
}
