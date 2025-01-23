<?php

namespace App\Http\Controllers;

use App\Models\HdBattlepassQuest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HdBattlepassQuestsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $sortField = $request->input('sortField', 'id');
            $globalFilter = $request->input('globalFilter', '');

            $validSortFields = ['id', 'period_battlepass_id', 'quest_id'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HdBattlepassQuest::with(['period', 'quest']);

            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->WhereHas('quest', function ($query) use ($globalFilter) {
                        $query->where('name_quest', 'like', "%{$globalFilter}%");
                    });
                });
            }

            $data = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'next_page' => $data->currentPage() < $data->lastPage() ? $data->currentPage() + 1 : null,
                'prev_page' => $data->currentPage() > 1 ? $data->currentPage() - 1 : null,
                'next_page_url' => $data->nextPageUrl(),
                'prev_page_url' => $data->previousPageUrl(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'data' => $data->items(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'period_battlepass_id' => 'required|exists:hr_period_battlepass,id',
                'quest_id' => 'required|exists:hc_quest_battlepass,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $request->only(['period_battlepass_id', 'quest_id']);
            $battlepassQuest = HdBattlepassQuest::create($data);

            return response()->json([
                'status' => 'success',
                'data' => $battlepassQuest,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $battlepassQuest = HdBattlepassQuest::with(['period', 'quest'])->find($id);

            if (!$battlepassQuest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Battlepass quest not found.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $battlepassQuest,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $battlepassQuest = HdBattlepassQuest::find($id);

            if (!$battlepassQuest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Battlepass quest not found.',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'period_battlepass_id' => 'nullable|exists:hr_period_battlepass,id',
                'quest_id' => 'nullable|exists:hc_quest_battlepass,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $request->only(['period_battlepass_id', 'quest_id']);
            $battlepassQuest->update($data);

            return response()->json([
                'status' => 'success',
                'data' => $battlepassQuest,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $battlepassQuest = HdBattlepassQuest::find($id);

            if (!$battlepassQuest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Battlepass quest not found.',
                ], 404);
            }

            $battlepassQuest->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Battlepass quest deleted successfully.',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
