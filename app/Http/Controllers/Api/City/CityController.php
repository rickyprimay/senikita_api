<?php

namespace App\Http\Controllers\Api\City;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index()
    {
        $cities = City::all();

        return response()->json([
            'status' => 'success',
            'message' => 'Cities retrieved successfully',
            'cities' => $cities,
            'code' => 200,
        ], 200);
    }
}
