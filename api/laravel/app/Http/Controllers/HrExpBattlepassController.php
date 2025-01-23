<?php

namespace App\Http\Controllers;

use App\Models\HrExpBattlepass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HrExpBattlepassController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'player_id', 'quest_battlepass_id', 'exp'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field',
                ], 400);
            }

            $query = HrExpBattlepass::with(['player', 'quest']);

            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('exp', 'like', "%{$globalFilter}%")
                      ->orWhereHas('player', function ($playerQuery) use ($globalFilter) {
                          $playerQuery->where('name', 'like', "%{$globalFilter}%");
                      })
                      ->orWhereHas('quest', function ($questQuery) use ($globalFilter) {
                          $questQuery->where('name_quest', 'like', "%{$globalFilter}%");
                      });
                });
            }

            $expData = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $expData->transform(function ($exp) {
                return [
                    'id' => $exp->id,
                    'player_id' => $exp->player_id,
                    'player_name' => $exp->player ? $exp->player->name : null,
                    'quest_id' => $exp->quest_battlepass_id,
                    'quest_name' => $exp->quest ? $exp->quest->name_quest : null,
                    'exp' => $exp->exp,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $expData->currentPage(),
                'last_page' => $expData->lastPage(),
                'next_page' => $expData->currentPage() < $expData->lastPage() ? $expData->currentPage() + 1 : null,
                'prev_page' => $expData->currentPage() > 1 ? $expData->currentPage() - 1 : null,
                'next_page_url' => $expData->nextPageUrl(),
                'prev_page_url' => $expData->previousPageUrl(),
                'per_page' => $expData->perPage(),
                'total' => $expData->total(),
                'data' => $expData->items(),
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
                'player_id' => 'required|integer|exists:hd_players,id',
                'quest_battlepass_id' => 'required|integer|exists:hc_quest_battlepass,id',
                'exp' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $expData = $request->all();

            $exp = HrExpBattlepass::create($expData);

            return response()->json(['status' => 'success', 'data' => $exp], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function show($id)
    {
        try {
            $exp = HrExpBattlepass::with(['player', 'quest'])->find($id);

            if (!$exp) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Experience record not found.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $exp->id,
                    'player_id' => $exp->player_id,
                    'player_name' => $exp->player ? $exp->player->name : null,
                    'quest_id' => $exp->quest_battlepass_id,
                    'quest_name' => $exp->quest ? $exp->quest->name_quest : null,
                    'exp' => $exp->exp,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $exp = HrExpBattlepass::find($id);

            if (!$exp) {
                return response()->json(['status' => 'error', 'message' => 'Experience record not found', 'error_code' => 'NOT_FOUND'], 404);
            }

            $validator = Validator::make($request->all(), [
                'player_id' => 'nullable|integer|exists:hd_players,id',
                'quest_battlepass_id' => 'nullable|integer|exists:hc_quest_battlepass,id',
                'exp' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $expData = $request->all();

            $exp->update($expData);

            return response()->json(['status' => 'success', 'data' => $exp], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $exp = HrExpBattlepass::find($id);

            if (!$exp) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Experience record not found.',
                ], 404);
            }

            $exp->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Experience record deleted successfully.',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
