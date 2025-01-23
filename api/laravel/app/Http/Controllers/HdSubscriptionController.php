<?php

namespace App\Http\Controllers;

use App\Models\HrPlayerSubscription;
use App\Models\HrSubscriptionPurchase;
use App\Models\HrPeriodSubscription;
use App\Models\HcSubscriptionReward;
use App\Models\HdSubscription;
use App\Models\HrExpSubscription;
use App\Models\HdWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class HdSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'level_subscription', 'period_subscription_id', 'reach_exp'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HdSubscription::with(['period']); // Optionally add relations

            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('level_subscription', 'like', "%{$globalFilter}%")
                      ->orWhere('period_subscription_id', 'like', "%{$globalFilter}%")
                      ->orWhere('reach_exp', 'like', "%{$globalFilter}%");
                });
            }

            $subscriptiones = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $subscriptiones->transform(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'level_subscription' => $subscription->level_subscription,
                    'period_subscription_id' => $subscription->period_subscription_id,
                    'reach_exp' => $subscription->reach_exp,
                    'period' => $subscription->period ? $subscription->period->name : null,
                    'start_date' => $subscription->period ? $subscription->period->start_date : null,
                    'end_date' => $subscription->period ? $subscription->period->end_date : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $subscriptiones->currentPage(),
                'last_page' => $subscriptiones->lastPage(),
                'next_page' => $subscriptiones->currentPage() < $subscriptiones->lastPage() ? $subscriptiones->currentPage() + 1 : null,
                'prev_page' => $subscriptiones->currentPage() > 1 ? $subscriptiones->currentPage() - 1 : null,
                'next_page_url' => $subscriptiones->nextPageUrl(),
                'prev_page_url' => $subscriptiones->previousPageUrl(),
                'per_page' => $subscriptiones->perPage(),
                'total' => $subscriptiones->total(),
                'data' => $subscriptiones->items(),
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
                'level_subscription' => 'required|integer',
                'period_subscription_id' => 'required|exists:hr_period_subscription,id',
                'reach_exp' => 'required|integer',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors()
                ], 422);
            }

            $subscriptionData = $request->all();
            $subscription = HdSubscription::create($subscriptionData);

            return response()->json(['status' => 'success', 'data' => $subscription], 201);
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function show($id)
    {
        try {
            $subscription = HdSubscription::find($id);
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
    public function showPlayer()
    {
        try {
            // Autentikasi user
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized, please login again',
                    'error_code' => 'USER_NOT_FOUND',
                ], 401);
            }

            // Ambil semua data Subscription dengan relasi Rewards dan Reward Details
            $subscriptiones = HdSubscription::with([
                'rewards',        // Relasi ke HdSubscriptionRewards
                'rewards.reward', // Relasi ke HcSubscriptionReward
            ])->get();

            // Hitung total exp dari player
            $totalExp = HrExpSubscription::where('player_id', $user->id)->sum('exp');

            // Tambahkan properti isLock dan claimed berdasarkan kondisi exp dan klaim
            foreach ($subscriptiones as $subscription) {
                $playerSubscription = HrPlayerSubscription::where('player_id', $user->id)
                    ->where('subscription_id', $subscription->id)
                    ->first();

                $period = HrPeriodSubscription::where('id', $subscription->period_subscription_id)->first();

                if ($period) {
                    // Ambil data pembelian subscription player dengan kondisi purchased_at dalam rentang periode
                    $purchase = HrSubscriptionPurchase::where('player_id', $user->id)
                        ->where('purchased_at', '>=', $period->start_date)
                        ->where('purchased_at', '<=', $period->end_date)
                        ->first();

                    $subscription->isPurchased = $purchase ? true : false;
                } else {
                    $subscription->isPurchased = false;
                }

                // Cek apakah subscription terkunci atau tidak
                $subscription->isLock = $totalExp < $subscription->reach_exp;

                // Tambahkan pengecekan tipe reward
                $isPremiumReward = $subscription->rewards->some(function ($reward) {
                    return $reward->type === 'premium';
                });

                if ($isPremiumReward && !$subscription->isPurchased) {
                    $subscription->isLock = true;
                }

                // Tambahkan properti claimed
                $subscription->isClaim = $playerSubscription ? true : false;
            }

            return response()->json([
                'status' => 'success',
                'total_exp' => $totalExp,
                'data' => $subscriptiones,
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
    public function claim(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reward_id' => 'required|exists:hc_subscription_rewards,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized, please login again',
                    'error_code' => 'USER_NOT_FOUND',
                ], 401);
            }

            $subscription = HdSubscription::select('hd_subscription.*','hc_subscription_rewards.category','hc_subscription_rewards.value') // Pilih kolom dari HdSubscription
            ->join('hd_subscription_rewards', 'hd_subscription.id', '=', 'hd_subscription_rewards.subscription_id')
            ->join('hc_subscription_rewards', 'hd_subscription_rewards.reward_id', '=', 'hc_subscription_rewards.id')
            ->where('hc_subscription_rewards.id', $request->reward_id)
            ->first();
            // dd( $subscription );
            $totalExp = HrExpSubscription::where('player_id', $user->id)->sum('exp');

            // foreach ($subscription as $subscription) {
                $playerSubscription = HrPlayerSubscription::where('player_id', $user->id)
                    ->where('subscription_id', $subscription->id)
                    ->first();

                $period = HrPeriodSubscription::where('id', $subscription->period_subscription_id)->first();

                if ($period) {
                    $purchase = HrSubscriptionPurchase::where('player_id', $user->id)
                        ->where('purchased_at', '>=', $period->start_date)
                        ->where('purchased_at', '<=', $period->end_date)
                        ->first();

                    $subscription->isPurchased = $purchase ? true : false;
                } else {
                    $subscription->isPurchased = false;
                }

                // Cek apakah subscription terkunci atau tidak
                $subscription->isLock = $totalExp < $subscription->reach_exp;

                // Tambahkan pengecekan tipe reward
                $isPremiumReward = $subscription->rewards->some(function ($reward) {
                    return $reward->type === 'premium';
                });

                if ($isPremiumReward && !$subscription->isPurchased) {
                    $subscription->isLock = true;
                }

                // Tambahkan properti claimed
                $subscription->isClaim = $playerSubscription ? true : false;

                // Jika subscription terkunci, berikan response "Can't claim reward"
                if ($subscription->isLock == true) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Can\'t claim reward',
                        'error_code' => 'REWARD_LOCKED',
                    ], 403);
                }
                if ($subscription->isClaim == true) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'already claimed',
                        'error_code' => 'REWARD_CLAIMED',
                    ], 403);
                }

