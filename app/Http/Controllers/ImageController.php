<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function fetchImage(Request $request)
    {
        if (empty($request->path)) {
            return response()->json([
                'status' => false,
                'code' => 400,
                'message' => "You need path"
            ], 400);
        }

        $path = 'public/' . $request->path;

        if (!Storage::exists($path)) {
            return response()->json([
                'status' => false,
                'code' => 404,
                'message' => "File Not Found"
            ], 404);
        }

        $file = Storage::get($path);
        $type = Storage::mimeType($path);

        return response($file, 200)
            ->header('Content-Type', $type)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET')
            ->header('Access-Control-Allow-Headers', 'Content-Type');
    }
}
