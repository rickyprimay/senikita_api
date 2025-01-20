<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArtProvinceDetail;
use Illuminate\Http\Request;

class ArtProvinceDetailController extends Controller
{
    public function index()
    {
        $artProvinceDetails = ArtProvinceDetail::all();
        return response()->json([
            'status' => 'success',
            'data' => $artProvinceDetails
        ]);
    }

    public function getByArtProvince($id)
    {
        $artProvinceDetails = ArtProvinceDetail::where('art_province_id', $id)->get();
        return response()->json([
            'status' => 'success',
            'data' => $artProvinceDetails
        ]);
    }

    public function show($id)
    {
        $artProvinceDetail = ArtProvinceDetail::find($id);
        return response()->json([
            'status' => 'success',
            'data' => $artProvinceDetail
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'image' => 'required',
            'type' => 'required',
            'art_province_id' => 'required'
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/images', $fileName);
            // $request->merge(['image' => $fileName]);
            $fullPath = 'storage/images/' . $fileName;

            // $path = $request->file('image')->store('province_details', 'public');
            // $fullPath = asset('storage/' . $path);
            // $request->merge(['image' => $fullPath]);

        }

        $data = $request->all();
        $data['image'] = $fullPath;


        $artProvinceDetail = ArtProvinceDetail::create($data);
        return response()->json([
            'status' => 'success',
            'data' => $artProvinceDetail
        ]);
    }


    public function delete($id)
    {
        $artProvinceDetail = ArtProvinceDetail::find($id);

        if (!$artProvinceDetail) {
            return response()->json([
                'status' => 'error',
                'message' => 'Art Province Detail not found'
            ], 404);
        }

        $artProvinceDetail->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Art Province Detail deleted'
        ]);
    }
}
