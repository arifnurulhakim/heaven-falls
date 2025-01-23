<?php

namespace App\Http\Controllers;

use App\Models\Assets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AssetsController extends Controller
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
            $validSortFields = ['id', 'name', 'file'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            // Eager loading creator dan modifier
            $query = Assets::with(['creator', 'modifier']);

            // Filter global
            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('name', 'like', "%{$globalFilter}%")
                      ->orWhere('file', 'like', "%{$globalFilter}%");
                });
            }

            // Mengambil data dengan sorting dan pagination
            $assets = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            // Transformasi data untuk menyertakan hanya nama creator dan modifier
            $assets->transform(function ($asset) {
                return [
                    'id' => $asset->id,
                    'name' => $asset->name,
                    'file' => $asset->file,
                    'creator' => $asset->creator ? $asset->creator->name : null,
                    'modifier' => $asset->modifier ? $asset->modifier->name : null,

                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $assets->currentPage(),
                'last_page' => $assets->lastPage(),
                'next_page' => $assets->currentPage() < $assets->lastPage() ? $assets->currentPage() + 1 : null,
                'prev_page' => $assets->currentPage() > 1 ? $assets->currentPage() - 1 : null,
                'next_page_url' => $assets->nextPageUrl(),
                'prev_page_url' => $assets->previousPageUrl(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
                'data' => $assets->items(),
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
                'file' => 'nullable|file|mimes:zip|max:50480',

            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation error', 'error_code' => 'VALIDATION_ERROR', 'errors' => $validator->errors()], 422);
            }

            $assetUrl = null;

            if ($request->hasFile('file')) {
                $asset = $request->file('file');
                $assetName = 'asset-' . time() . '.' . $asset->getClientOriginalExtension();
                $asset->move(public_path('assets'), $assetName);

                // Save asset URL in the database
                $assetUrl = 'assets/' . $assetName;
            }

            $assetData = $request->all();
            $assetData['file'] = $assetUrl;

            $asset = Assets::create($assetData);

            return response()->json(['status' => 'success', 'data' => $asset], 201);
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }


    public function show($id)
    {
        try {
            $asset = Assets::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $asset,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $asset = Assets::find($id);

            if (!$asset) {
                return response()->json(['status' => 'error', 'message' => 'asset not found', 'error_code' => 'NOT_FOUND'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'file' => 'nullable|file|mimes:zip|max:50480',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $assetUrl = $asset->file;

            if ($request->hasFile('file')) {
                // Delete the old asset if it exists
                if ($asset->file) {
                    $oldImagePath = public_path($asset->file);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                // Upload and update the new asset
                $asset = $request->file('file');
                $assetName = 'asset-' . time() . '.' . $asset->getClientOriginalExtension();
                $asset->move(public_path('assets'), $assetName);

                // Update the asset URL
                $assetUrl = 'assets/' . $assetName;
            }

            $assetData = $request->all();
            $assetData['file'] = $assetUrl;

            $asset->update($assetData);

            return response()->json(['status' => 'success', 'data' => $asset], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }


    public function destroy($id)
    {
        try {
            $asset = Assets::findOrFail($id);
            $asset->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'asset deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
