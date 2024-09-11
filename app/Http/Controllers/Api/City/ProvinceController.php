<?php

namespace App\Http\Controllers\Api\City;

use App\Http\Controllers\Controller;
use App\Models\Province;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    public function index()
    {
        $provinces = Province::all();

        return response()->json([
            'status' => 'success',
            'message' => 'Provinces retrieved successfully',
            'provinces' => $provinces,
            'code' => 200,
        ], 200);
    }
}
