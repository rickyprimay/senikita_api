<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;

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


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = Category::create([
            'name' => $request->name,
        ]);

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Category created successfully',
                'code' => 201,
                'data' => $category,
            ],
            201,
        );
    }

    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Category not found',
                    'code' => 404,
                ],
                404,
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Category retrieved successfully',
                'code' => 200,
                'data' => $category,
            ],
            200,
        );
    }

    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Category not found',
                    'code' => 404,
                ],
                404,
            );
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->update([
            'name' => $request->name,
        ]);

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Category updated successfully',
                'code' => 200,
                'data' => $category,
            ],
            200,
        );
    }

    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Category not found',
                    'code' => 404,
                ],
                404,
            );
        }

        // Hapus kategori
        $category->delete();

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Category deleted successfully',
                'code' => 200,
            ],
            200,
        );
    }
    public function search(Request $request)
    {
        $query = $request->input('name');

        if (!$query) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Name field is required in the request body',
                    'code' => 400,
                ],
                400,
            );
        }

        $categories = Category::where('name', 'LIKE', '%' . $query . '%')->get();

        if ($categories->isEmpty()) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'No categories found',
                    'code' => 404,
                ],
                404,
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Categories found',
                'code' => 200,
                'data' => $categories,
            ],
            200,
        );
    }
}
