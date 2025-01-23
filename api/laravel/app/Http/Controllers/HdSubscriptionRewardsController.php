<?php

namespace App\Http\Controllers;

use App\Models\HdSubscriptionReward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HdSubscriptionRewardsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'subscription_id', 'reward_id'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HdSubscriptionReward::with(['subscription', 'reward']);

            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->whereHas('subscription', function ($subQuery) use ($globalFilter) {
                        $subQuery->where('level_subscription', 'like', "%{$globalFilter}%");
                    })->orWhereHas('reward', function ($subQuery) use ($globalFilter) {
                        $subQuery->where('name_item', 'like', "%{$globalFilter}%");
                    });
                });
            }

            $rewards = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $rewards->transform(function ($reward) {
                return [
                    'id' => $reward->id,
                    'subscription' => $reward->subscription ? $reward->subscription->level_subscription : null,
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
                'subscription_id' => 'required|integer|exists:hd_subscription,id',
                'reward_id' => 'required|integer|exists:hc_subscription_rewards,id',
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

            $reward = HdSubscriptionReward::create($rewardData);

            return response()->json(['status' => 'success', 'data' => $reward], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function show($id)
    {
        try {
            $reward = HdSubscriptionReward::with(['subscription', 'reward'])->find($id);

            if (!$reward) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Subscription reward not found.',
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
            $reward = HdSubscriptionReward::find($id);

            if (!$reward) {
                return response()->json(['status' => 'error', 'message' => 'Subscription reward not found', 'error_code' => 'NOT_FOUND'], 404);
            }

            $validator = Validator::make($request->all(), [
                'subscription_id' => 'nullable|integer|exists:hd_subscription,id',
                'reward_id' => 'nullable|integer|exists:hc_subscription_rewards,id',
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
            $reward = HdSubscriptionReward::find($id);

            if (!$reward) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Subscription reward not found.',
                ], 404);
            }

            $reward->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Subscription reward deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
