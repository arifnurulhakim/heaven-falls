<?php

namespace App\Http\Controllers;

use App\Models\HrSkinWeapon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HrSkinWeaponController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Mengambil parameter dari request
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            // Daftar field yang valid untuk sorting
            $validSortFields = ['id', 'name_skin', 'code_skin', 'point_price', 'gender_skin'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            // Eager loading creator dan modifier
            $query = HrSkinWeapon::with(['creator', 'modifier']);

            // Filter global
            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('name_skin', 'like', "%{$globalFilter}%")
                        ->orWhere('code_skin', 'like', "%{$globalFilter}%")
                        ->orWhere('gender_skin', 'like', "%{$globalFilter}%");
                });
            }

            // Mengambil data dengan sorting dan pagination
            $skins = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            // Transformasi data untuk menyertakan hanya nama creator dan modifier
            $skins->transform(function ($skin) {
                return [
                    'id' => $skin->id,
                    'name_skin' => $skin->name_skin,
                    'level_reach' => $skin->level_reach,
                    'code_skin' => $skin->code_skin,
                    'image_skin' => $skin->image_skin,
                    'gender_skin' => $skin->gender_skin,
                    'point_price' => $skin->point_price,
                    'creator' => $skin->creator ? $skin->creator->name : null,
                    'modifier' => $skin->modifier ? $skin->modifier->name : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $skins->currentPage(),
                'last_page' => $skins->lastPage(),
                'next_page' => $skins->currentPage() < $skins->lastPage() ? $skins->currentPage() + 1 : null,
                'prev_page' => $skins->currentPage() > 1 ? $skins->currentPage() - 1 : null,
                'next_page_url' => $skins->nextPageUrl(),
                'prev_page_url' => $skins->previousPageUrl(),
                'per_page' => $skins->perPage(),
                'total' => $skins->total(),
                'data' => $skins->items(),
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
                'name_skin' => 'required|string|max:255',
                'code_skin' => 'required|string|max:255',
                'image_skin' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5048',
                'gender_skin' => 'nullable|string',
                'point_price' => 'required|numeric',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation error', 'error_code' => 'VALIDATION_ERROR', 'errors' => $validator->errors()], 422);
            }

            $imageUrl = null;

            if ($request->hasFile('image_skin')) {
                $image = $request->file('image_skin');
                $imageName = 'skin-' . time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/skins'), $imageName);

                // Save image URL in the database
                $imageUrl = 'images/skins/' . $imageName;
            }

            $skinData = $request->all();
            $skinData['image_skin'] = $imageUrl;

            $skin = HrSkinWeapon::create($skinData);

            return response()->json(['status' => 'success', 'data' => $skin], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }


    public function show($id)
    {
        try {
            $skin = HrSkinWeapon::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $skin,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $skin = HrSkinWeapon::find($id);

            if (!$skin) {
                return response()->json(['status' => 'error', 'message' => 'SkinWeapon not found', 'error_code' => 'NOT_FOUND'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name_skin' => 'nullable|string|max:255',
                'code_skin' => 'nullable|string|max:255',
                'level_reach' => 'nullable|integer',
                'image_skin' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5048',
                'gender_skin' => 'nullable|string',
                'point_price' => 'nullable|numeric',
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

            $imageUrl = $skin->image_skin;

            if ($request->hasFile('image_skin')) {
                // Delete the old image if it exists
                if ($skin->image_skin) {
                    $oldImagePath = public_path($skin->image_skin);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                // Upload and update the new image
                $image = $request->file('image_skin');
                $imageName = 'skin-' . time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/skins'), $imageName);

                // Update the image URL
                $imageUrl = 'images/skins/' . $imageName;
            }

            $skinData = $request->all();
            $skinData['image_skin'] = $imageUrl;

            $skin->update($skinData);

            return response()->json(['status' => 'success', 'data' => $skin], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }


    public function destroy($id)
    {
        try {
            $skin = HrSkinWeapon::findOrFail($id);
            $skin->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Skin deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
