<?php

namespace App\Http\Controllers;

use App\Models\HcCharacterRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HcCharacterRoleController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'role', 'hitpoints', 'damage', 'defense', 'speed'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HcCharacterRole::with(['creator', 'modifier']);

            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('role', 'like', "%{$globalFilter}%")
                      ->orWhere('hitpoints', 'like', "%{$globalFilter}%")
                      ->orWhere('damage', 'like', "%{$globalFilter}%")
                      ->orWhere('defense', 'like', "%{$globalFilter}%")
                      ->orWhere('skills', 'like', "%{$globalFilter}%")
                      ->orWhere('speed', 'like', "%{$globalFilter}%");
                });
            }

            $roles = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            // Transform data to include only names for creator and modifier
            $roles->transform(function ($role) {
                return [
                    'id' => $role->id,
                    'role' => $role->role,
                    'hitpoints' => $role->hitpoints,
                    'damage' => $role->damage,
                    'defense' => $role->defense,
                    'speed' => $role->speed,
                    'skills' => $role->skills,
                    'creator' => $role->creator ? $role->creator->name : null,
                    'modifier' => $role->modifier ? $role->modifier->name : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'next_page' => $roles->currentPage() < $roles->lastPage() ? $roles->currentPage() + 1 : null,
                'prev_page' => $roles->currentPage() > 1 ? $roles->currentPage() - 1 : null,
                'next_page_url' => $roles->nextPageUrl(),
                'prev_page_url' => $roles->previousPageUrl(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
                'data' => $roles->items(),
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
            'role' => 'required|string|max:255|unique:hc_character_roles,role',
            'hitpoints' => 'required|integer',
            'damage' => 'required|integer',
            'defense' => 'required|integer',
            'speed' => 'required|integer',
            'skills' => 'nullable|string',
            'created_by' => 'nullable|integer',
            'modified_by' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation error', 'error_code' => 'VALIDATION_ERROR', 'errors' => $validator->errors()], 422);
        }

        $roleData = $request->all();

        $role = HcCharacterRole::create($roleData);

        return response()->json(['status' => 'success', 'data' => $role], 201);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
    }
}



    public function show($id)
    {
        try {
            $characterRole = HcCharacterRole::find($id);
            if(!$characterRole){
                return response()->json([
                    'status' => 'error',
                    'message' => 'character role not found.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $characterRole,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $role = HcCharacterRole::find($id);

            if (!$role) {
                return response()->json(['status' => 'error', 'message' => 'Character role not found', 'error_code' => 'NOT_FOUND'], 404);
            }
            if (empty($request->all())) {
                return response()->json([  'status' => 'error','message' => 'body is empty'], 400);
            }


            $validator = Validator::make($request->all(), [
                'role' => [
                    'required', 'string', 'max:255',
                    Rule::unique('hc_character_roles', 'role')->ignore($id),
                ],
                'hitpoints' => 'nullable|integer',
                'damage' => 'nullable|integer',
                'defense' => 'nullable|integer',
                'speed' => 'nullable|integer',
                'skills' => 'nullable|string',
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

            $roleData = $request->all();

            $role->update($roleData);

            return response()->json(['status' => 'success', 'data' => $role], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }


    public function destroy($id)
    {
        try {
            $characterRole = HcCharacterRole::find($id);

            if(!$characterRole){
                return response()->json([
                    'status' => 'error',
                    'message' => 'character role not found.',
                ], 404);
            }
            $characterRole->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Character deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
