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
use App\Models\HdWallet;
use App\Models\HdWeaponPlayer;
use Illuminate\Support\Facades\Auth;

class HrInventoryPlayersController extends Controller
{
    public function inventoryWeapon()
    {
        try {
            $user = Auth::user();

            // Mendapatkan data level player dengan join ke tabel hc_levels
            $level = HrLevelPlayer::where('hr_level_players.id', $user->level_r_id)
                ->leftJoin('hc_levels', 'hr_level_players.level_id', '=', 'hc_levels.id')
                ->select('hr_level_players.*', 'hc_levels.name as level_name')
                ->first();

            // Mendapatkan data inventory player
            $inventoryPlayer = HrInventoryPlayer::where('id', $user->inventory_r_id)->first();

            // Mendapatkan data weapon player dengan join ke tabel hc_weapons dan hc_type_weapons
            $weaponPlayer = HdWeaponPlayer::where('inventory_id', $user->inventory_r_id)
                ->leftJoin('hc_weapons', 'hd_weapon_players.weapon_id', '=', 'hc_weapons.id')
                ->leftJoin('hc_type_weapons', 'hc_weapons.weapon_r_type', '=', 'hc_type_weapons.id')
                ->select('hd_weapon_players.*', 'hc_weapons.name_weapons', 'hc_type_weapons.name as type_weapons')
                ->get()
                ->map(function ($weapon) use ($inventoryPlayer) {
                    // Tentukan apakah weapon ini digunakan berdasarkan kolom di inventory
                    $weapon->used = (
                        $weapon->weapon_id == $inventoryPlayer->weapon_primary_r_id ||
                        $weapon->weapon_id == $inventoryPlayer->weapon_secondary_r_id ||
                        $weapon->weapon_id == $inventoryPlayer->weapon_melee_r_id ||
                        $weapon->weapon_id == $inventoryPlayer->weapon_explosive_r_id
                    );
                    return $weapon;
                });

            return response()->json([
                'status' => 'success',
                'level' => $level,
                'weapons' => $weaponPlayer,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function shopWeapon()
    {
        try {
            $user = Auth::user();
            $level = HrLevelPlayer::where('hr_level_players.id', $user->level_r_id)
                ->leftJoin('hc_levels', 'hr_level_players.level_id', '=', 'hc_levels.id')
                ->select('hr_level_players.id', 'hr_level_players.level_id','hr_level_players.exp',  'hc_levels.name as level_name')
                ->first();

                $weapons = HcWeapon::with('type')->get();
            $weaponPlayer = HdWeaponPlayer::where('inventory_id', $user->inventory_r_id)->pluck('weapon_id')->toArray();

            foreach ($weapons as $weapon) {
                $weapon->isLock = $weapon->level_reach > $level->level_id;
                $weapon->sell = !in_array($weapon->id, $weaponPlayer);
            }

            return response()->json([
                'status' => 'success',
                'level' => $level,
                'weapons' => $weapons,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
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
            $weapon_type = HcTypeWeapon::find($weapon->weapon_r_type);

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
