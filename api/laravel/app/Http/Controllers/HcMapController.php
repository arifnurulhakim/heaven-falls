<?php

namespace App\Http\Controllers;

use App\Models\HcMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HcMapController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Mengambil parameter dari request
            $perPage = $request->input('pageSize', 10);
            $sortField = $request->input('sortField', 'maps_name');
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');

            // Validasi field sorting
            $validSortFields = ['id', 'maps_name','dificulity', 'created_by', 'modified_by'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            // Query untuk mengambil data maps
            $query = HcMap::with(['creator', 'modifier']);

            // Filter berdasarkan kata kunci global
            if ($globalFilter) {
                $query->where('maps_name', 'like', "%{$globalFilter}%")->orwhere('dificulity', 'like', "%{$globalFilter}%");
            }

            // Mengambil data dengan pagination dan sorting
            $maps = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'current_page' => $maps->currentPage(),
                'last_page' => $maps->lastPage(),
                'next_page' => $maps->currentPage() < $maps->lastPage() ? $maps->currentPage() + 1 : null,
                'prev_page' => $maps->currentPage() > 1 ? $maps->currentPage() - 1 : null,
                'next_page_url' => $maps->nextPageUrl(),
                'prev_page_url' => $maps->previousPageUrl(),
                'per_page' => $maps->perPage(),
                'total' => $maps->total(),
                'data' => $maps->items(),
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
    public function show($id)
    {
        try {
            $maps = HcMap::find($id);

            return response()->json([
                'status' => 'success',
                'data' => $maps,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function store(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'maps_name' => 'required|string|max:255',

        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }
        $mapData = $request->all();

        $map = HcMap::create($mapData);

        return response()->json(['status' => 'success', 'data' => $map], 201);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
    }
}
public function update(Request $request, $id)
{
    try {
        $map = HcMap::find($id);

        if (!$map) {
            return response()->json(['status' => 'error', 'message' => 'Map not found', 'error_code' => 'NOT_FOUND'], 404);
        }

        $validator = Validator::make($request->all(), [
            'maps_name' => 'nullable|string|max:255',

        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }


        $mapData = $request->all();

        $map->update($mapData);

        return response()->json(['status' => 'success', 'data' => $map], 200);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
    }
}
public function destroy($id)
{
    try {
        $map = HcMap::find($id);
        $map->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Map deleted successfully',
        ], 204);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

}
