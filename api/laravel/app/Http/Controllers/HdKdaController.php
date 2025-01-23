<?php

namespace App\Http\Controllers;

use App\Models\HdKda;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class HdKdaController extends Controller
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
            $user = Auth::user();
            $kdas = HdKda::orderByRaw($order)->where('player_id',$user->id);
            $total_data = $kdas->count();
            if ($search !== '') {
                $kdas->where(function ($query) use ($search) {
                    $query->where('id', 'like', "%$search%");
                });
            }

            $kdas = $kdas->offset($offset)->limit($limit)->get();
            return response()->json([
                'status' => 'success',
                'offset' => $offset,
                'limit' => $limit,
                'order' => $getOrder,
                'search' => $search,
                'total_data' => $total_data,
                'data' => $kdas,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'kill' => 'required|integer',
                'death' => 'required|integer',
                'assist' => 'required|integer',
                'room_code' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }

            $user = Auth::user(); // Get the authenticated user

            // Add player_id to the request data
            $data = $request->all();
            $data['player_id'] = $user->id;

            // Create the record in the HdKda model
            $kdas = HdKda::create($data);

            return response()->json([
                'status' => 'success',
                'data' => $kdas,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
