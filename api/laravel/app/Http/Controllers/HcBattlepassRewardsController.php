<?php

namespace App\Http\Controllers;

use App\Models\HcBattlepassReward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HcBattlepassRewardsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'name_item', 'category', 'value'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HcBattlepassReward::with(['skin']);

            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('name_item', 'like', "%{$globalFilter}%")
                      ->orWhere('category', 'like', "%{$globalFilter}%")
                      ->orWhere('value', 'like', "%{$globalFilter}%");
                });
            }

            $rewards = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'current_page' => $rewards->currentPage(),
                'last_page' => $rewards->lastPage(),
                'next_page' => $rewards->currentPage() < $rewards->lastPage() ? $rewards->currentPage() + 1 : null,
                'prev_page' => $rewards->currentPage() > 1 ? $rewards->currentPage() - 1 : null,
                'next_page_url' => $rewards->nextPageUrl(),
                'prev_page_url' => $rewards->previousPageUrl(),
                'per_page' => $rewards->perPage(),
                'total' => $rewards->total(),
                'data' => $rewards->items(),
                'params' => [
                    'pageSize' => $perPage,
                    'sortField' => $sortField,
                    'sortDirection' => $sortDirection,
                    'globalFilter' => $globalFilter,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name_item' => 'required|string|max:255',
                'category' => 'required|string|in:item,coin',
                'skin_id' => 'nullable|integer|exists:hr_skin_characters,id',
                'type' => 'required|string|in:free,premium',
                'value' => 'required|integer',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors()
                ], 422);
            }

            $rewardData = $request->all();

            $reward = HcBattlepassReward::create($rewardData);

            return response()->json(['status' => 'success', 'data' => $reward], 201);
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function show($id)
    {
        try {
            $reward = HcBattlepassReward::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $reward,
            ]);
        } catch (\Exception $e) {

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $reward = HcBattlepassReward::find($id);

            if (!$reward) {
                return response()->json(['status' => 'error', 'message' => 'Reward not found', 'error_code' => 'NOT_FOUND'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name_item' => 'nullable|string|max:255',
                'category' => 'nullable|string|in:item,coin',
                'skin_id' => 'nullable|integer|exists:hr_skin_characters,id',
                'type' => 'nullable|string|in:free,premium',
                'value' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $rewardData = $request->all();

            $reward->update($rewardData);

            return response()->json(['status' => 'success', 'data' => $reward], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $reward = HcBattlepassReward::findOrFail($id);
            $reward->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Reward deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
