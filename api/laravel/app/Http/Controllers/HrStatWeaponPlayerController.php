<?php

namespace App\Http\Controllers;

use App\Models\HrStatWeaponPlayer;
use App\Models\HdWeaponPlayer;
use App\Models\HdUpgradeCurrency;
use App\Models\HdWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
class HrStatWeaponPlayerController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'level', 'player_id', 'weapon_id', 'accuracy', 'damage', 'range', 'fire_rate'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field',
                ], 400);
            }

            $query = PHrStatWeaponPlayer::with(['player', 'weapon']);

            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {

                      $q->orWhereHas('player', function ($playerQuery) use ($globalFilter) {
                          $playerQuery->where('name', 'like', "%{$globalFilter}%");
                      })
                      ->orWhereHas('weapon', function ($weaponQuery) use ($globalFilter) {
                          $weaponQuery->where('name', 'like', "%{$globalFilter}%");
                      });
                });
            }

            $stats = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $stats->transform(function ($stat) {
                return [
                    'id' => $stat->id,
                    'player_id' => $stat->player_id,
                    'player_name' => $stat->player ? $stat->player->name : null,
                    'weapon_id' => $stat->weapon_id,
                    'weapon_name' => $stat->weapon ? $stat->weapon->name : null,
                    'accuracy' => $stat->accuracy,
                    'damage' => $stat->damage,
                    'range' => $stat->range,
                    'fire_rate' => $stat->fire_rate,
                    'weapon'=>$stats->weapon,
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
                'weapon_id' => 'required|integer|exists:hc_weapons,id',
                'accuracy' => 'nullable|integer|min:0',
                'damage' => 'nullable|integer|min:0',
                'range' => 'nullable|integer|min:0',
                'fire_rate' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }
            $WeaponPlayer = HdWeaponPlayer::where('inventory_id', $user->inventory_r_id)->where('weapon_id',$request->weapon_id)->first();
            if (!$WeaponPlayer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'your does not have this weapon',
                    'error_code' => 'WEAPON_NOT_FOUND',
                    ], 404);
            }
            // Cek apakah data sudah ada
            $stat = HrStatWeaponPlayer::where('player_id', $user->id)
                ->where('weapon_id', $request->weapon_id)
                ->first();

            if ($stat) {
                // Update data jika sudah ada
                $stat->update($request->only(['accuracy', 'damage', 'range', 'fire_rate']));
            } else {
                // Buat data baru jika belum ada
                $stat = HrStatWeaponPlayer::create(array_merge(
                    $request->only(['weapon_id', 'accuracy', 'damage', 'range', 'fire_rate']),
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
            $stat = HrStatWeaponPlayer::with(['player', 'weapon'])->find($id);

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
                    'weapon_id' => $stat->weapon_id,
                    'weapon_name' => $stat->weapon ? $stat->weapon->name : null,
                    'accuracy' => $stat->accuracy,
                    'damage' => $stat->damage,
                    'range' => $stat->range,
                    'fire_rate' => $stat->fire_rate,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $stat = HrStatWeaponPlayer::find($id);

            if (!$stat) {
                return response()->json(['status' => 'error', 'message' => 'Stat record not found.'], 404);
            }

            $validator = Validator::make($request->all(), [
                'player_id' => 'nullable|integer|exists:players,id',
                'weapon_id' => 'nullable|integer|exists:weapons,id',
                'accuracy' => 'nullable|integer|min:0',
                'damage' => 'nullable|integer|min:0',
                'range' => 'nullable|integer|min:0',
                'fire_rate' => 'nullable|integer|min:0',
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
            $stat = HrStatWeaponPlayer::find($id);

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

    public function upgradeWeapon(Request $request)
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
                'weapon_id' => 'required|integer|exists:hc_weapons,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }
            $WeaponPlayer = HdWeaponPlayer::where('inventory_id', $user->inventory_r_id)->where('weapon_id',$request->weapon_id)->with('weapon')->first();
            if (!$WeaponPlayer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'your does not have this weapon',
                    'error_code' => 'WEAPON_NOT_FOUND',
                    ], 404);
            }
                else{
                    $nextLevel =$WeaponPlayer->level + 1;
                    $upgrade = HdUpgradeCurrency::where('category','weapon')->where('weapon_id',$request->weapon_id)->where('level',$nextLevel)->first();
                    if (!$upgrade) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'upgrade price not found.',
                        ], 404);
                    }
                    $walletGold = HdWallet::where('player_id', $user->id)->where('currency_id', 1)->sum('amount');
                    if ($walletGold >= $upgrade->price) {
                        $WeaponPlayer->update([
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


            return response()->json(['status' => 'success', 'data' => $WeaponPlayer], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
