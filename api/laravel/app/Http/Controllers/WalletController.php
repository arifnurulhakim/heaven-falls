<?php

namespace App\Http\Controllers;

use App\Models\HcLevel;
use App\Models\Player;
use App\Models\HrLevelPlayer;
use App\Models\HdWallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    public function index()
    {
        try {
            $offset = request()->get('offset', 0);
            $limit = request()->get('limit', 10);
            $search = request()->get('search', '');
            $getOrder = request()->get('order', '');
            $defaultOrder = $getOrder ? $getOrder : "id ASC";
            $orderMappings = [
                'idASC' => 'id ASC',
                'idDESC' => 'id DESC',
                'nameASC' => 'name ASC',
                'nameDESC' => 'name DESC',
                'descASC' => 'desc ASC',
                'descDESC' => 'desc DESC',
                'hudASC' => 'hud ASC',
                'hudDESC' => 'hud DESC',
                'level_reachASC' => 'level_reach ASC',
                'level_reachDESC' => 'level_reach DESC',
            ];

            $order = $orderMappings[$getOrder] ?? $defaultOrder;
            $validOrderValues = implode(',', array_keys($orderMappings));
            $rules = [
                'offset' => 'integer|min:0',
                'limit' => 'integer|min:1',
                'order' => "in:$validOrderValues",
            ];

            $validator = Validator::make([
                'offset' => $offset,
                'limit' => $limit,
                'order' => $getOrder,
            ], $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid input parameters',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $levels = HcLevel::orderByRaw($order);
            $total_data = $levels->count();
            if ($search !== '') {
                $levels->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%$search%")
                        ->orWhere('desc', 'like', "%$search%")
                        ->orWhere('hud', 'like', "%$search%")
                        ->orWhere('level_reach', 'like', "%$search%");
                });
            }

            $levels = $levels->offset($offset)->limit($limit)->get();
            return response()->json([
                'status' => 'success',
                'offset' => $offset,
                'limit' => $limit,
                'order' => $getOrder,
                'search' => $search,
                'total_data' => $total_data,
                'data' => $levels,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'desc' => 'nullable|string',
                'hud' => 'nullable|string',
                'level_reach' => 'required|integer',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation error', 'error_code' => 'VALIDATION_ERROR', 'errors' => $validator->errors()], 422);
            }

            $levelData = $request->all();

            $level = HcLevel::create($levelData);

            return response()->json(['status' => 'success', 'data' => $level], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function show($id)
    {
        try {
            $level = HcLevel::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $level,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $level = HcLevel::find($id);

            if (!$level) {
                return response()->json(['status' => 'error', 'message' => 'Level not found', 'error_code' => 'NOT_FOUND'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'desc' => 'nullable|string',
                'hud' => 'nullable|string',
                'level_reach' => 'nullable|integer',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $levelData = $request->all();

            $level->update($levelData);

            return response()->json(['status' => 'success', 'data' => $level], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $level = HcLevel::findOrFail($id);
            $level->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'HcLevel deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function topup(Request $request){
        try {
            $validator = Validator::make($request->all(), [

                'currency' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation error', 'error_code' => 'VALIDATION_ERROR', 'errors' => $validator->errors()], 422);
            }

            $user = Auth::user();
            $wallet = HdWallet::create([
                'player_id' => $user->id,
                'amount' => $request->currency,
                'currency_id' => 1,
                'category'=> 'reward',
                'label'=>'reward',
                'created_by'=> $user->id,
                'modified_by'=> $user->id,
                ]);
            $data =[
                $level,
                $wallet
            ];



            return response()->json(['status' => 'success', 'data' => $data], 201);
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }
}
