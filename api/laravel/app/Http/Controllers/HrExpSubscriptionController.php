<?php

namespace App\Http\Controllers;

use App\Models\HrExpSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
class HrExpSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'player_id', 'exp'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field',
                ], 400);
            }

            $query = HrExpSubscription::with(['player']);

            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('exp', 'like', "%{$globalFilter}%")
                      ->orWhereHas('player', function ($playerQuery) use ($globalFilter) {
                          $playerQuery->where('name', 'like', "%{$globalFilter}%");
                      });
                });
            }

            $expData = $query->orderBy($sortField, $sortDirection)->paginate($perPage);
            // $total = HrExpSubscription::where('player_id',$user->id)->sum('exp');

            $expData->transform(function ($exp) {
                return [
                    'id' => $exp->id,
                    'player_id' => $exp->player_id,
                    'player_name' => $exp->player ? $exp->player->name : null,
                    'exp' => $exp->exp,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $expData->currentPage(),
                'last_page' => $expData->lastPage(),
                'next_page' => $expData->currentPage() < $expData->lastPage() ? $expData->currentPage() + 1 : null,
                'prev_page' => $expData->currentPage() > 1 ? $expData->currentPage() - 1 : null,
                'next_page_url' => $expData->nextPageUrl(),
                'prev_page_url' => $expData->previousPageUrl(),
                'per_page' => $expData->perPage(),
                'total' => $expData->total(),
                'data' => $expData->items(),
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
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized, please login again',
                    'error_code' => 'USER_NOT_FOUND',
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'exp' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors(),
                ], 422);
            }
            $period = HrPeriodSubscription::where('id', $subscription->period_subscription_id)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();
            if($period){
                $purchase = HrBattlepassPurchase::where('player_id',$user->id)->where('purchased_at',now())->first();
                if( !$purchase ){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You are not subscribed.',
                        'error_code' => 'NOT_SUBSCRIBED',
                    ], 403);
                }
            }

            // Tambahkan player_id ke data request
            $expData = $request->all();
            $expData['player_id'] = $user->id;

            // Simpan data ke database
            $exp = HrExpSubscription::create($expData);

            return response()->json(['status' => 'success', 'data' => $exp], 201);
        } catch (\Exception $e) {
            dd($e);
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred.',
                'error_code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $exp = HrExpSubscription::with(['player'])->find($id);

            if (!$exp) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Experience record not found.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $exp->id,
                    'player_id' => $exp->player_id,
                    'player_name' => $exp->player ? $exp->player->name : null,

                    'exp' => $exp->exp,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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
            $exp = HrExpSubscription::with(['player'])->where('player_id',$user->id)->sum('exp');

            if (!$exp) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Experience record not found.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' =>  $exp
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $exp = HrExpSubscription::find($id);

            if (!$exp) {
                return response()->json(['status' => 'error', 'message' => 'Experience record not found', 'error_code' => 'NOT_FOUND'], 404);
            }

            $validator = Validator::make($request->all(), [
                'player_id' => 'nullable|integer|exists:hd_players,id',
                'exp' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $expData = $request->all();

            $exp->update($expData);

            return response()->json(['status' => 'success', 'data' => $exp], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $exp = HrExpSubscription::find($id);

            if (!$exp) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Experience record not found.',
                ], 404);
            }

            $exp->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Experience record deleted successfully.',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
