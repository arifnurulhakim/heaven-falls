<?php

namespace App\Http\Controllers;

use App\Models\HcCharacterPlayers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HcCharacterPlayersController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'character_name', 'player_name', 'inventory_name'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HcCharacterPlayers::with(['inventory:id,name', 'character:id,name']);

            if ($globalFilter) {
                $query->whereHas('character', function ($q) use ($globalFilter) {
                    $q->where('name', 'like', "%{$globalFilter}%");
                })->orWhereHas('inventory', function ($q) use ($globalFilter) {
                    $q->where('name', 'like', "%{$globalFilter}%");
                })->orWhere('player_name', 'like', "%{$globalFilter}%");
            }

            $characterPlayers = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $characterPlayers->getCollection()->transform(function ($characterPlayer) {
                return [
                    'id' => $characterPlayer->id,
                    'character_name' => $characterPlayer->character ? $characterPlayer->character->name : null,
                    'inventory_name' => $characterPlayer->inventory ? $characterPlayer->inventory->name : null,
                    'player_name' => $characterPlayer->player_name,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $characterPlayers->currentPage(),
                'last_page' => $characterPlayers->lastPage(),
                'next_page' => $characterPlayers->currentPage() < $characterPlayers->lastPage() ? $characterPlayers->currentPage() + 1 : null,
                'prev_page' => $characterPlayers->currentPage() > 1 ? $characterPlayers->currentPage() - 1 : null,
                'next_page_url' => $characterPlayers->nextPageUrl(),
                'prev_page_url' => $characterPlayers->previousPageUrl(),
                'per_page' => $characterPlayers->perPage(),
                'total' => $characterPlayers->total(),
                'data' => $characterPlayers->items(),
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
                'character_id' => 'required|integer',
                'player_id' => 'required|integer',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }

            $characterPlayer = HcCharacterPlayers::create($request->all());

            return response()->json([
                'status' => 'success',
                'data' => $characterPlayer,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $characterPlayer = HcCharacterPlayers::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $characterPlayer,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'character_id' => 'required|integer',
                'player_id' => 'required|integer',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }

            $characterPlayer = HcCharacterPlayers::findOrFail($id);
            $characterPlayer->update($request->all());

            return response()->json([
                'status' => 'success',
                'data' => $characterPlayer,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $characterPlayer = HcCharacterPlayers::findOrFail($id);
            $characterPlayer->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Character Player deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
