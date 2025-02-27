<?php

namespace App\Http\Controllers;

use App\Models\HdSkinWeaponPlayer;
use App\Models\HdWeaponPlayers;
use App\Models\HrInventoryPlayer;
use App\Models\HdSkinCharacterPlayer;
use App\Models\HcTypeWeapon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\HcLevel;
use App\Models\HrSkinWeapon;
use App\Models\Player;
use App\Models\HrLevelPlayer;
use App\Models\HcWeapon;
use App\Models\HdWallet;
use App\Models\HdWeaponPlayer;

use Illuminate\Support\Facades\Auth;

class HdSkinWeaponPlayersController extends Controller
{
    public function inventorySkin()
    {
        try {
            $user = Auth::user();

            // Ambil inventory player
            $inventoryPlayer = HrInventoryPlayer::where('id', $user->inventory_r_id)->first();

            // Ambil semua tipe senjata beserta sub-tipe dan senjata di dalamnya
            $weapons = HcTypeWeapon::with(['subType.weapon'])->get();

            // Ambil semua skin yang dimiliki player dari tabel HdSkinWeaponPlayer
            $ownedSkins = HdSkinWeaponPlayer::where('inventory_id', $user->inventory_r_id)
                ->pluck('skin_id')
                ->toArray();

            // Ambil semua skin yang sedang digunakan (skin_equipped = true)
            $equippedSkins = HdSkinWeaponPlayer::where('inventory_id', $user->inventory_r_id)
                ->where('skin_equipped', true)
                ->pluck('skin_id')
                ->toArray();

            $weapons->each(function ($weaponType) use ($ownedSkins, $equippedSkins) {
                $weaponType->subType->each(function ($subType) use ($ownedSkins, $equippedSkins) {
                    $subType->weapon->each(function ($weapon) use ($ownedSkins, $equippedSkins) {
                        $weapon->skin_weapon = HrSkinWeapon::where('weapon_id', $weapon->id)->get()->map(function ($skin) use ($ownedSkins, $equippedSkins) {
                            return [
                                'id' => $skin->id,
                                'name_skin' => $skin->name_skin,
                                'code_skin' => $skin->code_skin,
                                'image_skin' => $skin->image_skin,
                                'level' => $skin->level_reach,
                                'owned' => in_array($skin->id, $ownedSkins), // Apakah skin dimiliki player?
                                'used' => in_array($skin->id, $equippedSkins), // Ambil dari skin_equipped di HdSkinWeaponPlayer
                            ];
                        });
                    });
                });
            });

            return response()->json([
                'status' => 'success',
                'data' => $weapons,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function shopSkin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'weapon_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }

            $user = Auth::user();
            $inventory = HrInventoryPlayer::where('id', $user->inventory_r_id)->first();
            if (!$inventory) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Inventory not found',
                ], 404);
            }

            // Ambil semua skin yang sudah dimiliki oleh player
            $skins = HrSkinWeapon::where('weapon_id', $request->weapon_id)->get();

            // Ambil semua skin yang sudah dimiliki oleh player berdasarkan daftar skin yang tersedia
            $ownedSkinIds = HdSkinWeaponPlayer::where('inventory_id', $user->inventory_r_id)
                ->whereIn('skin_id', $skins->pluck('id')) // Menggunakan whereIn untuk filter berdasarkan skin yang tersedia
                ->pluck('skin_id')
                ->toArray();
            // Ambil level senjata yang dimiliki player
            $weaponPlayer = HdWeaponPlayer::where('inventory_id', $user->inventory_r_id)
                ->where('weapon_id', $request->weapon_id)
                ->first();

            // Jika player tidak memiliki weapon ini, anggap level 0
            $playerWeaponLevel = $weaponPlayer->level ?? 0;

            // Tambahkan properti `owned` dan `locked` pada setiap skin
            $skins->each(function ($skin) use ($ownedSkinIds, $playerWeaponLevel) {
                // Cek apakah skin sudah dimiliki oleh player
                $skin->owned = in_array($skin->id, $ownedSkinIds);

                // Cek apakah level senjata cukup untuk membeli/menggunakan skin
                $skin->locked = $playerWeaponLevel < $skin->level_reach; // Jika level senjata kurang dari level skin, maka terkunci
            });

            return response()->json([
                'status' => 'success',
                'data' => $skins,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong.',
                'error_detail' => $e->getMessage(),
            ], 500);
        }
    }
    public function purchaseSkin(Request $request)
    {
        try {
            $user = Auth::user();
            $validator = Validator::make($request->all(), [
                'skin_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }

            $skin = HrSkinWeapon::find($request->input('skin_id'));

            if (!$skin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Skin not found',
                ], 404);
            }

            // Check if the player already owns the weapon
            $existingSkin = HdSkinWeaponPlayer::where('inventory_id', $user->inventory_r_id)
                ->where('skin_id', $request->input('skin_id'))
                ->first();

            if ($existingSkin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You already own this skin',
                ], 409); // Conflict
            }

            $walletGold = HdWallet::where('player_id', $user->id)->where('currency_id', 1)->sum('amount');
            $Weapon = HdWeaponPlayer::where('inventory_id', $user->inventory_r_id)
            ->where('weapon_id', $skin->weapon_id)
            ->first();
            if(!$Weapon){
                return response()->json([
                    'status' => 'error',
                    'message' => 'your does not have this weapon',
                    'error_code' => 'WEAPON_NOT_FOUND',
                    ], 404);
            }else{
            if($Weapon->level >= $skin->level_reach){
                if ($walletGold >= $skin->point_price) {

                    HdSkinWeaponPlayer::create([
                        'inventory_id' => $user->inventory_r_id,
                        'weapon_id' => $skin->weapon_id,
                        'skin_id' => $request->skin_id,
                        'created_by' => $user->id,
                        'modified_by' => $user->id,
                    ]);

                    HdWallet::create([
                        'player_id'   => $user->id,
                        'currency_id' => 1,
                        'amount'      => $skin->point_price * -1, // Nilai amount dibuat negatif
                        'created_by'  => $user->id,
                        'modified_by' => $user->id,
                    ]);
                }else{
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Insufficient gold',
                    ], 400);
                }
            }else{
                return response()->json([
                    'status' => 'error',
                    'message' => 'You must reach the level to unlock this skin',
                    ], 400);
            }
        }



            return response()->json([
                'status' => 'success',
                'message' => 'Skin purchased successfully',
                'data'=>$skin
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function useSkin(Request $request)
    {
        try {
                $user = Auth::user();
                $validator = Validator::make($request->all(), [
                    'skin_id' => 'required|integer',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $validator->errors(),
                    ], 422);
                }

                // Ambil data inventory player
                $inventory = HrInventoryPlayer::where('id', $user->inventory_r_id)->first();
                if (!$inventory) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Inventory not found',
                    ], 404);
                }

                // Cek apakah skin weapon ada
                $weaponSkin = HrSkinWeapon::find($request->input('skin_id'));
                if (!$weaponSkin) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Weapon skin not found',
                    ], 404);
                }

                // Cek apakah weapon terkait dengan skin ini ada
                $weapon = HcWeapon::find($weaponSkin->weapon_id);
                if (!$weapon) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Weapon not found',
                    ], 404);
                }

                // Cek apakah player memiliki skin ini
                $existingSkinWeapon = HdSkinWeaponPlayer::where('inventory_id', $user->inventory_r_id)
                    ->where('skin_id', $weaponSkin->id)
                    ->first();

                if (!$existingSkinWeapon) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Player does not own this skin',
                    ], 403);
                }

                // Cek apakah skin yang dipilih sudah dipakai
                if ($existingSkinWeapon->skin_equipped) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Skin is already equipped',
                    ], 200);
                }

                // Nonaktifkan skin yang sedang digunakan sebelumnya
                HdSkinWeaponPlayer::where('inventory_id', $user->inventory_r_id)
                    ->where('skin_equipped', true)
                    ->update(['skin_equipped' => false]);

                // Aktifkan skin yang baru dipilih
                $existingSkinWeapon->update([
                    'skin_equipped' => true,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Weapon skin equipped successfully',
                    'data' => $weaponSkin,
                ], 200);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Something went wrong.',
                    'error_detail' => $e->getMessage(),
                ], 500);
            }
    }
}
