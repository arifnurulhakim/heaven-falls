<?php

namespace App\Http\Controllers;

use App\Models\HdBattlepassReward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HdBattlepassRewardsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'battlepass_id', 'reward_id'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HdBattlepassReward::with(['battlepass', 'reward']);

            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->whereHas('battlepass', function ($subQuery) use ($globalFilter) {
                        $subQuery->where('level_battlepass', 'like', "%{$globalFilter}%");
                    })->orWhereHas('reward', function ($subQuery) use ($globalFilter) {
                        $subQuery->where('name_item', 'like', "%{$globalFilter}%");
                    });
                });
            }

            $rewards = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $rewards->transform(function ($reward) {
                return [
                    'id' => $reward->id,
                    'battlepass' => $reward->battlepass ? $reward->battlepass->level_battlepass : null,
                    'reward' => $reward->reward ? $reward->reward->name_item : null,
                ];
            });

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
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'battlepass_id' => 'required|integer|exists:hd_battlepass,id',
                'reward_id' => 'required|integer|exists:hc_battlepass_rewards,id',
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

            $reward = HdBattlepassReward::create($rewardData);

            return response()->json(['status' => 'success', 'data' => $reward], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function show($id)
    {
        try {
            $reward = HdBattlepassReward::with(['battlepass', 'reward'])->find($id);

            if (!$reward) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Battlepass reward not found.',
                ], 404);
            }

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
            $reward = HdBattlepassReward::find($id);

            if (!$reward) {
                return response()->json(['status' => 'error', 'message' => 'Battlepass reward not found', 'error_code' => 'NOT_FOUND'], 404);
            }

            $validator = Validator::make($request->all(), [
                'battlepass_id' => 'nullable|integer|exists:hd_battlepass,id',
                'reward_id' => 'nullable|integer|exists:hc_battlepass_rewards,id',
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
            $reward = HdBattlepassReward::find($id);

            if (!$reward) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Battlepass reward not found.',
                ], 404);
            }

            $reward->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Battlepass reward deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
