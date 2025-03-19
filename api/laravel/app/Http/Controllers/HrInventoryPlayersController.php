<?php

namespace App\Http\Controllers;

use App\Models\HrInventoryPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\HcLevel;
use App\Models\HcWeapon;
use App\Models\HcTypeWeapon;
use App\Models\Player;
use App\Models\HrLevelPlayer;
use App\Models\HdSkinCharacterPlayer;
use App\Models\HdWallet;
use App\Models\HrSkinWeapon;
use App\Models\HcStatWeapon;
use App\Models\HdSkinWeaponPlayer;
use App\Models\HcSubTypeWeapon;
use App\Models\HdWeaponPlayer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HrInventoryPlayersController extends Controller
{
    public function inventoryWeapon()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated.'
                ], 401);
            }

            // Ambil level player
            $level = HrLevelPlayer::where('hr_level_players.id', $user->level_r_id)
                ->leftJoin('hc_levels', 'hr_level_players.level_id', '=', 'hc_levels.id')
                ->select('hr_level_players.*', 'hc_levels.name as level_name')
                ->first();

            // Ambil inventory player
            $inventoryPlayer = HrInventoryPlayer::find($user->inventory_r_id);
            if (!$inventoryPlayer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Inventory not found.'
                ], 404);
            }

            // Ambil senjata yang dimiliki oleh pemain
            $weaponPlayer = HdWeaponPlayer::where('inventory_id', $user->inventory_r_id)
                ->select('weapon_id', 'level')
                ->get()
                ->keyBy('weapon_id')
                ->toArray();

            // Ambil tipe senjata beserta sub-tipe dan senjata
            $weapons = HcTypeWeapon::with(['subType.weapon'])->get();

            // Ambil semua skin yang dimiliki dan yang sedang digunakan oleh player
            $ownedSkins = HdSkinWeaponPlayer::where('inventory_id', $user->inventory_r_id)
                ->pluck('skin_id')
                ->toArray();

            $equippedSkins = HdSkinWeaponPlayer::where('inventory_id', $user->inventory_r_id)
                ->where('skin_equipped', true)
                ->pluck('skin_id')
                ->toArray();

            // Iterasi untuk menambahkan data tambahan ke weapon
            $weapons->each(function ($weaponType) use ($weaponPlayer, $inventoryPlayer, $ownedSkins, $equippedSkins) {
                $weaponType->subType->each(function ($subType) use ($weaponPlayer, $inventoryPlayer, $ownedSkins, $equippedSkins) {
                    $subType->weapon->each(function ($weapon) use ($weaponPlayer, $inventoryPlayer, $ownedSkins, $equippedSkins) {
                        // Apakah senjata sedang digunakan?
                        $weapon->used = in_array($weapon->id, [
                            $inventoryPlayer->weapon_primary_r_id,
                            $inventoryPlayer->weapon_secondary_r_id,
                            $inventoryPlayer->weapon_melee_r_id,
                            $inventoryPlayer->weapon_explosive_r_id
                        ]);

                        // Apakah senjata dimiliki?
                        $weapon->owned = isset($weaponPlayer[$weapon->id]);

                        // Ambil level weapon
                        $weaponLevel = $weaponPlayer[$weapon->id]['level'] ?? null;
                        $weapon->weapon_level = array_key_exists($weapon->id, $weaponPlayer) ? $weaponPlayer[$weapon->id]['level'] : 1;

                        // Ambil total statistik berdasarkan levelnya
                        if ($weaponLevel) {
                            $totalStats = HcStatWeapon::where('weapon_id', $weapon->id)
                                ->where('level_reach', '<=', $weaponLevel)
                                ->selectRaw('
                                    SUM(accuracy) as total_accuracy,
                                    SUM(damage) as total_damage,
                                    SUM(`range`) as total_range,
                                    SUM(fire_rate) as total_fire_rate
                                ')
                                ->first();

                            $weapon->total_current_stat_weapon = [
                                'accuracy' => (int)$totalStats->total_accuracy ?? 0,
                                'damage' => (int)$totalStats->total_damage ?? 0,
                                'range' => (int)$totalStats->total_range ?? 0,
                                'fire_rate' => (int)$totalStats->total_fire_rate ?? 0,
                            ];
                        } else {
                            $weapon->total_current_stat_weapon = [
                                'accuracy' => 0,
                                'damage' => 0,
                                'range' => 0,
                                'fire_rate' => 0,
                            ];
                        }

                        // Ambil daftar stat untuk setiap level senjata
                        $weapon->stat_level_weapons = HcStatWeapon::select(
                            'hc_stat_weapons.*',
                            DB::raw('COALESCE(CAST(hf_hd_upgrade_currencies.price AS DECIMAL(10,2)), 0.00) as price')
                        )
                        ->join('hd_upgrade_currencies', function ($join) {
                            $join->on('hc_stat_weapons.weapon_id', '=', 'hd_upgrade_currencies.weapon_id')
                                 ->on('hc_stat_weapons.level_reach', '=', 'hd_upgrade_currencies.level');
                        })
                        ->where('hc_stat_weapons.weapon_id', $weapon->id)
                        ->get()->map(function ($stat) {
                            $stat->price = (float) $stat->price; // Konversi ke float agar tidak dalam kutip
                            return $stat;
                        });

                        // Ambil daftar skin untuk setiap weapon
                        $weapon->skin_weapon = HrSkinWeapon::where('weapon_id', $weapon->id)->get()->map(function ($skin) use ($ownedSkins, $equippedSkins) {
                            return [
                                'id' => $skin->id,
                                'name_skin' => $skin->name_skin,
                                'code_skin' => $skin->code_skin,
                                'image_skin' => $skin->image_skin,
                                'level' => $skin->level_reach,
                                'owned' => in_array($skin->id, $ownedSkins),
                                'used' => in_array($skin->id, $equippedSkins),
                            ];
                        });
                    });
                });
            });

            return response()->json([
                'status' => 'success',
                'level' => $level,
                'type_weapons' => $weapons,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong.',
                'error_detail' => $e->getMessage(), // Bisa dihapus jika tidak ingin menampilkan detail error
            ], 500);
        }
    }
    public function shopWeapon()
    {
        try {
            $user = Auth::user();

            // Ambil informasi level user
            $level = HrLevelPlayer::where('hr_level_players.id', $user->level_r_id)
                ->leftJoin('hc_levels', 'hr_level_players.level_id', '=', 'hc_levels.id')
                ->select('hr_level_players.id', 'hr_level_players.level_id', 'hr_level_players.exp', 'hc_levels.name as level_name')
                ->first();

            if (!$level) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Player level not found.',
                ], 404);
            }

            // Ambil semua tipe senjata beserta sub-tipe dan senjata di dalamnya
            $weapons = HcTypeWeapon::with(['subType.weapon'])->get();

            // Ambil semua weapon_id yang dimiliki player
            $weaponPlayer = HdWeaponPlayer::where('inventory_id', $user->inventory_r_id)
                ->pluck('weapon_id')
                ->toArray();

            // Loop untuk menambahkan properti `locked` dan `owned`
            $weapons->each(function ($weaponType) use ($level, $weaponPlayer) {
                foreach ($weaponType->subType as $subType) {
                    foreach ($subType->weapon as $weapon) {
                        // Cek apakah weapon terkunci berdasarkan level
                        $weapon->locked = isset($weapon->level_reach) && $weapon->level_reach > $level->level_id;

                        // Cek apakah weapon sudah dimiliki oleh player
                        $weapon->owned = in_array($weapon->id, $weaponPlayer);
                    }
                }
            });

            return response()->json([
                'status' => 'success',
                'level' => $level,
                'type_weapon' => $weapons,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong.',
                'error_detail' => $e->getMessage(), // Bisa dihapus jika tidak ingin menampilkan error detail
            ], 500);
        }
    }
    public function purchaseWeapon(Request $request)
    {
        try {
            $user = Auth::user();
            $validator = Validator::make($request->all(), [
                'weapon_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }

            $weapon = HcWeapon::find($request->input('weapon_id'));

            if (!$weapon) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Weapon not found',
                ], 404);
            }

            // Check if user's level meets the weapon's required level
            $level = HrLevelPlayer::where('hr_level_players.id', $user->level_r_id)
                ->leftJoin('hc_levels', 'hr_level_players.level_id', '=', 'hc_levels.id')
                ->select('hr_level_players.level_id') // Assuming level_id is needed
                ->first();

            if ($level->level_id < $weapon->level_reach) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your level is too low to purchase this weapon',
                ], 403);
            }

            // Check if the player already owns the weapon
            $existingWeapon = HdWeaponPlayer::where('inventory_id', $user->inventory_r_id)
                ->where('weapon_id', $request->input('weapon_id'))
                ->first();

            if ($existingWeapon) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You already own this weapon',
                ], 409); // Conflict
            }

            $walletGold = HdWallet::where('player_id', $user->id)->where('currency_id', 1)->sum('amount');

            if ($walletGold >= $weapon->point_price) {
                HdWeaponPlayer::create([
                    'inventory_id' => $user->inventory_r_id,
                    'weapon_id' => $request->weapon_id,
                    'created_by' => $user->id,
                    'modified_by' => $user->id,
                ]);
                HdWallet::create([
                    'player_id'   => $user->id,
                    'currency_id' => 1,
                    'amount'      => $weapon->point_price * -1, // Nilai amount dibuat negatif
                    'created_by'  => $user->id,
                    'modified_by' => $user->id,
                ]);

            }else{
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient gold',
                ], 400);
            }



            return response()->json([
                'status' => 'success',
                'message' => 'Weapon purchased successfully',
                'data'=>$weapon
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function useWeapon(Request $request)
    {
        try {
            $user = Auth::user();
            $validator = Validator::make($request->all(), [
                'weapon_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }
            $inventory = HrInventoryPlayer::where('id', $user->inventory_r_id)->first();

            $weapon = HcWeapon::find($request->input('weapon_id'));

            if (!$weapon) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Weapon not found',
                ], 404);
            }


            $weapon_sub_type = HcSubTypeWeapon::find($weapon->weapon_r_sub_type);
            if (!$weapon_sub_type) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Weapon sub type not found',
                ], 404);
            }
            $weapon_type = HcTypeWeapon::find($weapon_sub_type->type_weapon_id);

            if (!$weapon_type) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Weapon type not found',
                ], 404);
            }
            // Check if the player already owns the weapon
            $existingWeapon = HdWeaponPlayer::where('inventory_id', $user->inventory_r_id)
                ->where('weapon_id', $request->input('weapon_id'))
                ->first();

            if ($existingWeapon) {
                if($weapon_type->id == 1){
                    $inventory->update([
                        'weapon_primary_r_id' => $request->weapon_id,
                    ]);
                } elseif($weapon_type->id == 2){
                    $inventory->update([
                        'weapon_secondary_r_id' => $request->weapon_id,
                    ]);
                }elseif($weapon_type->id == 3){
                    $inventory->update([
                        'weapon_melee_r_id' => $request->weapon_id,
                    ]);
                }elseif($weapon_type->id == 4){
                    $inventory->update([
                        'weapon_explosive_r_id' => $request->weapon_id,
                    ]);
                }else{
                    return response()->json([
                        'status' => 'error',
                        'message' => 'failed use weapon',
                    ], 400);
                }
            }else{
                return response()->json([
                    'status' => 'error',
                    'message' => 'You did not own this weapon',
                ], 400);
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Weapon used successfully',
                'data'=> $weapon
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }





}
