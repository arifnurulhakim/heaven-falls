<?php

namespace App\Http\Controllers;

use App\Models\HcTypeWeapon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HcTypeWeaponController extends Controller
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

            $query = HcTypeWeapon::with(['creator', 'modifier']);

            if ($globalFilter) {
                $query->where('name', 'like', "%{$globalFilter}%");
            }

            $weapons = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $weapons->transform(function ($weapon) {
                return [
                    'id' => $weapon->id,
                    'name' => $weapon->name,
                    'creator' => $weapon->creator ? $weapon->creator->name : null,
                    'modifier' => $weapon->modifier ? $weapon->modifier->name : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $weapons->currentPage(),
                'last_page' => $weapons->lastPage(),
                'next_page' => $weapons->currentPage() < $weapons->lastPage() ? $weapons->currentPage() + 1 : null,
                'prev_page' => $weapons->currentPage() > 1 ? $weapons->currentPage() - 1 : null,
                'next_page_url' => $weapons->nextPageUrl(),
                'prev_page_url' => $weapons->previousPageUrl(),
                'per_page' => $weapons->perPage(),
                'total' => $weapons->total(),
                'data' => $weapons->items(),
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
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation error', 'error_code' => 'VALIDATION_ERROR', 'errors' => $validator->errors()], 422);
            }

            $typeWeaponData = $request->all();

            $typeWeapon = HcTypeWeapon::create($typeWeaponData);

            return response()->json(['status' => 'success', 'data' => $typeWeapon], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function show($id)
    {
        try {
            $typeWeapon = HcTypeWeapon::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $typeWeapon,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $typeWeapon = HcTypeWeapon::find($id);

            if (!$typeWeapon) {
                return response()->json(['status' => 'error', 'message' => 'TypeWeapon not found', 'error_code' => 'NOT_FOUND'], 404);
            }

            $validator = Validator::make($request->all(), [
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

            $typeWeaponData = $request->all();

            $typeWeapon->update($typeWeaponData);

            return response()->json(['status' => 'success', 'data' => $typeWeapon], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }


    public function destroy($id)
    {
        try {
            $typeWeapon = HcTypeWeapon::findOrFail($id);
            $typeWeapon->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Type Weapon deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
