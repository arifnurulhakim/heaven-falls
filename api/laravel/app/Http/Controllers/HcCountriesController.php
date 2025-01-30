<?php

namespace App\Http\Controllers;

use App\Models\HcCountries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HcCountriesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortField = $request->input('sortField', 'name');
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');

            $validSortFields = ['id', 'name', 'iso3', 'iso2', 'phonecode', 'capital', 'currency', 'latitude', 'longitude'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field',
                ], 400);
            }

            $query = HcCountries::query();

            if ($globalFilter) {
                $query->where('name', 'like', "%{$globalFilter}%")
                      ->orWhere('iso3', 'like', "%{$globalFilter}%")
                      ->orWhere('iso2', 'like', "%{$globalFilter}%")
                      ->orWhere('capital', 'like', "%{$globalFilter}%");
            }

            $countries = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'current_page' => $countries->currentPage(),
                'last_page' => $countries->lastPage(),
                'next_page' => $countries->currentPage() < $countries->lastPage() ? $countries->currentPage() + 1 : null,
                'prev_page' => $countries->currentPage() > 1 ? $countries->currentPage() - 1 : null,
                'next_page_url' => $countries->nextPageUrl(),
                'prev_page_url' => $countries->previousPageUrl(),
                'per_page' => $countries->perPage(),
                'total' => $countries->total(),
                'data' => $countries->items(),
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
                'iso3' => 'nullable|string|max:3',
                'iso2' => 'nullable|string|max:2',
                'phonecode' => 'nullable|string|max:10',
                'capital' => 'nullable|string|max:255',
                'currency' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'flag' => 'nullable|boolean',
                'translations' => 'nullable|array',
                'timezones' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $country = HcCountries::create($request->all());

            return response()->json(['status' => 'success', 'data' => $country], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $country = HcCountries::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $country,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $country = HcCountries::find($id);

            if (!$country) {
                return response()->json(['status' => 'error', 'message' => 'Country not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'iso3' => 'nullable|string|max:3',
                'iso2' => 'nullable|string|max:2',
                'phonecode' => 'nullable|string|max:10',
                'capital' => 'nullable|string|max:255',
                'currency' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'flag' => 'nullable|boolean',
                'translations' => 'nullable|array',
                'timezones' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $country->update($request->all());

            return response()->json(['status' => 'success', 'data' => $country], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $country = HcCountries::findOrFail($id);
            $country->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Country deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}