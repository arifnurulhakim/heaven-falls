<?php

namespace App\Http\Controllers;

use App\Models\HrPeriodSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HrPeriodSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'name', 'start_date', 'end_date'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HrPeriodSubscription::query();

            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('name', 'like', "%{$globalFilter}%")
                      ->orWhere('start_date', 'like', "%{$globalFilter}%")
                      ->orWhere('end_date', 'like', "%{$globalFilter}%");
                });
            }

            $periods = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'current_page' => $periods->currentPage(),
                'last_page' => $periods->lastPage(),
                'next_page' => $periods->currentPage() < $periods->lastPage() ? $periods->currentPage() + 1 : null,
                'prev_page' => $periods->currentPage() > 1 ? $periods->currentPage() - 1 : null,
                'next_page_url' => $periods->nextPageUrl(),
                'prev_page_url' => $periods->previousPageUrl(),
                'per_page' => $periods->perPage(),
                'total' => $periods->total(),
                'data' => $periods->items(),
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
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors()
                ], 422);
            }

            $periodData = $request->all();

            $period = HrPeriodSubscription::create($periodData);

            return response()->json(['status' => 'success', 'data' => $period], 201);
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function show($id)
    {
        try {
            $period = HrPeriodSubscription::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $period,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $period = HrPeriodSubscription::find($id);

            if (!$period) {
                return response()->json(['status' => 'error', 'message' => 'Period not found', 'error_code' => 'NOT_FOUND'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $periodData = $request->all();

            $period->update($periodData);

            return response()->json(['status' => 'success', 'data' => $period], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $period = HrPeriodSubscription::findOrFail($id);
            $period->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Period deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
