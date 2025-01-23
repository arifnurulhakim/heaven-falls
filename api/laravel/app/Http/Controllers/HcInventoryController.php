<?php

namespace App\Http\Controllers;

use App\Models\HcInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HcInventoryController extends Controller
{
    public function index()
    {
        try {
            $inventory = HcInventory::all();

            return response()->json([
                'status' => 'success',
                'data' => $inventory,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'desc' => 'nullable|string',
                'hud' => 'nullable|string',
                'level_reach' => 'required|numeric',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }

            $inventoryItem = HcInventory::create($request->all());

            return response()->json([
                'status' => 'success',
                'data' => $inventoryItem,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $inventoryItem = HcInventory::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $inventoryItem,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'desc' => 'nullable|string',
                'hud' => 'nullable|string',
                'level_reach' => 'required|numeric',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }

            $inventoryItem = HcInventory::findOrFail($id);
            $inventoryItem->update($request->all());

            return response()->json([
                'status' => 'success',
                'data' => $inventoryItem,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $inventoryItem = HcInventory::findOrFail($id);
            $inventoryItem->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory Item deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
