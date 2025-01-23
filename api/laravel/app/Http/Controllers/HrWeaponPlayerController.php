<?php

namespace App\Http\Controllers;

use App\Models\HrWeaponPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HrWeaponPlayerController extends Controller
{
    public function index()
    {
        try {
            $weaponPlayers = HrWeaponPlayer::all();

            return response()->json([
                'status' => 'success',
                'data' => $weaponPlayers,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'players_id' => 'required|integer',
                'weapons_id' => 'required|integer',
                'weapons_equipped' => 'required|integer',
                'weapons_status' => 'required|integer',
                'created_by' => 'nullable|string',
                'modified_by' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }

            $weaponPlayer = HrWeaponPlayer::create($request->all());

            return response()->json([
                'status' => 'success',
                'data' => $weaponPlayer,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $weaponPlayer = HrWeaponPlayer::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $weaponPlayer,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'players_id' => 'required|integer',
                'weapons_id' => 'required|integer',
                'weapons_equipped' => 'required|integer',
                'weapons_status' => 'required|integer',
                'created_by' => 'nullable|string',
                'modified_by' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }

            $weaponPlayer = HrWeaponPlayer::findOrFail($id);
            $weaponPlayer->update($request->all());

            return response()->json([
                'status' => 'success',
                'data' => $weaponPlayer,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $weaponPlayer = HrWeaponPlayer::findOrFail($id);
            $weaponPlayer->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Weapon Player deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
