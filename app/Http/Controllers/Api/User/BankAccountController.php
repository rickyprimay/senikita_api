<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BankAccountController extends Controller
{

    public function index()
    {
        $user = Auth::user();

        $bank_accounts = BankAccount::where('user_id', $user->id)->get();

        if ($bank_accounts->isEmpty()) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'No bank accounts found for this user',
                    'data' => [],
                ],
                404
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Bank accounts retrieved successfully',
                'code' => 200,
                'data' => $bank_accounts,
            ],
            200
        );
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ],
                400
            );
        }

        $user = Auth::user();

        $bank_account = BankAccount::create([
            'user_id' => $user->id,
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
        ]);

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Bank account created successfully',
                'code' => 201,
                'data' => $bank_account,
            ],
            201
        );
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ],
                400
            );
        }

        $user = Auth::user();

        $bank_account = BankAccount::where('id', $id)->where('user_id', $user->id)->first();

        if (!$bank_account) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Bank account not found',
                ],
                404
            );
        }

        $bank_account->update([
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
        ]);

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Bank account updated successfully',
                'code' => 200,
                'data' => $bank_account,
            ],
            200
        );
    }

    public function destroy($id)
    {
        $user = Auth::user();

        $bank_account = BankAccount::where('id', $id)->where('user_id', $user->id)->first();

        if (!$bank_account) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Bank account not found',
                ],
                404
            );
        }

        $bank_account->delete();

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Bank account deleted successfully',
                'code' => 200,
            ],
            200
        );
    }
}
