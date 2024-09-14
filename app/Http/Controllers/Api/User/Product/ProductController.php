<?php

namespace App\Http\Controllers\Api\User\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('pag', 15);

        $products = Product::paginate($perPage);

        return response()->json(
            [
                'status' => 'success',
                'code' => 200,
                'message' => 'Products retrieved successfully',
                'data' => $products,
            ],
            200,
        );
    }
    public function randomProducts()
    {
        $products = Product::inRandomOrder()->limit(5)->get();

        return response()->json(
            [
                'status' => 'success',
                'code' => 200,
                'message' => 'Random products retrieved successfully',
                'data' => $products,
            ],
            200
        );
    }
}
