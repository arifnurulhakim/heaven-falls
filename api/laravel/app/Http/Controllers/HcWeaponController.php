<?php

namespace App\Http\Controllers;

use App\Models\HcWeapon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HcWeaponController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortField = $request->input('sortField', 'name_weapons');
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');

            $validSortFields = ['id', 'name_weapons', 'attack', 'durability', 'accuracy',
            'recoil',
            'firespeed','point_price', 'created_by', 'modified_by'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HcWeapon::with(['type', 'creator', 'modifier']);

            if ($globalFilter) {
                $query->where(function($query) use ($globalFilter) {
                    $query->where('name_weapons', 'like', "%{$globalFilter}%")
                          ->orWhere('description', 'like', "%{$globalFilter}%");
                });
            }

            $weapons = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $weapons->transform(function ($weapon) {
                return [
                    'id' => $weapon->id,
                    'name_weapons' => $weapon->name_weapons,
                    'attack' => $weapon->attack,
                    'durability' => $weapon->durability,
                    'point_price' => $weapon->point_price,
                    'type' => $weapon->type ? $weapon->type->name : null,
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
                'is_active' => 'required|boolean',
                'is_in_shop' => 'required|boolean',
                'weapon_r_type' => 'required|integer|exists:hc_type_weapons,id',
                'name_weapons' => 'required|string|max:255',
                'description' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5048',
                'attack' => 'required|integer',
                'durability' => 'required|integer',
                'accuracy' => 'required|integer',
                'recoil' => 'required|integer',
                'firespeed' => 'required|integer',
                'point_price' => 'required|numeric',
                'repair_price' => 'required|numeric',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation error', 'error_code' => 'VALIDATION_ERROR', 'errors' => $validator->errors()], 422);
            }

            $imageUrl = null;

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = 'weapon-' .$request->name_weapons.'-'. time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/weapons'), $imageName);

                // Save image URL in the database
                $imageUrl = 'images/weapons/' . $imageName;
            }

            $weaponData = $request->all();
            $weaponData['image'] = $imageUrl;

            $weapon = HcWeapon::create($weaponData);

            return response()->json(['status' => 'success', 'data' => $weapon], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }


    public function show($id)
    {
        try {
            $weapon = HcWeapon::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $weapon,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $weapon = HcWeapon::find($id);

            if (!$weapon) {
                return response()->json(['status' => 'error', 'message' => 'Weapon not found', 'error_code' => 'NOT_FOUND'], 404);
            }

            $validator = Validator::make($request->all(), [
                'is_active' => 'nullable|boolean',
                'is_in_shop' => 'nullable|boolean',
                'weapon_r_type' => 'nullable|integer|exists:hc_type_weapons,id',
                'name_weapons' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5048',
                'attack' => 'nullable|integer',
                'durability' => 'nullable|integer',
                'accuracy' => 'nullable|integer',
                'recoil' => 'nullable|integer',
                'firespeed' => 'nullable|integer',
                'point_price' => 'nullable|numeric',
                'repair_price' => 'nullable|numeric',
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

            $imageUrl = $weapon->image;

            if ($request->hasFile('image')) {
                // Delete the old image if it exists
                if ($weapon->image) {
                    $oldImagePath = public_path($weapon->image);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $weapon_name = $request->name_weapons ?? $weapon->name_weapons;

                // Upload and update the new image
                $image = $request->file('image');
                $imageName = 'weapon-' .$weapon_name.'-'. time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/weapons'), $imageName);

                // Update the image URL
                $imageUrl = 'images/weapons/' . $imageName;
            }

            $weaponData = $request->all();
            $weaponData['image'] = $imageUrl;

            $weapon->update($weaponData);

            return response()->json(['status' => 'success', 'data' => $weapon], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $weapon = HcWeapon::findOrFail($id);
            $weapon->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Weapon deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
