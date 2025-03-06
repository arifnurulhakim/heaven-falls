<?php

namespace App\Http\Controllers;

use App\Models\HcCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HcCurrencyController extends Controller
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
            $validSortFields = ['id', 'name', 'code'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            // Eager loading creator dan modifier
            $query = HcCurrency::with(['creator', 'modifier']);

            // Filter global
            if ($globalFilter) {
                $query->where(function ($q) use ($globalFilter) {
                    $q->where('name', 'like', "%{$globalFilter}%")
                      ->orWhere('code', 'like', "%{$globalFilter}%");
                });
            }

            // Mengambil data dengan sorting dan pagination
            $currencies = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            // Transformasi data untuk menyertakan hanya nama creator dan modifier
            $currencies->transform(function ($currency) {
                return [
                    'id' => $currency->id,
                    'name' => $currency->name,
                    'code' => $currency->code,
                    'value' => $currency->value,
                    'creator' => $currency->creator ? $currency->creator->name : null,
                    'modifier' => $currency->modifier ? $currency->modifier->name : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $currencies->currentPage(),
                'last_page' => $currencies->lastPage(),
                'next_page' => $currencies->currentPage() < $currencies->lastPage() ? $currencies->currentPage() + 1 : null,
                'prev_page' => $currencies->currentPage() > 1 ? $currencies->currentPage() - 1 : null,
                'next_page_url' => $currencies->nextPageUrl(),
                'prev_page_url' => $currencies->previousPageUrl(),
                'per_page' => $currencies->perPage(),
                'total' => $currencies->total(),
                'data' => $currencies->items(),
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
                'value' => 'required|integer|max:255',
                'code' => 'required|string|max:10|unique:hc_currencies,code',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation error', 'error_code' => 'VALIDATION_ERROR', 'errors' => $validator->errors()], 422);
            }

            $currencyData = $request->all();

            $currency = HcCurrency::create($currencyData);

            return response()->json(['status' => 'success', 'data' => $currency], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function show($id)
    {
        try {
            $currency = HcCurrency::find($id);
            if(!$currency){
                return response()->json([
                    'status' => 'error',
                    'message' => 'currency not found.',
                ], 404);
            }


            return response()->json([
                'status' => 'success',
                'data' => $currency,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $currency = HcCurrency::find($id);

            if (!$currency) {
                return response()->json(['status' => 'error', 'message' => 'Currency not found', 'error_code' => 'NOT_FOUND'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'value' => 'nullable|integer|max:255',
                'code' => 'nullable|string|max:10|unique:hc_currencies,code,' . $id,
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

            $currencyData = $request->all();

            $currency->update($currencyData);

            return response()->json(['status' => 'success', 'data' => $currency], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }




    public function destroy($id)
    {
        try {
            $currency = HcCurrency::find($id);
            if(!$currency){
                return response()->json([
                    'status' => 'error',
                    'message' => 'currency not found.',
                ], 404);
            }
            $currency->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'currency deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
