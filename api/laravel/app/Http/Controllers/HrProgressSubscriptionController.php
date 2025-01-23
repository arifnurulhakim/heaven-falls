<?php

namespace App\Http\Controllers;

use App\Models\HrProgressSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HrProgressSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'player_id', 'quest_subscription_id', 'current_progress', 'is_completed', 'updated_at'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HrProgressSubscription::query();

            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('player_id', 'like', "%{$globalFilter}%")
                      ->orWhere('quest_subscription_id', 'like', "%{$globalFilter}%")
                      ->orWhere('current_progress', 'like', "%{$globalFilter}%")
                      ->orWhere('is_completed', 'like', "%{$globalFilter}%")
                      ->orWhere('updated_at', 'like', "%{$globalFilter}%");
                });
            }

            $subscriptions = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'next_page' => $subscriptions->currentPage() < $subscriptions->lastPage() ? $subscriptions->currentPage() + 1 : null,
                'prev_page' => $subscriptions->currentPage() > 1 ? $subscriptions->currentPage() - 1 : null,
                'next_page_url' => $subscriptions->nextPageUrl(),
                'prev_page_url' => $subscriptions->previousPageUrl(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
                'data' => $subscriptions->items(),
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
                'player_id' => 'required|exists:hd_players,id',
                'quest_subscription_id' => 'required|exists:hc_quest_battlepass,id',
                'current_progress' => 'required|integer|min:0',
                'is_completed' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation error', 'error_code' => 'VALIDATION_ERROR', 'errors' => $validator->errors()], 422);
            }

            $progressSubscription = HrProgressSubscription::create($request->all());

            return response()->json(['status' => 'success', 'data' => $progressSubscription], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function show($id)
    {
        try {
            $progressSubscription = HrProgressSubscription::find($id);

            if (!$progressSubscription) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Progress Subscription not found.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $progressSubscription,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $progressSubscription = HrProgressSubscription::find($id);

            if (!$progressSubscription) {
                return response()->json(['status' => 'error', 'message' => 'Progress Subscription not found', 'error_code' => 'NOT_FOUND'], 404);
            }
            if (empty($request->all())) {
                return response()->json(['status' => 'error', 'message' => 'body is empty'], 400);
            }

            $validator = Validator::make($request->all(), [
                'player_id' => 'nullable|exists:hd_players,id',
                'quest_subscription_id' => 'nullable|exists:hc_quest_battlepass,id',
                'current_progress' => 'nullable|integer|min:0',
                'is_completed' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $progressSubscription->update($request->all());

            return response()->json(['status' => 'success', 'data' => $progressSubscription], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $progressSubscription = HrProgressSubscription::find($id);

            if (!$progressSubscription) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Progress Subscription not found.',
                ], 404);
            }
            $progressSubscription->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Progress Subscription deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
