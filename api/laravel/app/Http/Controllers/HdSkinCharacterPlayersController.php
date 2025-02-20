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
use Illuminate\Support\Facades\Auth;

class HdSkinCharacterPlayersController extends Controller
{
    public function inventorySkin()
    {
        try {
            $user = Auth::user();

            $inventoryPlayer = HrInventoryPlayer::where('id', $user->inventory_r_id)->first();

            $skinCharacterPlayer = HdSkinCharacterPlayer::where('inventory_id', $user->inventory_r_id)
                ->where('hd_skin_character_players.character_id', $inventoryPlayer->character_r_id)
                ->leftJoin('hr_skin_characters', 'hd_skin_character_players.skin_id', '=', 'hr_skin_characters.id')
                ->select('hd_skin_character_players.*', 'hr_skin_characters.name_skin as skin_character_name')
                ->get()
                ->map(function ($skin) use ($inventoryPlayer) {
                    // Menentukan apakah skin digunakan
                    $skin->used = ($skin->skin_id == $inventoryPlayer->skin_r_id);
                    return $skin;
                });



            return response()->json([
                'status' => 'success',
                'data' => $skinCharacterPlayer,
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
                HrInventoryPlayer::where('id', $user->inventory_r_id)
                ->update([
                    'skin_r_id' => $request->input('skin_id'), // Use the correct character_id from request
                ]);

            }else{
                return response()->json([
                    'status' => 'error',
                    'message' => 'You did not own this skin',
                ], 400);
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Skin used successfully',
                'data'=> $skin
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
