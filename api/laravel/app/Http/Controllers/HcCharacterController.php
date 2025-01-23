<?php

namespace App\Http\Controllers;

use App\Models\HcCharacter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HcCharacterController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'name', 'assets_name', 'gender_character','character_role_id', 'point_price'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HcCharacter::with([
                'creator:id,name',
                'modifier:id,name',
                'role:id,role',
            ]);

            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('name', 'like', "%{$globalFilter}%")
                      ->orWhere('assets_name', 'like', "%{$globalFilter}%")
                      ->orWhere('gender_character', 'like', "%{$globalFilter}%");
                });
            }

            $characters = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            // Memodifikasi output untuk hanya menampilkan nama creator dan modifier
            $characters->getCollection()->transform(function ($character) {
                return [
                    'id' => $character->id,
                    'name' => $character->name,
                    'assets_name' => $character->assets_name,
                    'gender_character' => $character->gender_character,
                    'gender_character_string' => $character->gender_character == 0 ? 'male' : 'female',
                    'point_price' => $character->point_price,
                    'role_character' => $character->character_role_id,
                    'role_character_string' => $character->role ? $character->role->role : null,
                    'creator' => $character->creator ? $character->creator->name : null,
                    'modifier' => $character->modifier ? $character->modifier->name : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $characters->currentPage(),
                'last_page' => $characters->lastPage(),
                'next_page' => $characters->currentPage() < $characters->lastPage() ? $characters->currentPage() + 1 : null,
                'prev_page' => $characters->currentPage() > 1 ? $characters->currentPage() - 1 : null,
                'next_page_url' => $characters->nextPageUrl(),
                'prev_page_url' => $characters->previousPageUrl(),
                'per_page' => $characters->perPage(),
                'total' => $characters->total(),
                'data' => $characters->items(),
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
                'name' => 'required|string|max:255',
                'desc' => 'nullable|string',
                'assets_name' => 'required|string|max:255',
                'gender_character' => 'required|string|max:50',
                'point_price' => 'required|numeric',
                'character_role_id' => 'required|integer|exists:hc_character_roles,id',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation error', 'error_code' => 'VALIDATION_ERROR', 'errors' => $validator->errors()], 422);
            }
            $characterData = $request->all();
            $character = HcCharacter::create($characterData);

            return response()->json(['status' => 'success', 'data' => $character], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function show($id)
    {
        try {
            $character = HcCharacter::find($id);
            if(!$character){
                return response()->json([
                    'status' => 'error',
                    'message' => 'character not found.',
                ], 404);
            }


            return response()->json([
                'status' => 'success',
                'data' => $character,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $character = HcCharacter::find($id);

            if (!$character) {
                return response()->json(['status' => 'error', 'message' => 'Character not found', 'error_code' => 'NOT_FOUND'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'desc' => 'nullable|string',
                'assets_name' => 'nullable|string|max:255',
                'gender_character' => 'nullable|string|max:50',
                'point_price' => 'nullable|numeric',
                'character_role_id' => 'nullable|integer|exists:hc_character_roles,id',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors(),
                ], 422);
            }
            $characterData = $request->all();
            $character->update($characterData);

            return response()->json(['status' => 'success', 'data' => $character], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $character = HcCharacter::find($id);
            if(!$character){
                return response()->json([
                    'status' => 'error',
                    'message' => 'character not found.',
                ], 404);
            }
            $character->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Character deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
