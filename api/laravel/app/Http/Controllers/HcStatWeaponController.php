<?php

namespace App\Http\Controllers;

use App\Models\HcStatWeapon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HcStatWeaponController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortField = $request->input('sortField', 'id');
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');

            $validSortFields = ['id', 'weapon_id', 'level_reach', 'accuracy', 'damage', 'range', 'fire_rate', 'created_by', 'modified_by'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HcStatWeapon::with(['weapon', 'creator', 'modifier']);

            if ($globalFilter) {
                $query->whereHas('weapon', function ($q) use ($globalFilter) {
                    $q->where('name', 'like', "%{$globalFilter}%");
                });
            }

            $statWeapons = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $statWeapons->transform(function ($stat) {
                return [
                    'id' => $stat->id,
                    'weapon_id' => $stat->weapon_id,
                    'weapon_name' => $stat->weapon->name ?? null,
                    'level_reach' => $stat->level_reach,
                    'accuracy' => $stat->accuracy,
                    'damage' => $stat->damage,
                    'range' => $stat->range,
                    'fire_rate' => $stat->fire_rate,
                    'creator' => $stat->creator ? $stat->creator->name : null,
                    'modifier' => $stat->modifier ? $stat->modifier->name : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $statWeapons->currentPage(),
                'last_page' => $statWeapons->lastPage(),
                'next_page' => $statWeapons->currentPage() < $statWeapons->lastPage() ? $statWeapons->currentPage() + 1 : null,
                'prev_page' => $statWeapons->currentPage() > 1 ? $statWeapons->currentPage() - 1 : null,
                'next_page_url' => $statWeapons->nextPageUrl(),
                'prev_page_url' => $statWeapons->previousPageUrl(),
                'per_page' => $statWeapons->perPage(),
                'total' => $statWeapons->total(),
                'data' => $statWeapons->items(),
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
                'weapon_id' => 'required|exists:hc_weapons,id',
                'level_reach' => 'required|integer|min:1',
                'accuracy' => 'required|numeric|min:0|max:100',
                'damage' => 'required|numeric|min:0|max:100',
                'range' => 'required|numeric|min:0|max:100',
                'fire_rate' => 'required|numeric|min:0|max:100',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
            }

            $statWeapon = HcStatWeapon::create($request->all());

            return response()->json(['status' => 'success', 'data' => $statWeapon], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function show($id)
    {
        try {
            $statWeapon = HcStatWeapon::with(['weapon', 'creator', 'modifier'])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $statWeapon->id,
                    'weapon_id' => $statWeapon->weapon_id,
                    'weapon_name' => $statWeapon->weapon->name ?? null,
                    'level_reach' => $statWeapon->level_reach,
                    'accuracy' => $statWeapon->accuracy,
                    'damage' => $statWeapon->damage,
                    'range' => $statWeapon->range,
                    'fire_rate' => $statWeapon->fire_rate,
                    'creator' => $statWeapon->creator ? $statWeapon->creator->name : null,
                    'modifier' => $statWeapon->modifier ? $statWeapon->modifier->name : null,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $statWeapon = HcStatWeapon::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'weapon_id' => 'nullable|exists:hc_weapons,id',
                'level_reach' => 'nullable|integer|min:1',
                'accuracy' => 'nullable|numeric|min:0|max:100',
                'damage' => 'nullable|numeric|min:0|max:100',
                'range' => 'nullable|numeric|min:0|max:100',
                'fire_rate' => 'nullable|numeric|min:0|max:100',
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

            $statWeapon->update($request->all());

            return response()->json(['status' => 'success', 'data' => $statWeapon], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $statWeapon = HcStatWeapon::findOrFail($id);
            $statWeapon->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Weapon stat deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
