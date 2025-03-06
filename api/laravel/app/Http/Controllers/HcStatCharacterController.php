<?php

namespace App\Http\Controllers;

use App\Models\HcStatCharacter;
use App\Models\HdUpgradeCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HcStatCharacterController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortField = $request->input('sortField', 'id');
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');

            $validSortFields = ['id', 'character_id', 'level_reach', 'hitpoints', 'damage', 'defense', 'speed', 'created_by', 'modified_by'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HcStatCharacter::with(['character', 'creator', 'modifier']);

            if ($globalFilter) {
                $query->whereHas('character', function ($q) use ($globalFilter) {
                    $q->where('name', 'like', "%{$globalFilter}%");
                });
            }

            $statCharacters = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $statCharacters->transform(function ($stat) {
                return [
                    'id' => $stat->id,
                    'character_id' => $stat->character_id,
                    'character_name' => $stat->character->name ?? null,
                    'level_reach' => $stat->level_reach,
                    'hitpoints' => $stat->hitpoints,
                    'damage' => $stat->damage,
                    'defense' => $stat->defense,
                    'speed' => $stat->speed,
                    'skills' => $stat->skills,
                    'creator' => $stat->creator ? $stat->creator->name : null,
                    'modifier' => $stat->modifier ? $stat->modifier->name : null,
                    'character'=>$stat->character
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $statCharacters->currentPage(),
                'last_page' => $statCharacters->lastPage(),
                'next_page' => $statCharacters->currentPage() < $statCharacters->lastPage() ? $statCharacters->currentPage() + 1 : null,
                'prev_page' => $statCharacters->currentPage() > 1 ? $statCharacters->currentPage() - 1 : null,
                'next_page_url' => $statCharacters->nextPageUrl(),
                'prev_page_url' => $statCharacters->previousPageUrl(),
                'per_page' => $statCharacters->perPage(),
                'total' => $statCharacters->total(),
                'data' => $statCharacters->items(),
                'params' => [
                    'pageSize' => $perPage,
                    'sortField' => $sortField,
                    'sortDirection' => $sortDirection,
                    'globalFilter' => $globalFilter,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'character_id' => 'required|exists:hc_characters,id',
                'level_reach' => 'required|integer|min:1',
                'hitpoints' => 'required|numeric|min:0|max:100',
                'damage' => 'required|numeric|min:0|max:100',
                'defense' => 'required|numeric|min:0|max:100',
                'speed' => 'required|numeric|min:0|max:100',
                'skills' => 'required|string|min:0|max:100',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
            }

            $statCharacter = HcStatCharacter::create($request->all());

            return response()->json(['status' => 'success', 'data' => $statCharacter], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function show($id)
    {
        try {
            $statCharacter = HcStatCharacter::with(['character', 'creator', 'modifier'])->find($id);
            if(!$statCharacter){
                return response()->json([
                    'status' => 'error',
                    'message' => 'stat weapon not found.',
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $statCharacter->id,
                    'character_id' => $statCharacter->character_id,
                    'character_name' => $statCharacter->character->name ?? null,
                    'level_reach' => $statCharacter->level_reach,
                    'hitpoints' => $statCharacter->hitpoints,
                    'damage' => $statCharacter->damage,
                    'defense' => $statCharacter->defense,
                    'speed' => $statCharacter->speed,
                    'skills' => $statCharacter->skils,
                    'creator' => $statCharacter->creator ? $statCharacter->creator->name : null,
                    'modifier' => $statCharacter->modifier ? $statCharacter->modifier->name : null,
                    'character'=>$statCharacter->character,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $statCharacter = HcStatCharacter::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'character_id' => 'nullable|exists:hc_characters,id',
                'level_reach' => 'nullable|integer|min:1',
                'hitpoints' => 'nullable|numeric|min:0|max:100',
                'damage' => 'nullable|numeric|min:0|max:100',
                'defense' => 'nullable|numeric|min:0|max:100',
                'speed' => 'nullable|numeric|min:0|max:100',
                'skills' => 'nullable|string|min:0|max:100',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $statCharacter->update($request->all());

            return response()->json(['status' => 'success', 'data' => $statCharacter], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $statCharacter = HcStatCharacter::find($id);
            if(!$statCharacter){
                return response()->json([
                    'status' => 'error',
                    'message' => 'stat character not found.',
                ], 404);
            }
            $statCharacter->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'stat character deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
