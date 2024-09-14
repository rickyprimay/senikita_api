<?php

namespace App\Http\Controllers\Api\User\Service;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('pag', 15);

        $services = Service::paginate($perPage);

        return response()->json(
            [
                'status' => 'success',
                'code' => 200,
                'message' => 'Service retrieved successfully',
                'data' => $services,
            ],
            200,
        );
    }
    public function randomService()
    {
        $services = Service::inRandomOrder()->limit(5)->get();

        return response()->json(
            [
                'status' => 'success',
                'code' => 200,
                'message' => 'Random services retrieved successfully',
                'data' => $services,
            ],
            200
        );
    }
}
