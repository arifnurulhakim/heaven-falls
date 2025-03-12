<?php

namespace App\Http\Controllers;

use App\Models\HdMissionMap;
use App\Models\HcMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HdMissionMapController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortField = $request->input('sortField', 'missions_name');
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $map_id =  $request->input('map_id', '');

            $validSortFields = [
                'id', 'missions_name', 'condition', 'backstory','type_missions',
                'target_missions', 'reward_currency', 'reward_exp',
                'status_missions', 'created_by', 'modified_by'
            ];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HdMissionMap::with(['map',  'creator', 'modifier']);

            if ($globalFilter) {
                $query->where(function($query) use ($globalFilter) {
                    $query->where('missions_name', 'like', "%{$globalFilter}%")
                          ->orWhere('condition', 'like', "%{$globalFilter}%")
                          ->orWhere('backstory', 'like', "%{$globalFilter}%");
                });
            }
            if ($map_id) {
                $query->where('map_id', $map_id);
            }

            $missions = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $missions->transform(function ($mission) {
                return [
                    'id' => $mission->id,
                    'missions_name' => $mission->missions_name,
                    'condition' => $mission->condition,
                    'backstory' => $mission->backstory,
                    'type_missions' => $mission->type_missions,
                    'target_missions' => $mission->target_missions,
                    'reward_currency' => $mission->reward_currency,
                    'dificulity' => $mission->dificulity,
                    'reward_exp' => $mission->reward_exp,
                    'status_missions' => $mission->status_missions,
                    'map' => $mission->map ? $mission->map->maps_name : null,
                    'creator' => $mission->creator ? $mission->creator->username : null,
                    'modifier' => $mission->modifier ? $mission->modifier->username : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $missions->currentPage(),
                'last_page' => $missions->lastPage(),
                'next_page' => $missions->currentPage() < $missions->lastPage() ? $missions->currentPage() + 1 : null,
                'prev_page' => $missions->currentPage() > 1 ? $missions->currentPage() - 1 : null,
                'next_page_url' => $missions->nextPageUrl(),
                'prev_page_url' => $missions->previousPageUrl(),
                'per_page' => $missions->perPage(),
                'total' => $missions->total(),
                'data' => $missions->items(),
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
        $rules = [
            'maps_id' => 'required|integer|exists:hc_maps,id',
            'data' => 'required|array',
            'data.*.missions_name' => 'required|string|max:255',
            'data.*.condition' => 'required|string',
            'data.*.backstory' => 'required|string',
            'data.*.type_missions' => 'required|string',
            'data.*.target_missions' => 'required|integer',
            'data.*.reward_currency' => 'required|integer',
            'data.*.reward_exp' => 'required|integer',
            'data.*.status_missions' => 'required|string',
            'data.*.created_by' => 'required|integer',
            'data.*.dificulity' => 'required|numeric|min:0|max:5',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Invalid input parameters',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $missionsData = [];
            foreach ($request->data as $mission) {
                $mission['maps_id'] = $request->maps_id;
                $missionsData[] = $mission;
            }

            HdMissionMap::insert($missionsData); // Simpan semua data

            return response()->json([
                'status' => 'success',
                'message' => 'Missions created successfully',
                'data' => $missionsData,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $mission = HdMissionMap::with('map')->find($id);
            if(!$mission){
                return response()->json([
                    'status' => 'error',
                    'message' => 'missions not found.',
                ], 404);
            }


            return response()->json([
                'status' => 'success',
                'data' => $mission,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function showByMap($id)
    {
        try {
            $mission = HcMap::with('missions')->find($id);
            if(!$mission){
                return response()->json([
                    'status' => 'error',
                    'message' => 'missions not found.',
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'data' => $mission,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
public function update(Request $request, $id)
{
    $rules = [
        'maps_id' => 'sometimes|integer|exists:hc_maps,id',
        'missions_name' => 'sometimes|string|max:255',
        'condition' => 'sometimes|string',
        'backstory' => 'sometimes|string',
        'type_missions' => 'sometimes|string',
        'target_missions' => 'sometimes|integer',
        'reward_currency' => 'sometimes|integer',
        'reward_exp' => 'sometimes|integer',
        'status_missions' => 'sometimes|string',
        'modified_by' => 'required|integer',
        'data.*.dificulity' => 'sometimes|numeric|min:0|max:5',
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'ERROR',
            'message' => 'Invalid input parameters',
            'errors' => $validator->errors(),
        ], 400);
    }

    try {
        $mission = HdMissionMap::findOrFail($id);
        $mission->update($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Mission updated successfully',
            'data' => $mission,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
public function destroy($id)
{
    try {
        $mission = HdMissionMap::findOrFail($id);
        $mission->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Mission deleted successfully',
        ], 204);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
}
