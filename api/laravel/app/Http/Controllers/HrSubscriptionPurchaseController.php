<?php

namespace App\Http\Controllers;

use App\Models\HrSubscriptionPurchase;
use App\Models\HdSubscription;
use App\Models\HrPeriodSubscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HrSubscriptionPurchaseController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'player_id', 'subscription_id', 'purchased_at'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HrSubscriptionPurchase::with(['player', 'subscription']);

            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->whereHas('player', function ($subQuery) use ($globalFilter) {
                        $subQuery->where('name', 'like', "%{$globalFilter}%");
                    })->orWhereHas('subscription', function ($subQuery) use ($globalFilter) {
                        $subQuery->where('level_subscription', 'like', "%{$globalFilter}%");
                    });
                });
            }

            $purchases = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $purchases->transform(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'player' => $purchase->player ? $purchase->player->name : null,
                    'subscription' => $purchase->subscription ? $purchase->subscription->level_subscription : null,
                    'purchased_at' => $purchase->purchased_at,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $purchases->currentPage(),
                'last_page' => $purchases->lastPage(),
                'next_page' => $purchases->currentPage() < $purchases->lastPage() ? $purchases->currentPage() + 1 : null,
                'prev_page' => $purchases->currentPage() > 1 ? $purchases->currentPage() - 1 : null,
                'next_page_url' => $purchases->nextPageUrl(),
                'prev_page_url' => $purchases->previousPageUrl(),
                'per_page' => $purchases->perPage(),
                'total' => $purchases->total(),
                'data' => $purchases->items(),
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
            // Get the authenticated user
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized, please login again',
                    'error_code' => 'USER_NOT_FOUND',
                ], 401);
            }

            // Fetch the current period based on the current date
            $period = HrPeriodSubscription::where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->first();
                // dd($period);÷ß÷

            if (!$period) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active period available.',
                    'error_code' => 'PERIOD_NOT_FOUND',
                ], 404);
            }

            // Fetch the subscription associated with the period
            $subscription = HdSubscription::where('period_subscription_id', $period->id)->first();

// dd($subscription);[]
            if (!$subscription) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No subscription found for the active period.',
                    'error_code' => 'BATTLEPASS_NOT_FOUND',
                ], 404);
            }

            // if ($request->subscription_id != $subscription->id) {
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'The selected subscription does not belong to the active period.',
            //         'error_code' => 'INVALID_BATTLEPASS',
            //     ], 400);
            // }

            // Create the purchase record
            $purchase = HrSubscriptionPurchase::create([
                'subscription_id' => $subscription->id,
                'player_id' => $user->id,
                'purchased_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $purchase,
            ], 201);
        } catch (\Exception $e) {
            dd($e);
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred.',
                'error_code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    public function showPlayer()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized, please login again',
                    'error_code' => 'USER_NOT_FOUND',
                ], 401);
            }

            $purchase = HrSubscriptionPurchase::with(['subscription'])->where('player_id',$user->id)->first();

            if (!$purchase) {
                return response()->json(['status' => 'error', 'message' => 'Subscription purchase not found.'], 404);
            }

            return response()->json(['status' => 'success', 'data' => $purchase], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function show($id)
    {
        try {
            $purchase = HrSubscriptionPurchase::with(['player', 'subscription'])->find($id);

            if (!$purchase) {
                return response()->json(['status' => 'error', 'message' => 'Subscription purchase not found.'], 404);
            }

            return response()->json(['status' => 'success', 'data' => $purchase], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $purchase = HrSubscriptionPurchase::find($id);

            if (!$purchase) {
                return response()->json(['status' => 'error', 'message' => 'Subscription purchase not found.', 'error_code' => 'NOT_FOUND'], 404);
            }

            $validator = Validator::make($request->all(), [
                'player_id' => 'nullable|integer|exists:hd_players,id',
                'subscription_id' => 'nullable|integer|exists:hd_subscription,id',
                'purchased_at' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation error', 'error_code' => 'VALIDATION_ERROR', 'errors' => $validator->errors()], 422);
            }

            $purchase->update($request->all());

            return response()->json(['status' => 'success', 'data' => $purchase], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $purchase = HrSubscriptionPurchase::find($id);

            if (!$purchase) {
                return response()->json(['status' => 'error', 'message' => 'Subscription purchase not found.'], 404);
            }

            $purchase->delete();

            return response()->json(['status' => 'success', 'message' => 'Subscription purchase deleted successfully.'], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
