<?php

namespace App\Http\Controllers;

use App\Models\HdMissionReward;
use App\Models\HrLevelPlayersLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\HcLevel;
use App\Models\Player;
use App\Models\HrLevelPlayer;
use App\Models\HdWallet;
use Illuminate\Support\Facades\Auth;
use App\Models\HdMissionMap;


class HdMissionRewardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortField = $request->input('sortField', 'id_player');
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');

            $validSortFields = [
                'id', 'id_player', 'reward_currency', 'reward_exp',
                'claim_status', 'created_by', 'modified_by'
            ];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = HdMissionReward::with(['mission', 'player', 'creator', 'modifier']);

            if ($globalFilter) {
                $query->where(function($query) use ($globalFilter) {
                    $query->where('id_player', 'like', "%{$globalFilter}%")
                          ->orWhere('reward_currency', 'like', "%{$globalFilter}%");
                });
            }

            $rewards = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $rewards->transform(function ($reward) {
                return [
                    'id' => $reward->id,
                    'player' => $reward->player ? $reward->player->name : null,
                    'reward_currency' => $reward->reward_currency,
                    'reward_exp' => $reward->reward_exp,
                    'claim_status' => $reward->claim_status,
                    'mission' => $reward->mission ? $reward->mission->missions_name : null,
                    'creator' => $reward->creator ? $reward->creator->name : null,
                    'modifier' => $reward->modifier ? $reward->modifier->name : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $rewards->currentPage(),
                'last_page' => $rewards->lastPage(),
                'next_page' => $rewards->currentPage() < $rewards->lastPage() ? $rewards->currentPage() + 1 : null,
                'prev_page' => $rewards->currentPage() > 1 ? $rewards->currentPage() - 1 : null,
                'next_page_url' => $rewards->nextPageUrl(),
                'prev_page_url' => $rewards->previousPageUrl(),
                'per_page' => $rewards->perPage(),
                'total' => $rewards->total(),
                'data' => $rewards->items(),
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
    // Method for storing a new mission reward
    public function store(Request $request)
    {
        try {
            $rules = [
                'missions_map_id' => 'required|integer|exists:hd_missions_map,id',
                'id_player' => 'required|integer|exists:players,id', // Ensure correct model/table name
                'reward_currency' => 'required|integer|min:0',
                'reward_exp' => 'required|integer|min:0',
                'claim_status' => 'required|boolean',
                'created_by' => 'required|integer',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $reward = HdMissionReward::create($request->all());
            return response()->json([
                'status' => 'success',
                'message' => 'Mission reward created successfully',
                'data' => $reward,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Method for updating an existing mission reward
    public function update(Request $request, $id)
    {
        try {
            $reward = HdMissionReward::findOrFail($id);

            $rules = [
                'missions_map_id' => 'integer|exists:hd_missions_map,id',
                'id_player' => 'integer|exists:players,id', // Ensure correct model/table name
                'reward_currency' => 'integer|min:0',
                'reward_exp' => 'integer|min:0',
                'claim_status' => 'boolean',
                'modified_by' => 'required|integer',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $reward->update($request->all());
            return response()->json([
                'status' => 'success',
                'message' => 'Mission reward updated successfully',
                'data' => $reward,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Method for deleting a mission reward
    public function destroy($id)
    {
        try {
            $reward = HdMissionReward::findOrFail($id);
            $reward->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Mission reward deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function addMission(Request $request)
    {
        try {
            $rules = [
                'mission_id' => 'required|integer|exists:hd_missions_map,id',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 400);
            }

            // Fetch all missions related to the map_id
            $mission = HdMissionMap::find($request->mission_id);
            $user = Auth::user();
            if(!$user){
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated.'
                ], 401);
            }

                $reward = HdMissionReward::create([
                    'missions_map_id' => $mission->id,
                    'id_player' => $user->id,
                    'reward_currency' => $mission->reward_currency,
                    'reward_exp' => $mission->reward_exp,
                ]);


            return response()->json([
                'status' => 'success',
                'message' => 'Mission and reward added successfully',
                'data' => $reward,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function claimReward(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'reward_mission_id' => 'required|integer',
                'target_missions'=>'required|integer'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation error', 'error_code' => 'VALIDATION_ERROR', 'errors' => $validator->errors()], 422);
            }
            $user = Auth::user();
            $missionReward = HdMissionReward::find($request->reward_mission_id);
            if(!$missionReward){
                return response()->json(['status' => 'error', 'message' => 'Reward not found']);
            }
            if($missionReward->id_player != $user->id){
                return response()->json(['status' => 'error', 'message' => 'Reward is not your']);
            }
            if ($missionReward->claim_status == true) {
                return response()->json(['status' => 'error', 'message' => 'Reward already claimed']);
            }
            $missionMap = HdMissionMap::find($missionReward->missions_map_id);
            // dd($missionMap);
            if( $missionMap->target_missions >= $request->target_missions){
                return response()->json(['status' => 'error', 'message' => 'target not completed']);
            }

            $levelUser = HrLevelPlayer::find($user->level_r_id);
            $log = HrLevelPlayersLog::create([
                'level_player_id' => $levelUser->id,
                'exp' => $missionReward->reward_exp,
                ]);
            $logLevel = HrLevelPlayersLog::where('level_player_id',$levelUser->id)->sum('exp');

            $currentExp = $logLevel;
            $level = HcLevel::where('level_reach','<=',$currentExp)->orderby('id','desc')->first();
            $levelUser->update([
                'exp' => $currentExp,
                'level_id' => $level->id ?? 1,
            ]);

            $wallet = HdWallet::create([
                'player_id' => $user->id,
                'amount' => $missionReward->reward_currency,
                'currency_id' => 1,
                'category'=> 'reward',
                'label'=>'reward',
                'created_by'=> $user->id,
                'modified_by'=> $user->id,
                ]);

            $missionReward->update([
                'claim_status'=>true
            ]);
            $data =[
                $levelUser,
                $wallet
            ];
            return response()->json(['status' => 'success', 'data' => $data], 201);
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['status' => 'error', 'message' => 'An error occurred.', 'error_code' => 'INTERNAL_ERROR'], 500);
        }
    }

    public function listMissionPlayer(){
        try {
            $user = Auth::user();
            $mission = HdMissionReward::where('player_id',$user->id)->get();
            return response()->json([
                'status' => 'success',
                'data' => $mission,
            ], 201);
        }catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
