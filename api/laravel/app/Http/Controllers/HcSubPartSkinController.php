<?php

namespace App\Http\Controllers;

use App\Models\HcSubPartSkin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HcSubPartSkinController extends Controller
{
    public function index()
    {
        try {
            $subParts = HcSubPartSkin::all();

            return response()->json([
                'status' => 'success',
                'data' => $subParts,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'part_id_skin' => 'required|integer',
                'name_sub_part_skin' => 'required|string',
                'code_sub_part_skin' => 'nullable|string',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }

            $subPartSkin = HcSubPartSkin::create($request->all());

            return response()->json([
                'status' => 'success',
                'data' => $subPartSkin,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $subPartSkin = HcSubPartSkin::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $subPartSkin,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'part_id_skin' => 'required|integer',
                'name_sub_part_skin' => 'required|string',
                'code_sub_part_skin' => 'nullable|string',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }

            $subPartSkin = HcSubPartSkin::findOrFail($id);
            $subPartSkin->update($request->all());

            return response()->json([
                'status' => 'success',
                'data' => $subPartSkin,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $subPartSkin = HcSubPartSkin::findOrFail($id);
            $subPartSkin->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Sub Part Skin deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
