<?php

namespace App\Http\Controllers;

use App\Models\HrCurrenciesShop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HrCurrenciesShopController extends Controller
{
    public function index()
    {
        try {
            $offset = request()->get('offset', 0);
            $limit = request()->get('limit', 10);
            $search = request()->get('search', '');
            $getOrder = request()->get('order', '');
            $defaultOrder = $getOrder ? $getOrder : "id ASC";
            $orderMappings = [
                'idASC' => 'id ASC',
                'idDESC' => 'id DESC',
                'nameASC' => 'name ASC',
                'nameDESC' => 'name DESC',
                'valueASC' => 'value ASC',
                'valueDESC' => 'value DESC',
            ];

            $order = $orderMappings[$getOrder] ?? $defaultOrder;
            $validOrderValues = implode(',', array_keys($orderMappings));
            $rules = [
                'offset' => 'integer|min:0',
                'limit' => 'integer|min:1',
                'order' => "in:$validOrderValues",
            ];

            $validator = Validator::make([
                'offset' => $offset,
                'limit' => $limit,
                'order' => $getOrder,
            ], $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid input parameters',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $currenciesShops = HrCurrenciesShop::with('currency')->orderByRaw($order);
            $total_data = $currenciesShops->count();
            if ($search !== '') {
                $currenciesShops->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%$search%")
                        ->orWhere('desciption', 'like', "%$search%")
                        ->orWhere('value', 'like', "%$search%");
                });
            }

            $currenciesShops = $currenciesShops->offset($offset)->limit($limit)->get();
            return response()->json([
                'status' => 'success',
                'offset' => $offset,
                'limit' => $limit,
                'order' => $getOrder,
                'search' => $search,
                'total_data' => $total_data,
                'data' => $currenciesShops,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'currency_id' => 'required|integer|exists:hc_currencies,id',
                'name' => 'required|string|max:255',
                'desciption' => 'nullable|string',
                'value' => 'required|numeric',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation error', 'error_code' => 'VALIDATION_ERROR', 'errors' => $validator->errors()], 422);
            }

            $currenciesShopData = $request->all();

            $currenciesShop = HrCurrenciesShop::create($currenciesShopData);

            return response()->json(['status' => 'success', 'data' => $currenciesShop], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }


    public function show($id)
    {
        try {
            $currenciesShop = HrCurrenciesShop::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $currenciesShop,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $currenciesShop = HrCurrenciesShop::find($id);

            if (!$currenciesShop) {
                return response()->json(['status' => 'error', 'message' => 'CurrenciesShop not found', 'error_code' => 'NOT_FOUND'], 404);
            }

            $validator = Validator::make($request->all(), [
                'currency_id' => 'nullable|integer|exists:hc_currencies,id',
                'name' => 'nullable|string|max:255',
                'desciption' => 'nullable|string',
                'value' => 'nullable|numeric',
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

            $currenciesShopData = $request->all();

            $currenciesShop->update($currenciesShopData);

            return response()->json(['status' => 'success', 'data' => $currenciesShop], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $currenciesShop = HrCurrenciesShop::findOrFail($id);
            $currenciesShop->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'CurrenciesShop deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
