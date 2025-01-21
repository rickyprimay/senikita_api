<?php

namespace App\Http\Controllers\Api\User\Shop;

use App\Http\Controllers\Controller;
use App\Models\LogBalance;
use App\Models\RatingProduct;
use App\Models\RatingService;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{
    public function show($id)
    {
        $shop = Shop::with(['services', 'products', 'categories', 'products.category', 'services.category', 'products.shop', 'services.shop', 'products.shop.city', 'services.shop.city', 'products.ratings', 'services.ratings'])->find($id);
        if (!$shop) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Shop not found.'
            ], 404);
        }
        $shop->region = $shop->city->name . ', ' . $shop->city->province->name;
        $services = $shop->services->map(function ($service) {
            $ratings = RatingService::where('service_id', $service->id)->get();
            $service->average_rating = $ratings->avg('rating') ?? 0;
            $service->rating_count = $ratings->count();
            // $service->region = $service->shop->city->name . ', ' . $service->shop->city->province->name;
            return $service;
        });

        $products = $shop->products->map(function ($product) {
            $ratings = RatingProduct::where('product_id', $product->id)->get();
            $product->average_rating = $ratings->avg('rating') ?? 0;
            $product->rating_count = $ratings->count();
            // $product->region = $product->shop->city->name . ', ' . $product->shop->city->province->name;
            return $product;
        });
        $sold = 0;
        $rating = 0;

        foreach ($services as $service) {
            $sold += $service->sold;
            $rating += $service->average_rating;
        }

        foreach ($products as $product) {
            $sold += $product->sold;
            $rating += $product->average_rating;
        }

        $shop->rating = $rating / ($services->count() + $products->count());

        $shop->sold = $sold;

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Shop retrieved successfully',
            'data' => $shop,
        ], 200);
    }
    public function cashOutBalance(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'balance' => 'required|numeric',
            'bank_account_id' => 'required|exists:bank_accounts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        if ($request->balance < 100000) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Minimum balance to cash out is 100000.'
            ], 400);
        }

        if ($request->balance > Auth::user()->shop->balance) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Insufficient balance.'
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

        $shop = $user->shop;

        $shop->balance -= $request->balance;
        $shop->save();

        $logBalance = LogBalance::create([
            'user_id' => $user->id,
            'bank_account_id' => $request->bank_account_id,
            'message' => "Cashout of {$request->balance} from shop balance.",
        ]);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Balance cashed out successfully',
            'balance_cash_out' => $request->balance,
            'balance' => $shop->balance,
            'log' => $logBalance,
        ], 200);
    }

    public function getLogBalanceByUser()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'code' => 401,
                'message' => 'Unauthorized. Please log in.',
            ], 401);
        }

        $logBalances = LogBalance::where('user_id', $user->id)
            ->with('bankAccount') 
            ->orderBy('created_at', 'desc') 
            ->get();

        if ($logBalances->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'No log balances found for this user.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Log balances retrieved successfully',
            'data' => $logBalances,
        ], 200);
    }


    public function getShopByLogin()
    {
        $user = Auth::user();
        if (!$user->shop) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'User does not have a shop.'
            ], 404);
        }
        $shop = Shop::with('categories', 'user')->find($user->shop->id);
        $shop->region = $shop->city->name . ', ' . $shop->city->province->name;

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Shop retrieved successfully',
            'data' => $shop,
        ], 200);
    }
}
