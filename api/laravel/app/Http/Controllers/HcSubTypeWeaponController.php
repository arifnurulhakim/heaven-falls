<?php

namespace App\Http\Controllers;

use App\Models\HcSubTypeWeapon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HcSubTypeWeaponController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortField = $request->input('sortField', 'name');
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');

            $validSortFields = ['id', 'name', 'created_by', 'modified_by'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HcSubTypeWeapon::with(['subTypeWeapon','creator', 'modifier']);

            if ($globalFilter) {
                $query->where('name', 'like', "%{$globalFilter}%");
            }

            $subTypeWeapons = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $subTypeWeapons->transform(function ($weapon) {
                return [
                    'id' => $weapon->id,
                    'name' => $weapon->name,
                    'type_weapon_id' => $weapon->type_weapon_id,
                    'type_weapon_name' => $weapon->subTypeWeapon->name ?? null,
                    'creator' => $weapon->creator ? $weapon->creator->name : null,
                    'modifier' => $weapon->modifier ? $weapon->modifier->name : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $subTypeWeapons->currentPage(),
                'last_page' => $subTypeWeapons->lastPage(),
                'next_page' => $subTypeWeapons->currentPage() < $subTypeWeapons->lastPage() ? $subTypeWeapons->currentPage() + 1 : null,
                'prev_page' => $subTypeWeapons->currentPage() > 1 ? $subTypeWeapons->currentPage() - 1 : null,
                'next_page_url' => $subTypeWeapons->nextPageUrl(),
                'prev_page_url' => $subTypeWeapons->previousPageUrl(),
                'per_page' => $subTypeWeapons->perPage(),
                'total' => $subTypeWeapons->total(),
                'data' => $subTypeWeapons->items(),
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
                'type_weapon_id' => 'required|exists:hc_type_weapons,id',
                'name' => 'required|string|max:255',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation error', 'error_code' => 'VALIDATION_ERROR', 'errors' => $validator->errors()], 422);
            }

            $subTypeWeaponData = $request->all();

            $subTypeWeapon = HcSubTypeWeapon::create($subTypeWeaponData);

            return response()->json(['status' => 'success', 'data' => $subTypeWeapon], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function show($id)
    {
        try {
            $subTypeWeapon = HcSubTypeWeapon::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $subTypeWeapon,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $subTypeWeapon = HcSubTypeWeapon::find($id);

            if (!$subTypeWeapon) {
                return response()->json(['status' => 'error', 'message' => 'TypeWeapon not found', 'error_code' => 'NOT_FOUND'], 404);
            }

            $validator = Validator::make($request->all(), [
                'type_weapon_id' => 'nullable|exists:hc_type_weapons,id',
                'name' => 'nullable|string|max:255',
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

            $subTypeWeaponData = $request->all();

            $subTypeWeapon->update($subTypeWeaponData);

            return response()->json(['status' => 'success', 'data' => $subTypeWeapon], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }


    public function destroy($id)
    {
        try {
            $subTypeWeapon = HcSubTypeWeapon::findOrFail($id);
            $subTypeWeapon->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Type Weapon deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
