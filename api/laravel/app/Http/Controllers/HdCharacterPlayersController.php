<?php

namespace App\Http\Controllers;

use App\Models\HdCharacterPlayer;
use App\Models\HrInventoryPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\HcLevel;
use App\Models\HcCharacter;
use App\Models\HcStatCharacter;
use App\Models\HrSkinCharacter;
use App\Models\Player;
use App\Models\HrLevelPlayer;
use App\Models\HcCharacterRole;
use App\Models\HrStatCharacterPlayer;
use App\Models\HdSkinCharacterPlayer;
use App\Models\HdWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class HdCharacterPlayersController extends Controller
{
    public function inventoryCharacter()
    {
        try {
            $userId = Auth::id();
            if (!$userId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated.'
                ], 401);
            }

            $user = Auth::user();

            // Ambil inventory player
            $inventoryPlayer = HrInventoryPlayer::find($user->inventory_r_id);
            if (!$inventoryPlayer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Inventory not found.'
                ], 404);
            }

            // Ambil karakter yang dimiliki oleh pemain
            $characterPlayer = HdCharacterPlayer::where('inventory_id', $user->inventory_r_id)
                ->pluck('level', 'character_id'); // Key: character_id, Value: level

            // Ambil semua role karakter beserta karakter
            $characterRoles = HcCharacterRole::with('characters')->get();

            // Ambil statistik karakter yang dimiliki player
            $statCharacters = HcStatCharacter::whereIn('character_id', $characterPlayer->keys())->get()
                ->keyBy('character_id');

            // Ambil daftar skin yang dimiliki dan yang sedang digunakan
            $ownedSkins = HdSkinCharacterPlayer::where('inventory_id', $user->inventory_r_id)
                ->pluck('skin_id')->toArray();

            $equippedSkins = HdSkinCharacterPlayer::where('inventory_id', $user->inventory_r_id)
                ->where('skin_equipped', true)
                ->pluck('skin_id')->toArray();

            // Iterasi untuk menambahkan data tambahan ke karakter
            $characterRoles->each(function ($characterRole) use ($characterPlayer, $inventoryPlayer, $statCharacters, $ownedSkins, $equippedSkins) {
                $characterRole->characters->each(function ($character) use ($characterPlayer, $inventoryPlayer, $statCharacters, $ownedSkins, $equippedSkins) {
                    // Apakah karakter sedang digunakan?
                    $character->used = $character->id == $inventoryPlayer->character_r_id;

                    // Apakah karakter dimiliki?
                    $character->owned = $characterPlayer->has($character->id);

                    // Ambil level karakter
                    $characterLevel = $characterPlayer->get($character->id);
                    $character->character_level = $characterLevel ?? 1;

                    // Ambil statistik karakter jika tersedia
                    if ($characterLevel) {
                        $totalStats = HcStatCharacter::where('character_id', $character->id)
                            ->where('level_reach', '<=', $characterLevel)
                            ->selectRaw('
                                SUM(hitpoints) as total_hitpoints,
                                SUM(damage) as total_damage,
                                SUM(`defense`) as total_defense,
                                SUM(speed) as total_speed
                            ')
                            ->first();

                        $character->total_current_stat_character = [
                            'hitpoints' =>(int) $totalStats->total_hitpoints ?? 0,
                            'damage' =>(int)  $totalStats->total_damage ?? 0,
                            'defense' =>(int)  $totalStats->total_defense ?? 0,
                            'speed' =>(int)  $totalStats->total_speed ?? 0,
                        ];
                    } else {
                        $character->total_current_stat_character = [
                            'hitpoints' => 0,
                            'damage' => 0,
                            'defense' => 0,
                            'speed' => 0,
                        ];
                    }

                    // Ambil statistik level karakter
                    // $character->stat_level_characters = HcStatCharacter::where('character_id', $character->id)->get();
                    $character->stat_level_characters = HcStatCharacter::select(
                        'hc_stat_characters.*',
                        DB::raw('COALESCE(CAST(hf_hd_upgrade_currencies.price AS DECIMAL(10,2)), 0.00) as price')
                    )
                    ->join('hd_upgrade_currencies', function ($join) {
                        $join->on('hc_stat_characters.character_id', '=', 'hd_upgrade_currencies.character_id')
                             ->on('hc_stat_characters.level_reach', '=', 'hd_upgrade_currencies.level');
                    })
                    ->where('hc_stat_characters.character_id', $character->id)
                    ->get()
                    ->map(function ($stat) {
                        $stat->price = (float) $stat->price; // Konversi ke float agar tidak dalam kutip
                        return $stat;
                    });

                    // Ambil daftar skin untuk setiap karakter
                    $character->skin_character = HrSkinCharacter::where('character_id', $character->id)
                        ->get()
                        ->map(function ($skin) use ($ownedSkins, $equippedSkins) {
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

            return response()->json([
                'status' => 'success',
                'character_roles' => $characterRoles,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong.',
                'error_detail' => $e->getMessage(),
            ], 500);
        }
    }

    public function shopCharacter()
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

            // Ambil semua karakter berdasarkan perannya dan skin yang tersedia
            $characters = HcCharacterRole::with(['characters.skins'])->get();

            // Ambil semua character_id yang dimiliki player
            $characterPlayer = HdCharacterPlayer::where('inventory_id', $user->inventory_r_id)
                ->pluck('character_id')
                ->toArray();

            // Loop untuk menambahkan properti `locked` dan `owned`
            $characters->each(function ($characterRole) use ($level, $characterPlayer) {
                foreach ($characterRole->characters as $character) {
                    // Cek apakah karakter terkunci berdasarkan level
                    $character->locked = isset($character->level_reach) && $character->level_reach > $level->level_id;

                    // Cek apakah karakter sudah dimiliki oleh player
                    $character->owned = in_array($character->id, $characterPlayer);

                    // Loop untuk menambahkan informasi pada setiap skin karakter
                    $character->skins->each(function ($skin) use ($character) {
                        $skin->owned = $character->owned; // Misalnya, jika karakter dimiliki, skin otomatis dimiliki
                    });
                }
            });

            return response()->json([
                'status' => 'success',
                'level' => $level,
                'character_roles' => $characters,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong.',
                'error_detail' => $e->getMessage(), // Bisa dihapus jika tidak ingin menampilkan error detail
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
