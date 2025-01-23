<?php

namespace App\Http\Controllers;

use App\Models\HcLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LevelController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortField = $request->input('sortField', 'name');
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');

            $validSortFields = ['id', 'name', 'desc', 'hud', 'level_reach', 'created_by', 'modified_by'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HcLevel::with(['creator', 'modifier']);

            if ($globalFilter) {
                $query->where(function($query) use ($globalFilter) {
                    $query->where('name', 'like', "%{$globalFilter}%")
                          ->orWhere('desc', 'like', "%{$globalFilter}%");
                });
            }

            $levels = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $levels->transform(function ($level) {
                return [
                    'id' => $level->id,
                    'name' => $level->name,
                    'desc' => $level->desc,
                    'hud' => $level->hud,
                    'level_reach' => $level->level_reach,
                    'creator' => $level->creator ? $level->creator->name : null,
                    'modifier' => $level->modifier ? $level->modifier->name : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $levels->currentPage(),
                'last_page' => $levels->lastPage(),
                'next_page' => $levels->currentPage() < $levels->lastPage() ? $levels->currentPage() + 1 : null,
                'prev_page' => $levels->currentPage() > 1 ? $levels->currentPage() - 1 : null,
                'next_page_url' => $levels->nextPageUrl(),
                'prev_page_url' => $levels->previousPageUrl(),
                'per_page' => $levels->perPage(),
                'total' => $levels->total(),
                'data' => $levels->items(),
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
                'hud' => 'nullable|string',
                'level_reach' => 'required|integer',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation error', 'error_code' => 'VALIDATION_ERROR', 'errors' => $validator->errors()], 422);
            }

            $levelData = $request->all();

            $level = HcLevel::create($levelData);

            return response()->json(['status' => 'success', 'data' => $level], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function show($id)
    {
        try {
            $level = HcLevel::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $level,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $level = HcLevel::find($id);

            if (!$level) {
                return response()->json(['status' => 'error', 'message' => 'Level not found', 'error_code' => 'NOT_FOUND'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'desc' => 'nullable|string',
                'hud' => 'nullable|string',
                'level_reach' => 'nullable|integer',
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

            $levelData = $request->all();

            $level->update($levelData);

            return response()->json(['status' => 'success', 'data' => $level], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $level = HcLevel::findOrFail($id);
            $level->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'HcLevel deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
