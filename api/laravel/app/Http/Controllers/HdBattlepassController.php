<?php

namespace App\Http\Controllers;

use App\Models\HrPlayerBattlepass;
use App\Models\HrBattlepassPurchase;
use App\Models\HrPeriodBattlepass;
use App\Models\HcBattlepassReward;
use App\Models\HdBattlepass;
use App\Models\HrExpBattlepass;
use App\Models\HdWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class HdBattlepassController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'level_battlepass', 'period_battlepass_id', 'reach_exp'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HdBattlepass::with(['period']); // Optionally add relations

            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('level_battlepass', 'like', "%{$globalFilter}%")
                      ->orWhere('period_battlepass_id', 'like', "%{$globalFilter}%")
                      ->orWhere('reach_exp', 'like', "%{$globalFilter}%");
                });
            }

            $battlepasses = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $battlepasses->transform(function ($battlepass) {
                return [
                    'id' => $battlepass->id,
                    'level_battlepass' => $battlepass->level_battlepass,
                    'period_battlepass_id' => $battlepass->period_battlepass_id,
                    'reach_exp' => $battlepass->reach_exp,
                    'period' => $battlepass->period ? $battlepass->period->name : null,
                    'start_date' => $battlepass->period ? $battlepass->period->start_date : null,
                    'end_date' => $battlepass->period ? $battlepass->period->end_date : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $battlepasses->currentPage(),
                'last_page' => $battlepasses->lastPage(),
                'next_page' => $battlepasses->currentPage() < $battlepasses->lastPage() ? $battlepasses->currentPage() + 1 : null,
                'prev_page' => $battlepasses->currentPage() > 1 ? $battlepasses->currentPage() - 1 : null,
                'next_page_url' => $battlepasses->nextPageUrl(),
                'prev_page_url' => $battlepasses->previousPageUrl(),
                'per_page' => $battlepasses->perPage(),
                'total' => $battlepasses->total(),
                'data' => $battlepasses->items(),
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
                'level_battlepass' => 'required|integer',
                'period_battlepass_id' => 'required|exists:hr_period_battlepass,id',
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

            $battlepassData = $request->all();
            $battlepass = HdBattlepass::create($battlepassData);

            return response()->json(['status' => 'success', 'data' => $battlepass], 201);
        } catch (\Exception $e) {

            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function show($id)
    {
        try {
            $battlepass = HdBattlepass::find($id);
            if (!$battlepass) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Battlepass not found.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $battlepass,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    // public function showPlayer()
    // {
    //     try {
    //         // Autentikasi user
    //         $user = Auth::user();
    //         if (!$user) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Unauthorized, please login again',
    //                 'error_code' => 'USER_NOT_FOUND',
    //             ], 401);
    //         }

    //         // Cari periode yang sedang berlangsung
    //         $currentDate = now();
    //         $currentPeriod = HrPeriodBattlepass::where('start_date', '<=', $currentDate)
    //             ->where('end_date', '>=', $currentDate)
    //             ->first();

    //         if (!$currentPeriod) {
    //             return response()->json([
    //                 'status' => 'success',
    //                 'message' => 'No active battlepass period found.',
    //                 'total_exp' => 0,
    //                 'data' => [],
    //             ], 200);
    //         }

    //         // Ambil semua data Battlepass berdasarkan periode saat ini
    //         $battlepasses = HdBattlepass::with([
    //             'rewards',
    //             'rewards.reward',
    //         ])->where('period_battlepass_id', $currentPeriod->id)->get();

    //         // Hitung total exp dari player
    //         $totalExp = HrExpBattlepass::where('player_id', $user->id)->sum('exp');

    //         // Tambahkan properti isLock dan claimed berdasarkan kondisi exp dan klaim
    //         foreach ($battlepasses as $battlepass) {
    //             $playerBattlepass = HrPlayerBattlepass::where('player_id', $user->id)
    //                 ->where('battlepass_id', $battlepass->id)
    //                 ->first();

    //             // Ambil data pembelian battlepass player dalam periode yang sedang berlangsung
    //             $purchase = HrBattlepassPurchase::where('player_id', $user->id)
    //                 ->where('purchased_at', '>=', $currentPeriod->start_date)
    //                 ->where('purchased_at', '<=', $currentPeriod->end_date)
    //                 ->first();

    //             $battlepass->isPurchased = $purchase ? true : false;

    //             // Cek apakah battlepass terkunci atau tidak
    //             $battlepass->isLock = $totalExp < $battlepass->reach_exp;

    //             // Tambahkan pengecekan tipe reward
    //             $isPremiumReward = $battlepass->rewards->some(function ($reward) {
    //                 return $reward->type === 'premium';
    //             });

    //             if ($isPremiumReward && !$battlepass->isPurchased) {
    //                 $battlepass->isLock = true;
    //             }

    //             // Tambahkan properti claimed
    //             $battlepass->isClaim = $playerBattlepass ? true : false;
    //         }

    //         return response()->json([
    //             'status' => 'success',
    //             'total_exp' => $totalExp,
    //             'end_date_session' => $currentPeriod->end_date,
    //             'data' => $battlepasses,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'An error occurred.',
    //             'error_code' => 'INTERNAL_ERROR',
    //             'error' => $e->getMessage(), // Debugging purpose (remove in production)
    //         ], 500);
    //     }
    // }
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

        $currentDate = now();
        $currentPeriod = HrPeriodBattlepass::where('start_date', '<=', $currentDate)
            ->where('end_date', '>=', $currentDate)
            ->first();

        if (!$currentPeriod) {
            return response()->json([
                'status' => 'success',
                'message' => 'No active battlepass period found.',
                'total_exp' => 0,
                'data' => [],
            ], 200);
        }

        $battlepasses = HdBattlepass::with(['rewards', 'rewards.reward'])
            ->where('period_battlepass_id', $currentPeriod->id)
            ->get();

        $totalExp = HrExpBattlepass::where('player_id', $user->id)->sum('exp');

        foreach ($battlepasses as $battlepass) {
            $playerBattlepass = HrPlayerBattlepass::where('player_id', $user->id)
                ->where('battlepass_id', $battlepass->id)
                ->first();

            $purchase = HrBattlepassPurchase::where('player_id', $user->id)
                ->where('purchased_at', '>=', $currentPeriod->start_date)
                ->where('purchased_at', '<=', $currentPeriod->end_date)
                ->first();

            $battlepass->isPurchased = $purchase ? true : false;
            $battlepass->isLock = $totalExp < $battlepass->reach_exp;

            $battlepass->rewards->transform(function ($reward) use ($playerBattlepass, $battlepass) {
                if (!$playerBattlepass) {
                    $reward->isClaim = false;
                } elseif ($reward->reward->type === 'free') {
                    $reward->isClaim = (bool) $playerBattlepass->status_claimed;
                } elseif ($reward->reward->type === 'premium') {
                    $reward->isClaim = $battlepass->isPurchased ? (bool) $playerBattlepass->status_claimed_premium : false;
                } else {
                    $reward->isClaim = false;
                }
                return $reward;
            });
            
            $isPremiumReward = $battlepass->rewards->some(fn($reward) => $reward->type === 'premium');
            if ($isPremiumReward && !$battlepass->isPurchased) {
                $battlepass->isLock = true;
            }
        }

        return response()->json([
            'status' => 'success',
            'total_exp' => $totalExp,
            'end_date_session' => $currentPeriod->end_date,
            'data' => $battlepasses,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred.',
            'error_code' => 'INTERNAL_ERROR',
            'error' => $e->getMessage(),
        ], 500);
    }
}
    // public function claim(Request $request)
    // {
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'reward_id' => 'required|exists:hc_battlepass_rewards,id',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Validation error',
    //                 'error_code' => 'VALIDATION_ERROR',
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }

    //         $user = Auth::user();
    //         if (!$user) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Unauthorized, please login again',
    //                 'error_code' => 'USER_NOT_FOUND',
    //             ], 401);
    //         }

    //         $battlepass = HdBattlepass::select('hd_battlepass.*','hc_battlepass_rewards.category','hc_battlepass_rewards.value') // Pilih kolom dari HdBattlepass
    //         ->join('hd_battlepass_rewards', 'hd_battlepass.id', '=', 'hd_battlepass_rewards.battlepass_id')
    //         ->join('hc_battlepass_rewards', 'hd_battlepass_rewards.reward_id', '=', 'hc_battlepass_rewards.id')
    //         ->where('hc_battlepass_rewards.id', $request->reward_id)
    //         ->first();
    //         // dd( $battlepass );
    //         $totalExp = HrExpBattlepass::where('player_id', $user->id)->sum('exp');

    //         // foreach ($battlepass as $battlepass) {
    //             $playerBattlepass = HrPlayerBattlepass::where('player_id', $user->id)
    //                 ->where('battlepass_id', $battlepass->id)
    //                 ->first();

    //             $period = HrPeriodBattlepass::where('id', $battlepass->period_battlepass_id)->first();

    //             if ($period) {
    //                 $purchase = HrBattlepassPurchase::where('player_id', $user->id)
    //                     ->where('purchased_at', '>=', $period->start_date)
    //                     ->where('purchased_at', '<=', $period->end_date)
    //                     ->first();

    //                 $battlepass->isPurchased = $purchase ? true : false;
    //             } else {
    //                 $battlepass->isPurchased = false;
    //             }

    //             // Cek apakah battlepass terkunci atau tidak
    //             $battlepass->isLock = $totalExp < $battlepass->reach_exp;

    //             // Tambahkan pengecekan tipe reward
    //             $isPremiumReward = $battlepass->rewards->some(function ($reward) {
    //                 return $reward->type === 'premium';
    //             });

    //             if ($isPremiumReward && !$battlepass->isPurchased) {
    //                 $battlepass->isLock = true;
    //             }

    //             // Tambahkan properti claimed
    //             $battlepass->isClaim = $playerBattlepass ? true : false;

    //             // Jika battlepass terkunci, berikan response "Can't claim reward"
    //             if ($battlepass->isLock == true) {
    //                 return response()->json([
    //                     'status' => 'error',
    //                     'message' => 'Can\'t claim reward',
    //                     'error_code' => 'REWARD_LOCKED',
    //                 ], 403);
    //             }
    //             if ($battlepass->isClaim == true) {
    //                 return response()->json([
    //                     'status' => 'error',
    //                     'message' => 'already claimed',
    //                     'error_code' => 'REWARD_CLAIMED',
    //                 ], 403);
    //             }


    //             if ($battlepass->isLock === false) {
    //                 if($battlepass->category =='coin'){
    //                     $wallet = HdWallet::create([
    //                         'player_id' => $user->id,
    //                         'amount' => $battlepass->value,
    //                         'currency_id' => 1,
    //                         'category'=> 'reward',
    //                         'label'=>'reward battlepass',
    //                         'created_by'=> $user->id,
    //                         'modified_by'=> $user->id,
    //                         ]);
    //                 }else{
    //                     return response()->json([
    //                         'status' => 'error',
    //                         'message' => 'claim item coming soon',
    //                         'error_code' => 'CLAIM_ITEM_ERROR',
    //                     ], 403);
    //                 }
    //                 $claimedData=HrPlayerBattlepass::where('player_id',$user->id)->where('battlepass_id',$battlepass->id)->first();
    //                 if(!$claimedData){
    //                     $type=
    //                     $claimedData=HrPlayerBattlepass::create([
    //                         'player_id' => $user->id,
    //                         'battlepass_id' => $battlepass->id,
    //                         'status_claimed' => true, // Simpan waktu klaim
    
    //                     ]);
    //                 }else
                   

    //             }

    //         return response()->json([
    //             'status' => 'success',
    //             'data' => $claimedData,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         dd($e);
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'An error occurred.',
    //             'error_code' => 'INTERNAL_ERROR',
    //             'error' => $e->getMessage(), // Debugging purpose (remove in production)
    //         ], 500);
    //     }
    // }
    public function claim(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'rewards_id' => 'required|exists:hd_battlepass_rewards,id',
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
    
            $battlepass = HdBattlepass::select('hd_battlepass.*', 'hc_battlepass_rewards.category', 'hc_battlepass_rewards.value', 'hc_battlepass_rewards.type')
                ->join('hd_battlepass_rewards', 'hd_battlepass.id', '=', 'hd_battlepass_rewards.battlepass_id')
                ->join('hc_battlepass_rewards', 'hd_battlepass_rewards.reward_id', '=', 'hc_battlepass_rewards.id')
                ->where('hd_battlepass_rewards.id', $request->rewards_id)
                ->first();
    
            $totalExp = HrExpBattlepass::where('player_id', $user->id)->sum('exp');
    
            $playerBattlepass = HrPlayerBattlepass::where('player_id', $user->id)
                ->where('battlepass_id', $battlepass->id)
                ->first();
    
            $period = HrPeriodBattlepass::where('id', $battlepass->period_battlepass_id)->first();
    
            if ($period) {
                $purchase = HrBattlepassPurchase::where('player_id', $user->id)
                    ->where('purchased_at', '>=', $period->start_date)
                    ->where('purchased_at', '<=', $period->end_date)
                    ->first();
                $battlepass->isPurchased = $purchase ? true : false;
            } else {
                $battlepass->isPurchased = false;
            }
    
            $battlepass->isLock = $totalExp < $battlepass->reach_exp;
    
            if ($battlepass->isLock) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Can\'t claim reward',
                    'error_code' => 'REWARD_LOCKED',
                ], 403);
            }
    
            if ($playerBattlepass) {
                if ($battlepass->type === 'free' && $playerBattlepass->status_claimed === 1) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Already claimed',
                        'error_code' => 'REWARD_CLAIMED',
                    ], 403);
                }
                if ($battlepass->type === 'premium' && $playerBattlepass->status_claimed_premium===1) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Already claimed',
                        'error_code' => 'REWARD_CLAIMED',
                    ], 403);
                }
            }
    
            if ($battlepass->category === 'coin') {
                HdWallet::create([
                    'player_id' => $user->id,
                    'amount' => $battlepass->value,
                    'currency_id' => 1,
                    'category' => 'reward',
                    'label' => 'reward battlepass',
                    'created_by' => $user->id,
                    'modified_by' => $user->id,
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Claim item coming soon',
                    'error_code' => 'CLAIM_ITEM_ERROR',
                ], 403);
            }
    
            if (!$playerBattlepass) {
                $claimedData = HrPlayerBattlepass::create([
                    'player_id' => $user->id,
                    'battlepass_id' => $battlepass->id,
                    'status_claimed' => $battlepass->type === 'free' ? 1 : 0,
                    'status_claimed_premium' => $battlepass->type === 'premium' ? 1 : 0,
                ]);
            } else {
                if ($battlepass->type === 'free') {
                    $playerBattlepass->update(['status_claimed' => 1]);
                } elseif ($battlepass->type === 'premium') {
                    $playerBattlepass->update(['status_claimed_premium' => 1]);
                }
                $claimedData = $playerBattlepass;
            }
    
            return response()->json([
                'status' => 'success',
                'data' => $claimedData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred.',
                'error_code' => 'INTERNAL_ERROR',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    

    public function update(Request $request, $id)
    {
        try {
            $battlepass = HdBattlepass::find($id);

            if (!$battlepass) {
                return response()->json(['status' => 'error', 'message' => 'Battlepass not found', 'error_code' => 'NOT_FOUND'], 404);
            }

            if (empty($request->all())) {
                return response()->json(['status' => 'error', 'message' => 'Body is empty'], 400);
            }

            $validator = Validator::make($request->all(), [
                'level_battlepass' => 'nullable|integer',
                'period_battlepass_id' => 'nullable|exists:hr_period_battlepasses,id',
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

            $battlepassData = $request->all();
            $battlepass->update($battlepassData);

            return response()->json(['status' => 'success', 'data' => $battlepass], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $battlepass = HdBattlepass::find($id);

            if (!$battlepass) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Battlepass not found.',
                ], 404);
            }

            $battlepass->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Battlepass deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
