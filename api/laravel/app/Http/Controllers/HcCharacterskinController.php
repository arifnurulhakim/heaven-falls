<?php

namespace App\Http\Controllers;

use App\Models\HcCharacterRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HcCharacterRoleController extends Controller
{
    public function index()
    {
        try {
            $characterRoles = HcCharacterRole::all();

            return response()->json([
                'status' => 'success',
                'data' => $characterRoles,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function store(Request $request)
    {
        try {
            // Validate the incoming request data
            $validator = Validator::make($request->all(), [
                'role' => 'required|string',
                'hitpoints' => 'nullable|string',
                'damage' => 'nullable|string',
                'defense' => 'nullable|string',
                'speed' => 'nullable|string',
                'skills' => 'nullable|string',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            // Check if validation fails
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }

            // Create a new character role with the validated data
            $characterRole = HcCharacterRole::create($request->all());

            // Return a success response with the newly created character role
            return response()->json([
                'status' => 'success',
                'data' => $characterRole,
            ], 201);
        } catch (\Exception $e) {
            // Return an error response if an exception occurs
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function show($id)
    {
        try {
            $characterRole = HcCharacterRole::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $characterRole,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'role' => 'nullable|string',
                'hitpoints' => 'nullable|string',
                'damage' => 'nullable|string',
                'defense' => 'nullable|string',
                'speed' => 'nullable|string',
                'skills' => 'nullable|string',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }

            $characterRole = HcCharacterRole::findOrFail($id);
            $characterRole->update($request->all());

            return response()->json([
                'status' => 'success',
                'data' => $characterRole,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $characterRole = HcCharacterRole::findOrFail($id);
            $characterRole->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Character deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
