<?php

namespace App\Http\Controllers;

use App\Models\HdSkinCharacterPlayer;
use App\Models\HdCharacterPlayers;
use App\Models\HrInventoryPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\HcLevel;
use App\Models\HrSkinCharacter;
use App\Models\Player;
use App\Models\HrLevelPlayer;
use App\Models\HdWallet;
use App\Models\HdCharacterPlayer;
use App\Models\HcCharacterRole;
use App\Models\HcCharacter;
use Illuminate\Support\Facades\Auth;

class HdSkinCharacterPlayersController extends Controller
{
    public function inventorySkin()
    {
        try {
            $user = Auth::user();

            // Ambil inventory player
            $inventoryPlayer = HrInventoryPlayer::where('id', $user->inventory_r_id)->first();

            // Ambil semua karakter berdasarkan peran
            $characterRoles = HcCharacterRole::with(['characters'])->get();

            // Ambil semua skin yang dimiliki player dari tabel HdSkinCharacterPlayer
            $ownedSkins = HdSkinCharacterPlayer::where('inventory_id', $user->inventory_r_id)
                ->pluck('skin_id')
                ->toArray();

            // Ambil semua skin yang sedang digunakan (skin_equipped = true)
            $equippedSkins = HdSkinCharacterPlayer::where('inventory_id', $user->inventory_r_id)
                ->where('skin_equipped', true)
                ->pluck('skin_id')
                ->toArray();

            $characterRoles->each(function ($role) use ($ownedSkins, $equippedSkins) {
                $role->characters->each(function ($character) use ($ownedSkins, $equippedSkins) {
                    $character->skin_character = HrSkinCharacter::where('character_id', $character->id)->get()->map(function ($skin) use ($ownedSkins, $equippedSkins) {
                        return [
                            'id' => $skin->id,
                            'name_skin' => $skin->name_skin,
                            'code_skin' => $skin->code_skin,
                            'image_skin' => $skin->image_skin,
                            'level' => $skin->level_reach,
                            'owned' => in_array($skin->id, $ownedSkins), // Apakah skin dimiliki player?
                            'used' => in_array($skin->id, $equippedSkins), // Apakah skin sedang digunakan?
                        ];
                    });
                });
            });

            return response()->json([
                'status' => 'success',
                'data' => $characterRoles,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function shopSkin()
    {
        try {
            $user = Auth::user();


            $inventory = HrInventoryPlayer::where('id',$user->inventory_r_id)->first();
            $skins = HrSkinCharacter::where('character_id',$inventory->character_r_id)->get();


            $skinCharacterPlayer = HdSkinCharacterPlayer::where('inventory_id', $user->inventory_r_id)->where('character_id',$inventory->character_r_id)->pluck('skin_id')->toArray();

            foreach ($skins as $skin) {
                $skin->sell = !in_array($skin->id, $skinCharacterPlayer);
                $skin->used = false;
                if($skin->id == $inventory->skin_r_id){
                    $skin->used = true;
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $skins,
            ], 200);
        } catch (\Exception $e) {
            dd($e);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function shopSkinAll()
    {
        try {
            $user = Auth::user();


            $inventory = HrInventoryPlayer::where('id',$user->inventory_r_id)->first();
            $skins = HrSkinCharacter::with('character')->get();


            $skinCharacterPlayer = HdSkinCharacterPlayer::where('inventory_id', $user->inventory_r_id)->pluck('skin_id')->toArray();

            foreach ($skins as $skin) {
                $skin->sell = !in_array($skin->id, $skinCharacterPlayer);
                $skin->used = false;
                if($skin->id == $inventory->skin_r_id){
                    $skin->used = true;
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $skins,
            ], 200);
        } catch (\Exception $e) {
            dd($e);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
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

            $skin = HrSkinCharacter::find($request->input('skin_id'));

            if (!$skin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Skin not found',
                ], 404);
            }

            // Check if the player already owns the character
            $existingSkin = HdSkinCharacterPlayer::where('inventory_id', $user->inventory_r_id)
                ->where('skin_id', $request->input('skin_id'))
                ->first();

            if ($existingSkin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You already own this skin',
                ], 409); // Conflict
            }

            $walletGold = HdWallet::where('player_id', $user->id)->where('currency_id', 1)->sum('amount');

            if ($walletGold >= $skin->point_price) {
                HdSkinCharacterPlayer::create([
                    'inventory_id' => $user->inventory_r_id,
                    'character_id' => $skin->character_id,
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

            // Cek apakah skin karakter ada
            $characterSkin = HrSkinCharacter::find($request->input('skin_id'));
            if (!$characterSkin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Character skin not found',
                ], 404);
            }

            // Cek apakah karakter terkait dengan skin ini ada
            $character = HcCharacter::find($characterSkin->character_id);
            if (!$character) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Character not found',
                ], 404);
            }

            // Cek apakah player memiliki skin ini
            $existingSkinCharacter = HdSkinCharacterPlayer::where('inventory_id', $user->inventory_r_id)
                ->where('skin_id', $characterSkin->id)
                ->first();

            if (!$existingSkinCharacter) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Player does not own this skin',
                ], 403);
            }

            // Cek apakah skin yang dipilih sudah dipakai
            if ($existingSkinCharacter->skin_equipped) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Skin is already equipped',
                ], 200);
            }

            // Nonaktifkan skin yang sedang digunakan sebelumnya
            HdSkinCharacterPlayer::where('inventory_id', $user->inventory_r_id)
                ->where('skin_equipped', true)
                ->update(['skin_equipped' => false]);

            // Aktifkan skin yang baru dipilih
            $existingSkinCharacter->update([
                'skin_equipped' => true,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Character skin equipped successfully',
                'data' => $characterSkin,
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
