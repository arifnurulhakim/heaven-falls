<?php

namespace App\Http\Controllers;

use App\Models\HdCharacterPlayer;
use App\Models\HrInventoryPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\HcLevel;
use App\Models\HcCharacter;
use App\Models\Player;
use App\Models\HrLevelPlayer;
use App\Models\HdWallet;

use Illuminate\Support\Facades\Auth;

class HdCharacterPlayersController extends Controller
{
    public function inventoryCharacter()
    {
        try {
            $user = Auth::user();

            // Mengambil data inventory pemain
            $inventoryPlayer = HrInventoryPlayer::where('id', $user->inventory_r_id)->first();

            // Mengambil data karakter pemain beserta informasi statistik dan peran
            $CharacterPlayer = HdCharacterPlayer::where('inventory_id', $user->inventory_r_id)
                ->leftJoin('hc_characters', 'hd_character_players.character_id', '=', 'hc_characters.id')
                ->leftJoin('hr_stat_character_players', function ($join) use ($user) {
                    $join->on('hc_characters.id', '=', 'hr_stat_character_players.character_id')
                        ->where('hr_stat_character_players.player_id', '=', $user->id);
                })
                ->leftJoin('hc_character_roles', 'hc_characters.character_role_id', '=', 'hc_character_roles.id')
                ->select(
                    'hd_character_players.id',
                    'hd_character_players.character_id',
                    'hd_character_players.inventory_id',
                    'hc_characters.name as character_name',
                    'hr_stat_character_players.hitpoints as stat_hitpoints',
                    'hr_stat_character_players.damage as stat_damage',
                    'hr_stat_character_players.defense as stat_defense',
                    'hr_stat_character_players.speed as stat_speed',
                    'hc_character_roles.id as role_id',
                    'hc_character_roles.role as role_name',
                    'hc_character_roles.hitpoints as role_hitpoints',
                    'hc_character_roles.damage as role_damage',
                    'hc_character_roles.defense as role_defense',
                    'hc_character_roles.speed as role_speed'
                )
                ->get()
                ->map(function ($character) use ($inventoryPlayer) {
                    // Menghitung total statistik sebagai penjumlahan dari role dan stat_upgraded
                    $totalHitpoints = ($character->role_hitpoints + $character->stat_hitpoints);
                    $totalDamage = ($character->role_damage + $character->stat_damage);
                    $totalDefense = ($character->role_defense + $character->stat_defense);
                    $totalSpeed = ($character->role_speed + $character->stat_speed);

                    return [
                        'character_id' => $character->character_id,
                        'inventory_id' => $character->inventory_id,
                        'character_name' => $character->character_name,
                        'used' => $character->character_id == $inventoryPlayer->character_r_id,
                        'role' => [
                            'role_id' => $character->role_id,
                            'role_name' => $character->role_name,
                                'hitpoints' => $character->role_hitpoints,
                                'damage' => $character->role_damage,
                                'defense' => $character->role_defense,
                                'speed' => $character->role_speed,
                        ],
                        'stat_upgraded' => [
                            'hitpoints' => $character->stat_hitpoints,
                            'damage' => $character->stat_damage,
                            'defense' => $character->stat_defense,
                            'speed' => $character->stat_speed,
                        ],
                        // Menambahkan total_stat untuk setiap karakter
                        'total_stat' => [
                            'hitpoints' => $totalHitpoints,
                            'damage' => $totalDamage,
                            'defense' => $totalDefense,
                            'speed' => $totalSpeed,
                        ]
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $CharacterPlayer
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function shopCharacter()
    {
        try {
            $user = Auth::user();
            $level = HrLevelPlayer::where('hr_level_players.id', $user->level_r_id)
                ->leftJoin('hc_levels', 'hr_level_players.level_id', '=', 'hc_levels.id')
                ->select('hr_level_players.*', 'hc_levels.name as level_name')
                ->first();

            $inventory = HrInventoryPlayer::where('id',$user->inventory_r_id)->first();
            $characters = HcCharacter::all();
            $characterPlayer = HdCharacterPlayer::where('inventory_id', $user->inventory_r_id)->pluck('character_id')->toArray();

            foreach ($characters as $character) {
                $character->locked = $character->level_reach > $level->level_id;
                $character->sell = !in_array($character->id, $characterPlayer);
                $character->used = false;
                if($character->id == $inventory->character_r_id){
                    $character->used = true;
                }
            }

            return response()->json([
                'status' => 'success',
                'level' => $level,
                'characters' => $characters,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function purchaseCharacter(Request $request)
    {
        try {
            $user = Auth::user();
            $validator = Validator::make($request->all(), [
                'character_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }

            $character = HcCharacter::find($request->input('character_id'));

            if (!$character) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Character not found',
                ], 404);
            }

            // Check if user's level meets the character's required level
            $level = HrLevelPlayer::where('hr_level_players.id', $user->level_r_id)
                ->leftJoin('hc_levels', 'hr_level_players.level_id', '=', 'hc_levels.id')
                ->select('hr_level_players.level_id') // Assuming level_id is needed
                ->first();

            if ($level->level_id < $character->level_reach) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your level is too low to purchase this character',
                ], 403);
            }

            // Check if the player already owns the character
            $existingCharacter = HdCharacterPlayer::where('inventory_id', $user->inventory_r_id)
                ->where('character_id', $request->input('character_id'))
                ->first();

            if ($existingCharacter) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You already own this character',
                ], 409); // Conflict
            }

            $walletGold = HdWallet::where('player_id', $user->id)->where('currency_id', 1)->sum('amount');

            if ($walletGold >= $character->point_price) {
                HdCharacterPlayer::create([
                    'inventory_id' => $user->inventory_r_id,
                    'character_id' => $request->character_id,
                    'created_by' => $user->id,
                    'modified_by' => $user->id,
                ]);

                HdWallet::create([
                    'player_id'   => $user->id,
                    'currency_id' => 1,
                    'amount'      => $character->point_price * -1, // Nilai amount dibuat negatif
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
                'message' => 'Character purchased successfully',
                'data'=>$character
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function useCharacter(Request $request)
    {
        try {
            $user = Auth::user();

            // Validate the request
            $validator = Validator::make($request->all(), [
                'character_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }

            // Find the character
            $character = HcCharacter::find($request->input('character_id'));

            if (!$character) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Character not found',
                ], 404);
            }

            // Check if the player already owns the character
            $existingCharacter = HdCharacterPlayer::where('inventory_id', $user->inventory_r_id)
                ->where('character_id', $request->input('character_id'))
                ->first();

            if ($existingCharacter) {
                // Update the player's character in the inventory
                HrInventoryPlayer::where('id', $user->inventory_r_id)
                    ->update([
                        'character_r_id' => $request->input('character_id'), // Use the correct character_id from request
                    ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Character used successfully',
                    'data' => $character
                ], 200);

            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not own this character',
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
