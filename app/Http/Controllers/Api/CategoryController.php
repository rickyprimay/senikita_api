<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('pag', 15);

        $search = $request->query('search');

        $query = Category::query();

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $categories = $query->paginate($perPage);

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Categories retrieved successfully',
                'code' => 200,
                'data' => $categories,
            ],
            200
        );
    }
}
