<?php

namespace App\Http\Controllers\Api\User\Order;

use App\Http\Controllers\Controller;
use App\Mail\ReminderPayments;
use App\Models\OrderService;
use App\Models\Service;
use App\Models\TransactionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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
            'activity_name' => 'required|string|max:255',
            'activity_time' => 'required',
            'phone' => 'required|string|max:15',
            'activity_date' => 'required|date_format:d/m/Y',
            'attendee' => 'required|integer',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
            'description' => 'nullable|string',
            'address' => 'required|string|max:255',
            'optional_document.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5000',
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

        $service = Service::find($request->service_id);
            if (!$service) {
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 404,
                        'message' => 'Service not found',
                    ],
                    404
                );
            }

        $no_transaction = 'Inv-' . rand();
        $price = $service->price;
        $feeAdmin = min($price * (5 / 100), 5000);
        $totalPrice = $price + $feeAdmin;
        $user = Auth::user();
        $items = new InvoiceItem([
            'name' => $service->name,
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
            'external_id' => $no_transaction,
            'amount' => $totalPrice,
            'invoice_duration' => 172800,
            'customer_email' => $user->email,
            'items' => [$items],
            'fees' => $fees
        ]);

        try {
            TransactionService::create([
                'service_id' => $service->id,
            ]);

            $apiInstance = new InvoiceApi();
            $generateInvoice = $apiInstance->createInvoice($invoice);
            $invoiceUrl = $generateInvoice['invoice_url'];

            $optionalDocuments = [];

            if ($request->hasFile('optional_document')) {
                foreach ($request->file('optional_document') as $file) {
                    $path = $file->store('optional_documents', 'public');
                    $optionalDocuments[] = asset('storage/' . $path);
                }
            }

            $activityDate = Carbon::createFromFormat('d/m/Y', $request->activity_date)->format('Y-m-d');

            $order = OrderService::create([
                'user_id' => $user->id,
                'service_id' => $request->service_id,
                'name' => Auth::user()->name,
                'phone' => $request->phone,
                'activity_name' => $request->activity_name,
                'activity_date' => $activityDate,
                'activity_time' => $request->activity_time,
                'attendee' => $request->attendee,
                'province_id' => $request->province_id,
                'city_id' => $request->city_id,
                'description' => $request->description,
                'email' => $user->email,
                'no_transaction' => $no_transaction,
                'price' => $price,
                'address' => $request->address,
                'optional_document' => json_encode($optionalDocuments),
                'invoice_url' => $invoiceUrl,
            ]);

            $details = [
                'name' => $request->input('name'),
                'price' => $totalPrice,
                'invoice_number' => $no_transaction,
                'product_names' => $service->name,
                'due_date' => '48 Hours',
                'invoice_url' => $invoiceUrl,
                'sender_name' => 'SeniKita Team',
            ];

            Mail::to($user->email)->send(new ReminderPayments($details));

            return response()->json(
                [
                    'status' => 'success',
                    'code' => 201,
                    'message' => 'Order created successfully',
                    'data' => [
                        'order' => $order,
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

    public function transactionHistory()
    {
        try {
            $user = Auth::user();

            $orders = OrderService::where('user_id', $user->id)
                ->with(['service', 'transaction'])
                ->orderBy('created_at', 'desc')
                ->get();

            if ($orders->isEmpty()) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'No transaction history service found',
                    ],
                    404,
                );
            }

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Transaction history service retrieved successfully',
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
}
