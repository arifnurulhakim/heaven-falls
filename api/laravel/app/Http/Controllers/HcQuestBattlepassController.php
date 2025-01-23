<?php

namespace App\Http\Controllers;

use App\Models\HcQuestBattlepass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HcQuestBattlepassController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'name_quest', 'category', 'target'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HcQuestBattlepass::query();

            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('name_quest', 'like', "%{$globalFilter}%")
                      ->orWhere('description_quest', 'like', "%{$globalFilter}%")
                      ->orWhere('category', 'like', "%{$globalFilter}%")
                      ->orWhere('target', 'like', "%{$globalFilter}%");
                });
            }

            $quests = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'current_page' => $quests->currentPage(),
                'last_page' => $quests->lastPage(),
                'next_page' => $quests->currentPage() < $quests->lastPage() ? $quests->currentPage() + 1 : null,
                'prev_page' => $quests->currentPage() > 1 ? $quests->currentPage() - 1 : null,
                'next_page_url' => $quests->nextPageUrl(),
                'prev_page_url' => $quests->previousPageUrl(),
                'per_page' => $quests->perPage(),
                'total' => $quests->total(),
                'data' => $quests->items(),
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
                'name_quest' => 'required|string|max:255',
                'description_quest' => 'nullable|string',
                'category' => 'required|string|max:255',
                'target' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors()
                ], 422);
            }

            $questData = $request->all();

            $quest = HcQuestBattlepass::create($questData);

            return response()->json(['status' => 'success', 'data' => $quest], 201);
        } catch (\Exception $e) {
        
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function show($id)
    {
        try {
            $quest = HcQuestBattlepass::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $quest,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $quest = HcQuestBattlepass::find($id);

            if (!$quest) {
                return response()->json(['status' => 'error', 'message' => 'Quest not found', 'error_code' => 'NOT_FOUND'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name_quest' => 'nullable|string|max:255',
                'description_quest' => 'nullable|string',
                'category' => 'nullable|string|max:255',
                'target' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $questData = $request->all();

            $quest->update($questData);

            return response()->json(['status' => 'success', 'data' => $quest], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $quest = HcQuestBattlepass::findOrFail($id);
            $quest->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Quest deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
