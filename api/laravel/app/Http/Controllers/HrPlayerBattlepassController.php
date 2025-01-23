<?php

namespace App\Http\Controllers;

use App\Models\HrPlayerBattlepass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HrPlayerBattlepassController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'battlepass_id', 'player_id', 'status_claimed'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HrPlayerBattlepass::with(['battlepass', 'player']);

            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->whereHas('battlepass', function ($q) use ($globalFilter) {
                        $q->where('level_battlepass', 'like', "%{$globalFilter}%");
                    })->orWhereHas('player', function ($q) use ($globalFilter) {
                        $q->where('name', 'like', "%{$globalFilter}%");
                    });
                });
            }

            $playerBattlepasses = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $playerBattlepasses->transform(function ($playerBattlepass) {
                return [
                    'id' => $playerBattlepass->id,
                    'battlepass' => $playerBattlepass->battlepass ? $playerBattlepass->battlepass->level_battlepass : null,
                    'player' => $playerBattlepass->player ? $playerBattlepass->player->name : null,
                    'status_claimed' => $playerBattlepass->status_claimed,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $playerBattlepasses->currentPage(),
                'last_page' => $playerBattlepasses->lastPage(),
                'next_page' => $playerBattlepasses->currentPage() < $playerBattlepasses->lastPage() ? $playerBattlepasses->currentPage() + 1 : null,
                'prev_page' => $playerBattlepasses->currentPage() > 1 ? $playerBattlepasses->currentPage() - 1 : null,
                'next_page_url' => $playerBattlepasses->nextPageUrl(),
                'prev_page_url' => $playerBattlepasses->previousPageUrl(),
                'per_page' => $playerBattlepasses->perPage(),
                'total' => $playerBattlepasses->total(),
                'data' => $playerBattlepasses->items(),
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
            $validator = Validator::make($request->all(), [
                'battlepass_id' => 'required|exists:hd_battlepass,id',
                'player_id' => 'required|exists:hd_players,id',
                'status_claimed' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors()
                ], 422);
            }

            $playerBattlepass = HrPlayerBattlepass::create($request->all());

            return response()->json(['status' => 'success', 'data' => $playerBattlepass], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function show($id)
    {
        try {
            $playerBattlepass = HrPlayerBattlepass::with(['battlepass', 'player'])->find($id);

            if (!$playerBattlepass) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Player Battlepass not found.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $playerBattlepass,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $playerBattlepass = HrPlayerBattlepass::find($id);

            if (!$playerBattlepass) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Player Battlepass not found.',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'battlepass_id' => 'nullable|exists:hd_battlepass,id',
                'player_id' => 'nullable|exists:hd_players,id',
                'status_claimed' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $playerBattlepass->update($request->all());

            return response()->json([
                'status' => 'success',
                'data' => $playerBattlepass,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $playerBattlepass = HrPlayerBattlepass::find($id);

            if (!$playerBattlepass) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Player Battlepass not found.',
                ], 404);
            }

            $playerBattlepass->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Player Battlepass deleted successfully.',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