// dd($subscription);
                // Jika tidak terkunci, simpan data di HrPlayerSubscription
                if ($subscription->isLock === false) {
                    if($subscription->category =='coin'){
                        $wallet = HdWallet::create([
                            'player_id' => $user->id,
                            'amount' => $subscription->value,
                            'currency_id' => 1,
                            'category'=> 'reward',
                            'label'=>'reward subscription',
                            'created_by'=> $user->id,
                            'modified_by'=> $user->id,
                            ]);
                    }else{
                        return response()->json([
                            'status' => 'error',
                            'message' => 'claim item coming soon',
                            'error_code' => 'CLAIM_ITEM_ERROR',
                        ], 403);
                    }
                    $claimedData=HrPlayerSubscription::create([
                        'player_id' => $user->id,
                        'subscription_id' => $subscription->id,
                        'status_claimed' => true, // Simpan waktu klaim

                    ]);

                }

            return response()->json([
                'status' => 'success',
                'data' => $claimedData,
            ], 200);
        } catch (\Exception $e) {
            dd($e);
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred.',
                'error_code' => 'INTERNAL_ERROR',
                'error' => $e->getMessage(), // Debugging purpose (remove in production)
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $subscription = HdSubscription::find($id);

            if (!$subscription) {
                return response()->json(['status' => 'error', 'message' => 'Subscription not found', 'error_code' => 'NOT_FOUND'], 404);
            }

            if (empty($request->all())) {
                return response()->json(['status' => 'error', 'message' => 'Body is empty'], 400);
            }

            $validator = Validator::make($request->all(), [
                'level_subscription' => 'nullable|integer',
                'period_subscription_id' => 'nullable|exists:hr_period_subscriptiones,id',
                'reach_exp' => 'nullable|integer',
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
            $subscription = HdSubscription::find($id);

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
