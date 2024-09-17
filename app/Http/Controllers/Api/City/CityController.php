<?php

namespace App\Http\Controllers\Api\City;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    public function getCitiesByProvince(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'province_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        $provinceId = $request->input('province_id');

        $cities = City::where('province_id', $provinceId)->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Cities retrieved successfully',
            'cities' => $cities,
            'code' => 200,
        ], 200);
    }
}
