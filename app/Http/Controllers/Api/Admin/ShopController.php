<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('pag', 15);
        $search = $request->query('search');
        $query = Shop::query();

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $shop = $query->paginate($perPage);

        return response()->json(
            [
                'status' => 'success',
                'message' => 'All shop retrieved successfully',
                'code' => 200,
                'data' => $shop,
            ],
            200,
        );
    }
    public function verificationShop(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:0,1,2',
        ]);

        $shop = Shop::find($id);

        if (!$shop) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Shop not found',
            ], 404);
        }

        $shop->status = $validated['status'];
        $shop->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Shop status updated successfully',
            'code' => 200,
            'data' => $shop,
        ], 200);
    }
}
