<?php

namespace App\Http\Controllers;

use App\Models\HrProgressBattlepass;
use App\Models\HcQuestBattlepass;
use App\Models\HdBattlepassQuest;
use App\Models\HrExpBattlepass;
use App\Models\HrPeriodBattlepass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
class HrProgressBattlepassController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'player_id', 'quest_battlepass_id', 'current_progress', 'is_completed', 'updated_at'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HrProgressBattlepass::with(['player', 'quest']);

            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('player_id', 'like', "%{$globalFilter}%")
                      ->orWhere('quest_battlepass_id', 'like', "%{$globalFilter}%")
                      ->orWhere('current_progress', 'like', "%{$globalFilter}%")
                      ->orWhere('is_completed', 'like', "%{$globalFilter}%");
                });
            }

            $progress = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            // Transform data to include only names for player and quest
            $progress->transform(function ($item) {
                return [
                    'id' => $item->id,
                    'player_id' => $item->player_id,
                    'quest_battlepass_id' => $item->quest_battlepass_id,
                    'current_progress' => $item->current_progress,
                    'is_completed' => $item->is_completed,
                    'updated_at' => $item->updated_at,
                    'player' => $item->player ? $item->player->name : null,
                    'quest' => $item->quest ? $item->quest->name_quest : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $progress->currentPage(),
                'last_page' => $progress->lastPage(),
                'next_page' => $progress->currentPage() < $progress->lastPage() ? $progress->currentPage() + 1 : null,
                'prev_page' => $progress->currentPage() > 1 ? $progress->currentPage() - 1 : null,
                'next_page_url' => $progress->nextPageUrl(),
                'prev_page_url' => $progress->previousPageUrl(),
                'per_page' => $progress->perPage(),
                'total' => $progress->total(),
                'data' => $progress->items(),
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
            // Validasi request
            $validator = Validator::make($request->all(), [
                'quest_battlepass_id' => 'required|integer|exists:hc_quest_battlepass,id',
                'current_progress' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Autentikasi user
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized, please login again',
                    'error_code' => 'USER_NOT_FOUND',
                ], 401);
            }


            // Ambil quest terkait
            $quest = HcQuestBattlepass::find($request->quest_battlepass_id);

            if (!$quest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Quest not found.',
                    'error_code' => 'QUEST_NOT_FOUND',
                ], 404);
            }
            $currentDate = now();
            $currentPeriod = HrPeriodBattlepass::where('start_date', '<=', $currentDate)
                ->where('end_date', '>=', $currentDate)
                ->first();
            $cekquest = HdBattlepassQuest::where('period_battlepass_id',$currentPeriod->id)->where('quest_id',$request->quest_battlepass_id)->first();
            if(!$cekquest){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Quest expired.',
                    'error_code' => 'QUEST_EXPIRED',
                    ], 404);
            }

            // Cek apakah progress sudah ada
            $progress = HrProgressBattlepass::where('player_id', $user->id)
            ->where('quest_battlepass_id', $request->quest_battlepass_id)
            ->first();

        if ($progress) {
            // Tambahkan progress baru ke progress sebelumnya
            $newProgress = $progress->current_progress + $request->current_progress;
            if ($newProgress > $quest->target){
                $newProgress = $quest->target;
            }

            // Update progress dan cek apakah sudah selesai
            $progress->update([
                'current_progress' => $newProgress,
                'is_completed' => $newProgress >= $quest->target,
                'updated_at' => now(),
            ]);
        } else {
            // Buat progress baru jika belum ada
            $newProgress = $request->current_progress;

            $progress = HrProgressBattlepass::create([
                'player_id' => $user->id,
                'quest_battlepass_id' => $request->quest_battlepass_id,
                'current_progress' => $newProgress,
                'is_completed' => (bool) $newProgress >= $quest->target??false,
            ]);
        }

        // Simpan EXP jika is_completed adalah true
        if ($progress->is_completed) {
            $exp = HrExpBattlepass::create([
                'player_id' => $user->id,
                'exp' => $quest->reward_exp,
            ]);
        }else{
            $exp = "";
        }
            return response()->json([
                'status' => 'success',
                'data' => $progress ?? 'New progress created',
                'exp'=> $exp,
            ], 201);
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
    public function show($id)
    {
        try {
            $progress = HrProgressBattlepass::find($id);
            if (!$progress) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Progress not found.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $progress,
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

            // Cari periode yang sedang berlangsung
            $currentDate = now();
            $currentPeriod = HrPeriodBattlepass::where('start_date', '<=', $currentDate)
                ->where('end_date', '>=', $currentDate)
                ->first();

            if (!$currentPeriod) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No active battlepass period found.',
                    'data' => [],
                ], 200);
            }

            // Mengambil semua quest dan progress (progress default = 0 jika tidak ditemukan)
            $quests = HdBattlepassQuest::whereHas('period', function ($query) use ($currentPeriod) {
                    $query->where('id', $currentPeriod->id);
                })
                ->with([
                    'quest',
                    'quest.progress' => function ($query) use ($user) {
                        $query->where('player_id', $user->id);
                    },
                ])
                ->get()
                ->map(function ($quest) {
                    $progress = optional($quest->quest->progress->first()); // Hindari error jika progress kosong

                    return [
                        'quest_id' => $quest->quest->id,
                        'name_quest' => $quest->quest->name_quest,
                        'quest_code' => $quest->quest->quest_code,
                        'description_quest' => $quest->quest->description_quest,
                        'reward_exp' => $quest->quest->reward_exp,
                        'category' => $quest->quest->category,
                        'target' => $quest->quest->target,
                        'current_progress' => $progress->current_progress ?? 0,
                        'is_completed' => (bool) $progress->is_completed,
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $quests,
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

    public function update(Request $request, $id)
    {
        try {
            $progress = HrProgressBattlepass::find($id);

            if (!$progress) {
                return response()->json(['status' => 'error', 'message' => 'Progress not found', 'error_code' => 'NOT_FOUND'], 404);
            }
            if (empty($request->all())) {
                return response()->json(['status' => 'error', 'message' => 'body is empty'], 400);
            }

            $validator = Validator::make($request->all(), [
                'player_id' => 'nullable|integer',
                'quest_battlepass_id' => 'nullable|integer',
                'current_progress' => 'nullable|integer',
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

            $progressData = $request->all();
            $progress->update($progressData);

            return response()->json(['status' => 'success', 'data' => $progress], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $progress = HrProgressBattlepass::find($id);

            if (!$progress) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Progress not found.',
                ], 404);
            }
            $progress->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Progress deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
