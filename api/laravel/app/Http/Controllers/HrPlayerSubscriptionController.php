<?php

namespace App\Http\Controllers;

use App\Models\HrPlayerSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HrPlayerSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'subscription_id', 'player_id', 'status_claimed', 'created_at', 'updated_at'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HrPlayerSubscription::with(['subscription', 'player']);

            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('subscription_id', 'like', "%{$globalFilter}%")
                      ->orWhere('player_id', 'like', "%{$globalFilter}%")
                      ->orWhere('status_claimed', 'like', "%{$globalFilter}%")
                      ->orWhere('created_at', 'like', "%{$globalFilter}%")
                      ->orWhere('updated_at', 'like', "%{$globalFilter}%");
                });
            }

            $subscriptions = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $subscriptions->transform(function ($item) {
                return [
                    'id' => $item->id,
                    'subscription_id' => $item->subscription_id,
                    'player_id' => $item->player_id,
                    'status_claimed' => $item->status_claimed,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'subscription' => $item->subscription ? $item->subscription->level_battlepass : null, // Adjust as needed
                    'player' => $item->player ? $item->player->name : null, // Adjust as needed
                ];
            });

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
                'subscription_id' => 'required|exists:hd_subscription,id',
                'player_id' => 'required|exists:hd_players,id',
                'status_claimed' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation error', 'error_code' => 'VALIDATION_ERROR', 'errors' => $validator->errors()], 422);
            }

            $subscriptionData = $request->all();
            $subscription = HrPlayerSubscription::create($subscriptionData);

            return response()->json(['status' => 'success', 'data' => $subscription], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function show($id)
    {
        try {
            $subscription = HrPlayerSubscription::with(['subscription', 'player'])->find($id);

            if (!$subscription) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Subscription not found.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $subscription,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $subscription = HrPlayerSubscription::find($id);

            if (!$subscription) {
                return response()->json(['status' => 'error', 'message' => 'Subscription not found', 'error_code' => 'NOT_FOUND'], 404);
            }
            if (empty($request->all())) {
                return response()->json(['status' => 'error', 'message' => 'body is empty'], 400);
            }

            $validator = Validator::make($request->all(), [
                'subscription_id' => 'nullable|exists:hd_subscription,id',
                'player_id' => 'nullable|exists:hd_players,id',
                'status_claimed' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $subscriptionData = $request->all();
            $subscription->update($subscriptionData);

            return response()->json(['status' => 'success', 'data' => $subscription], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $subscription = HrPlayerSubscription::find($id);

            if (!$subscription) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Subscription not found.',
                ], 404);
            }
            $subscription->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Subscription deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
