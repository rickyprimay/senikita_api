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

        $services = Service::with('images')->paginate($perPage);

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

    public function show($id)
    {
        $service = Service::with(['images', 'ratings'])->find($id);

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Service not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Service retrieved successfully',
            'data' => $service,
        ], 200);
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
            200,
        );
    }
}
