<?php

namespace App\Http\Controllers;

use App\Models\HrBattlepassPurchase;
use App\Models\HdBattlepass;
use App\Models\HrPeriodBattlepass;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HrBattlepassPurchaseController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'player_id', 'battlepass_id', 'purchased_at'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HrBattlepassPurchase::with(['player', 'battlepass']);

            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->whereHas('player', function ($subQuery) use ($globalFilter) {
                        $subQuery->where('name', 'like', "%{$globalFilter}%");
                    })->orWhereHas('battlepass', function ($subQuery) use ($globalFilter) {
                        $subQuery->where('level_battlepass', 'like', "%{$globalFilter}%");
                    });
                });
            }

            $purchases = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $purchases->transform(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'player' => $purchase->player ? $purchase->player->name : null,
                    'battlepass' => $purchase->battlepass ? $purchase->battlepass->level_battlepass : null,
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
            $period = HrPeriodBattlepass::where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->first();

            if (!$period) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active period available.',
                    'error_code' => 'PERIOD_NOT_FOUND',
                ], 404);
            }

            // Fetch the battlepass associated with the period
            $battlepass = HdBattlepass::where('period_battlepass_id', $period->id)->first();

            if (!$battlepass) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No battlepass found for the active period.',
                    'error_code' => 'BATTLEPASS_NOT_FOUND',
                ], 404);
            }

            // if ($request->battlepass_id != $battlepass->id) {
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'The selected battlepass does not belong to the active period.',
            //         'error_code' => 'INVALID_BATTLEPASS',
            //     ], 400);
            // }

            // Create the purchase record
            $purchase = HrBattlepassPurchase::create([
                'battlepass_id' => $battlepass->id,
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

            // Cek apakah user memiliki pembelian battlepass
            $purchase = HrBattlepassPurchase::with(['player', 'battlepass'])
                ->where('player_id', $user->id)
                ->first();

            if (!$purchase) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Battlepass purchase not found.',
                ], 404);
            }

            // Cari periode yang sedang berlangsung
            $currentDate = now();
            $currentPeriod = HrPeriodBattlepass::where('start_date', '<=', $currentDate)
                ->where('end_date', '>=', $currentDate)
                ->first();

            if (!$currentPeriod) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active battlepass period found.',
                ], 404);
            }

            // Cek apakah battlepass masih berlaku
            if ($purchase->purchased_at <= $currentPeriod->start_date || $purchase->purchased_at >= $currentPeriod->end_date) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Battlepass premium has expired.',
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'data' => $purchase,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred.',
                'error_code' => 'INTERNAL_ERROR',
                'error' => $e->getMessage(), // Debugging purpose (remove in production)
            ], 500);
        }
    }
    public function show($id)
    {
        try {
            $purchase = HrBattlepassPurchase::with(['player', 'battlepass'])->find($id);

            if (!$purchase) {
                return response()->json(['status' => 'error', 'message' => 'Battlepass purchase not found.'], 404);
            }

            return response()->json(['status' => 'success', 'data' => $purchase], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $purchase = HrBattlepassPurchase::find($id);

            if (!$purchase) {
                return response()->json(['status' => 'error', 'message' => 'Battlepass purchase not found.', 'error_code' => 'NOT_FOUND'], 404);
            }

            $validator = Validator::make($request->all(), [
                'player_id' => 'nullable|integer|exists:hd_players,id',
                'battlepass_id' => 'nullable|integer|exists:hd_battlepass,id',
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
            $purchase = HrBattlepassPurchase::find($id);

            if (!$purchase) {
                return response()->json(['status' => 'error', 'message' => 'Battlepass purchase not found.'], 404);
            }

            $purchase->delete();

            return response()->json(['status' => 'success', 'message' => 'Battlepass purchase deleted successfully.'], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
