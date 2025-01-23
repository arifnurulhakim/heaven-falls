<?php

namespace App\Http\Controllers;

use App\Models\HcPartSkin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HcPartSkinController extends Controller
{
    public function index()
    {
        try {
            $parts = HcPartSkin::all();

            return response()->json([
                'status' => 'success',
                'data' => $parts,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name_part_skin' => 'required|string',
                'code_part_skin' => 'nullable|string',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }

            $partSkin = HcPartSkin::create($request->all());

            return response()->json([
                'status' => 'success',
                'data' => $partSkin,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $partSkin = HcPartSkin::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $partSkin,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name_part_skin' => 'required|string',
                'code_part_skin' => 'nullable|string',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }

            $partSkin = HcPartSkin::findOrFail($id);
            $partSkin->update($request->all());

            return response()->json([
                'status' => 'success',
                'data' => $partSkin,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $partSkin = HcPartSkin::findOrFail($id);
            $partSkin->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Part Skin deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
