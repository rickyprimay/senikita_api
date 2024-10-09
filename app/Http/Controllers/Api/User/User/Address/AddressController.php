<?php

namespace App\Http\Controllers\Api\User\User\Address;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    public function index()
    {
        $user = Auth::user()->id;

        $addresses = Address::where('user_id', $user)
            ->with(['city', 'province'])
            ->get();

        if ($addresses->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Address is empty',
                'data' => [],
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'data' => $addresses,
        ], 200);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'label_address'  => 'required|string|max:255',
            'name'           => 'required|string|max:255',
            'phone'          => 'required|string|max:15',
            'address_detail' => 'required|string',
            'province_id'    => 'required|integer',
            'city_id'        => 'required|integer',
            'postal_code'    => 'required|string|max:10',
            'note'           => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ],
                400,
            );
        }

        $address = Address::create([
            'user_id'        => $user->id,
            'label_address'  => $request->label_address,
            'name'           => $request->name,
            'phone'          => $request->phone,
            'address_detail' => $request->address_detail,
            'province_id'    => $request->province_id,
            'city_id'        => $request->city_id,
            'postal_code'    => $request->postal_code,
            'note'           => $request->note,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Address created successfully',
            'data' => $address,
        ], 201);
    }

    public function show($id)
    {
        $address = Address::where('user_id', Auth::id())
            ->with(['city', 'province'])
            ->find($id);

        if (!$address) {
            return response()->json([
                'status' => 'error',
                'message' => 'Address not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $address,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $address = Address::where('user_id', $user->id)->find($id);

        if (!$address) {
            return response()->json([
                'status' => 'error',
                'message' => 'Address not found',
            ], 404);
        }

        $validatedData = $request->validate([
            'label_address'  => 'required|string|max:255',
            'name'           => 'required|string|max:255',
            'phone'          => 'required|string|max:15',
            'address_detail' => 'required|string',
            'province_id'    => 'required|integer',
            'city_id'        => 'required|integer',
            'postal_code'    => 'required|string|max:10',
            'note'           => 'nullable|string|max:500',
        ]);

        $address->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Address updated successfully',
            'data' => $address,
        ], 200);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $address = Address::where('user_id', $user->id)->find($id);

        if (!$address) {
            return response()->json([
                'status' => 'error',
                'message' => 'Address not found',
            ], 404);
        }

        $address->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Address deleted successfully',
        ], 200);
    }
}
