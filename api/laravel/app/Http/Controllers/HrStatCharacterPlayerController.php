<?php

namespace App\Http\Controllers;

use App\Models\HrStatCharacterPlayer;
use App\Models\HdCharacterPlayer;
use App\Models\HdUpgradeCurrency;
use App\Models\HdWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
class HrStatCharacterPlayerController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'level', 'player_id', 'character_id', 'hitpoints', 'damage', 'defense', 'speed'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field',
                ], 400);
            }

            $query = HrStatCharacterPlayer::with(['player', 'character']);

            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('hitpoints', 'like', "%{$globalFilter}%")
                      ->orWhere('damage', 'like', "%{$globalFilter}%")
                      ->orWhere('defense', 'like', "%{$globalFilter}%")
                      ->orWhere('speed', 'like', "%{$globalFilter}%")
                      ->orWhereHas('player', function ($playerQuery) use ($globalFilter) {
                          $playerQuery->where('name', 'like', "%{$globalFilter}%");
                      })
                      ->orWhereHas('character', function ($characterQuery) use ($globalFilter) {
                          $characterQuery->where('name', 'like', "%{$globalFilter}%");
                      });
                });
            }

            $stats = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $stats->transform(function ($stat) {
                return [
                    'id' => $stat->id,
                    'player_id' => $stat->player_id,
                    'player_name' => $stat->player ? $stat->player->name : null,
                    'character_id' => $stat->character_id,
                    'character_name' => $stat->character ? $stat->character->name : null,
                    'hitpoints' => $stat->hitpoints,
                    'damage' => $stat->damage,
                    'defense' => $stat->defense,
                    'speed' => $stat->speed,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $stats->currentPage(),
                'last_page' => $stats->lastPage(),
                'next_page' => $stats->currentPage() < $stats->lastPage() ? $stats->currentPage() + 1 : null,
                'prev_page' => $stats->currentPage() > 1 ? $stats->currentPage() - 1 : null,
                'next_page_url' => $stats->nextPageUrl(),
                'prev_page_url' => $stats->previousPageUrl(),
                'per_page' => $stats->perPage(),
                'total' => $stats->total(),
                'data' => $stats->items(),
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
                'character_id' => 'required|integer|exists:hc_characters,id',
                'hitpoints' => 'nullable|integer|min:0',
                'damage' => 'nullable|integer|min:0',
                'defense' => 'nullable|integer|min:0',
                'speed' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }
            $CharacterPlayer = HdCharacterPlayer::where('inventory_id', $user->inventory_r_id)->where('character_id',$request->character_id)->first();
            if (!$CharacterPlayer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'your does not have this character',
                    'error_code' => 'WEAPON_NOT_FOUND',
                    ], 404);
            }
            // Cek apakah data sudah ada
            $stat = HrStatCharacterPlayer::where('player_id', $user->id)
                ->where('character_id', $request->character_id)
                ->first();

            if ($stat) {
                // Update data jika sudah ada
                $stat->update($request->only(['hitpoints', 'damage', 'defense', 'speed']));
            } else {
                // Buat data baru jika belum ada
                $stat = HrStatCharacterPlayer::create(array_merge(
                    $request->only(['character_id', 'hitpoints', 'damage', 'defense', 'speed']),
                    ['player_id' => $user->id]
                ));
            }

            return response()->json(['status' => 'success', 'data' => $stat], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function show($id)
    {
        try {
            $stat = HrStatCharacterPlayer::with(['player', 'character'])->find($id);

            if (!$stat) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Stat record not found.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $stat->id,
                    'player_id' => $stat->player_id,
                    'player_name' => $stat->player ? $stat->player->name : null,
                    'character_id' => $stat->character_id,
                    'character_name' => $stat->character ? $stat->character->name : null,
                    'hitpoints' => $stat->hitpoints,
                    'damage' => $stat->damage,
                    'defense' => $stat->defense,
                    'speed' => $stat->speed,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $stat = HrStatCharacterPlayer::find($id);

            if (!$stat) {
                return response()->json(['status' => 'error', 'message' => 'Stat record not found.'], 404);
            }

            $validator = Validator::make($request->all(), [
                'player_id' => 'nullable|integer|exists:players,id',
                'character_id' => 'nullable|integer|exists:characters,id',
                'hitpoints' => 'nullable|integer|min:0',
                'damage' => 'nullable|integer|min:0',
                'defense' => 'nullable|integer|min:0',
                'speed' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $stat->update($request->all());

            return response()->json(['status' => 'success', 'data' => $stat], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $stat = HrStatCharacterPlayer::find($id);

            if (!$stat) {
                return response()->json(['status' => 'error', 'message' => 'Stat record not found.'], 404);
            }

            $stat->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Stat record deleted successfully.',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function upgradeCharacter(Request $request)
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
                'character_id' => 'required|integer|exists:hc_characters,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }
            $CharacterPlayer = HdCharacterPlayer::where('inventory_id', $user->inventory_r_id)->where('character_id',$request->character_id)->with('character')->first();
            if (!$CharacterPlayer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'your does not have this character',
                    'error_code' => 'WEAPON_NOT_FOUND',
                    ], 404);
            }else{
                $nextLevel =$CharacterPlayer->level + 1;
                $upgrade = HdUpgradeCurrency::where('category','character')->where('character_id',$request->character_id)->where('level',$nextLevel)->first();
                if (!$upgrade) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'upgrade price not found.',
                    ], 404);
                }
                $walletGold = HdWallet::where('player_id', $user->id)->where('currency_id', 1)->sum('amount');
                if ($walletGold >= $upgrade->price) {
                    $CharacterPlayer->update([
                        'level' => $nextLevel
                    ]);
                    HdWallet::create([
                        'player_id'   => $user->id,
                        'currency_id' => 1,
                        'amount'      => $upgrade->price * -1, // Nilai amount dibuat negatif
                        'created_by'  => $user->id,
                        'modified_by' => $user->id,
                    ]);
                }else{
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Insufficient gold',
                    ], 400);
                }

            }

            return response()->json(['status' => 'success', 'data' => $CharacterPlayer], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
