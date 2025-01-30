<?php

namespace App\Http\Controllers;

use App\Models\HcStates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HcStatesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortField = $request->input('sortField', 'name');
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');

            $validSortFields = ['id', 'name', 'country_id', 'country_code', 'fips_code', 'iso2', 'latitude', 'longitude'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field',
                ], 400);
            }

            $query = HcStates::with('country');

            if ($globalFilter) {
                $query->where('name', 'like', "%{$globalFilter}%")
                      ->orWhere('country_code', 'like', "%{$globalFilter}%")
                      ->orWhere('fips_code', 'like', "%{$globalFilter}%");
            }

            $states = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $states->transform(function ($state) {
                return [
                    'id' => $state->id,
                    'name' => $state->name,
                    'country_id' => $state->country_id,
                    'country_code' => $state->country_code,
                    'fips_code' => $state->fips_code,
                    'iso2' => $state->iso2,
                    'latitude' => $state->latitude,
                    'longitude' => $state->longitude,
                    'flag' => $state->flag,
                    'country_name' => $state->country ? $state->country->name : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $states->currentPage(),
                'last_page' => $states->lastPage(),
                'next_page' => $states->currentPage() < $states->lastPage() ? $states->currentPage() + 1 : null,
                'prev_page' => $states->currentPage() > 1 ? $states->currentPage() - 1 : null,
                'next_page_url' => $states->nextPageUrl(),
                'prev_page_url' => $states->previousPageUrl(),
                'per_page' => $states->perPage(),
                'total' => $states->total(),
                'data' => $states->items(),
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
                'country_id' => 'required|exists:hc_countries,id',
                'country_code' => 'nullable|string|max:10',
                'fips_code' => 'nullable|string|max:10',
                'iso2' => 'nullable|string|max:10',
                'type' => 'nullable|string|max:50',
                'level' => 'nullable|integer',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'flag' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $state = HcStates::create($request->all());

            return response()->json(['status' => 'success', 'data' => $state], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $state = HcStates::with('country')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $state,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function showBycountry($id)
    {
        try {
            $state = HcStates::with('country')->where('country_id',$id)->get();
            return response()->json([
                'status' => 'success',
                'data' => $state,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $state = HcStates::find($id);

            if (!$state) {
                return response()->json(['status' => 'error', 'message' => 'State not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'country_id' => 'nullable|exists:hc_countries,id',
                'country_code' => 'nullable|string|max:10',
                'fips_code' => 'nullable|string|max:10',
                'iso2' => 'nullable|string|max:10',
                'type' => 'nullable|string|max:50',
                'level' => 'nullable|integer',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'flag' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $state->update($request->all());

            return response()->json(['status' => 'success', 'data' => $state], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $state = HcStates::findOrFail($id);
            $state->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'State deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}