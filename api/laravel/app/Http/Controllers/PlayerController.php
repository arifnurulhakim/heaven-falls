<?php

namespace App\Http\Controllers;


use App\Models\Level;
use App\Models\HrLevelPlayer;
use App\Models\HcWeapon;
use App\Models\HrReferrerCode;
use Illuminate\Support\Str;
use App\Models\HrInventoryPlayer;
use App\Models\HdSkinCharacterPlayer;
use App\Models\HdWallet;
use App\Models\Player;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use App\Models\Character;
use App\Models\Stat;
use App\Models\Cosmetic;
use App\Models\Item;
use App\Models\HrWeaponPlayer;
use App\Models\HrSubscriptionPurchase;
use App\Models\HrExpSubscription;
use App\Models\HdSubscription;
use App\Models\HrBattlepassPurchase;
use App\Models\HrExpBattlepass;
use App\Models\HrPeriodBattlepass;
use App\Models\HrPeriodSubscription;
use App\Models\HdBattlepass;
use App\Models\HcCurrency;
use App\Models\HcMap;
use App\Models\HcCharacterRole;

class PlayerController extends Controller
{

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                    'error_code' => 'INPUT_VALIDATION_ERROR',
                ], 422);
            }

            $credentials = $request->only('email', 'password');
            Auth::shouldUse('player');
            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Username or password invalid',
                    'error_code' => 'USERNAME_OR_PASSWORD_INVALID',
                ], 401);
            }

            $player = Auth::user();
            $token = JWTAuth::fromUser($player);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $player->id,
                    'username' => $player->username,
                    'email' => $player->email,
                    'token' => $token,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateprofile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'gender' => 'required|integer|in:1,2',
                'mobile_number' => 'nullable|string|max:50', // Adjust validation based on your requirements
                'players_ip_address' => 'nullable|string|max:30',
                'players_mac_address' => 'nullable|string|max:30',
                'players_os_type' => 'nullable|integer',
                // Add any other fields as necessary
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                    'error_code' => 'INPUT_VALIDATION_ERROR',
                ], 422);
            }

            $playerId = Auth::id();
            if (!$playerId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized, please login again',
                    'error_code' => 'USER_NOT_FOUND',
                ], 401);
            };

            $player = Player::where('id', $playerId)->first();
            $player->update([
                'gender' => $request->get('gender', $player->gender),
                'mobile_number' => $request->get('mobile_number', $player->mobile_number),
                'players_ip_address' => $request->get('players_ip_address', $player->players_ip_address),
                'players_mac_address' => $request->get('players_mac_address', $player->players_mac_address),
                'players_os_type' => $request->get('players_os_type', $player->players_os_type),
            ]);



            return response()->json([
                'status' => 'success',
                'data' => $player,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function adminLogin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                    'error_code' => 'INPUT_VALIDATION_ERROR',
                ], 422);
            }

            $credentials = $request->only('email', 'password');
            Auth::shouldUse('user');
            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'email or password invalid',
                    'error_code' => 'EMAIL_OR_PASSWORD_INVALID',
                ], 401);
            }

            $user = Auth::user();

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $user->id,
                    'user' => $user->name,
                    'email' => $user->email,
                    'token' => $token,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function adminRegister(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:hd_users',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                    'error_code' => 'INPUT_VALIDATION_ERROR',
                ], 422);
            }

            $user = User::create([
                'name' => $request->get('name'),
                'email' => $request->get('email'),
                'password' => bcrypt($request->get('password')),
            ]);

            $token = JWTAuth::fromUser($user);

            return response()->json(compact('user', 'token'), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e], 500);
        }
    }

    public function logout()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized, please login again',
                    'error_code' => 'USER_NOT_FOUND',
                ], 401);
            }
            if (!Auth::check()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid token',
                    'error_code' => 'INVALID_TOKEN',
                ], 401);
            }

            Auth::logout();
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully logged out',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function logoutAdmin(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized, please login again',
                    'error_code' => 'USER_NOT_FOUND',
                ], 401);
            }

            Auth::guard('user')->logout();

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully logged out',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // public function getProfile(Request $request)
    // {
    //     try {
    //         $userId = Auth::id();

    //         $user = Player::where('hd_players.id', $userId)
    //             ->first();

    //         return response()->json([
    //             'status' => 'success',
    //             'data' => $user,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }
    public function getProfile(Request $request)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Get user level details
            $level = HrLevelPlayer::where('hr_level_players.id', $user->level_r_id)
                ->leftJoin('hc_levels', 'hr_level_players.level_id', '=', 'hc_levels.id')
                ->select('hr_level_players.*', 'hc_levels.name as level_name') // Include relevant fields
                ->first();
                $wallet = HcCurrency::leftJoin('hd_wallets', function($join) use ($user) {
                    $join->on('hc_currencies.id', '=', 'hd_wallets.currency_id')
                        ->where('hd_wallets.player_id', $user->id);
                })
                ->select('hc_currencies.name as currency_name',
                    \DB::raw('COALESCE(SUM(hf_hd_wallets.amount), 0) as amount')
                )
                ->groupBy('hc_currencies.name')
                ->pluck('amount', 'currency_name');
            $inventory = HrInventoryPlayer::find($user->inventory_r_id);

            if ($inventory) {
                $primary = HcWeapon::find($inventory->weapon_primary_r_id);
                $secondary = HcWeapon::find($inventory->weapon_secondary_r_id);
                $melee = HcWeapon::find($inventory->weapon_melee_r_id);
                $explosive = HcWeapon::find($inventory->weapon_explosive_r_id);
            }

            // Get skin character data
            $skinCharacter = HdSkinCharacterPlayer::where('inventory_id',$user->inventory_r_id)->get();
            $periodBp = HrPeriodBattlepass::where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

            if ($periodBp) {
                $battlepassPurchase = HrBattlepassPurchase::where('player_id', $user->id)
                    ->whereBetween('purchased_at', [$periodBp->start_date, $periodBp->end_date])
                    ->first();

                $battlepassExp = HrExpBattlepass::where('player_id', $user->id)
                    ->sum('exp');

                $battlepassLvl = HdBattlepass::where('period_battlepass_id', $periodBp->id)
                    ->where('reach_exp', '<=', $battlepassExp)
                    ->orderBy('reach_exp', 'desc')
                    ->first();
            } else {
                // Handle case where no active period is found
                $battlepass = null;
                $battlepassPurchase = false;
                $battlepassExp = 0;
                $battlepassLvl = null;
            }
            $periodSub = HrPeriodSubscription::where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

            if ($periodSub) {
                $subscriptionPurchase = HrSubscriptionPurchase::where('player_id', $user->id)
                    ->whereBetween('purchased_at', [$periodSub->start_date, $periodSub->end_date])
                    ->first();

                $subscriptionExp = HrExpSubscription::where('player_id', $user->id)
                    ->sum('exp');

                $subscriptionLvl = HdSubscription::where('period_subscription_id', $periodSub->id)
                    ->where('reach_exp', '<=', $subscriptionExp)
                    ->orderBy('reach_exp', 'desc')
                    ->first();
            } else {
                // Handle case where no active period is found
                $subscription = null;
                $subscriptionPurchase = false;
                $subscriptionExp = 0;
                $subscriptionLvl = null;
            }

            $refferer = HrReferrerCode::where('player_id',$user->id)->first();
            // $battlepassLevel = HdSubscription::where('period_battlepass_id', $period->id)->first();

            // Construct the response data
            $responseData = [
                'player' => $user,
                'level' => $level,
                'refferer_code'=>$refferer->code,
                'wallet' => $wallet,
                'battlepass'=>[
                    'purchase' => $battlepassPurchase ?? false,
                    'exp' => $battlepassExp,
                    'level' => $battlepassLvl,
                ],
                'subscription'=>[
                    'purchase' => $subscriptionPurchase ?? false,
                    'exp' => $subscriptionExp,
                    'level' => $subscriptionLvl,
                ],
                'inventory' => [
                    'primary_weapon' => $primary ?? null,
                    'secondary_weapon' => $secondary ?? null,
                    'melee_weapon' => $melee ?? null,
                    'explosive_weapon' => $explosive ?? null,
                ],
                'skin_character' => $skinCharacter,
            ];

            // Return success response with data
            return response()->json([
                'status' => 'success',
                'data' => $responseData,
            ]);

        } catch (\Exception $e) {
            // Return error response with exception message
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getprofileadmin()
    {
        try {
            $user = Auth::guard('user')->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                    'error_code' => 'UNAUTHORIZED',
                ], 401);
            }

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];

            return response()->json([
                'status' => 'success',
                'data' => $userData,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortField = $request->input('sortField', 'username');
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');

            $validSortFields = ['id', 'username', 'email', 'gender', 'mobile_number', 'level_r_id', 'inventory_r_id', 'players_ip_address', 'players_mac_address', 'players_os_type'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = Player::query();

            if ($globalFilter) {
                $query->where(function($query) use ($globalFilter) {
                    $query->where('username', 'like', "%{$globalFilter}%")
                          ->orWhere('email', 'like', "%{$globalFilter}%")
                          ->orWhere('mobile_number', 'like', "%{$globalFilter}%");
                });
            }

            $players = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $players->transform(function ($player) {
                return [
                    'id' => $player->id,
                    'username' => $player->username,
                    'email' => $player->email,
                    'gender' => $player->gender == 0 ? 'male' : 'female',
                    'mobile_number' => $player->mobile_number,
                    'players_ip_address' => $player->players_ip_address,
                    'players_mac_address' => $player->players_mac_address,
                    'players_os_type' => $player->players_os_type,
                ];
            });

            return response()->json([
                'status' => 'success',
                'current_page' => $players->currentPage(),
                'last_page' => $players->lastPage(),
                'next_page' => $players->currentPage() < $players->lastPage() ? $players->currentPage() + 1 : null,
                'prev_page' => $players->currentPage() > 1 ? $players->currentPage() - 1 : null,
                'next_page_url' => $players->nextPageUrl(),
                'prev_page_url' => $players->previousPageUrl(),
                'per_page' => $players->perPage(),
                'total' => $players->total(),
                'data' => $players->items(),
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
            // Validation rules
            $validator = Validator::make($request->all(), [
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    'unique:hd_players',
                    'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', // Regex to ensure valid domain like .com, .net, etc.
                ],
                'username' => 'required|string|min:4|max:13',
                'password' => 'required|string|min:6',
                'gender' => 'required|integer|in:1,2',
                'mobile_number' => 'nullable|string|max:50',
                'players_ip_address' => 'nullable|string|max:30',
                'players_mac_address' => 'nullable|string|max:30',
                'players_os_type' => 'nullable|integer',
                // 'level_id' => 'required|exists:hc_levels,id', // Ensure level_id exists in hc_levels table
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                    'error_code' => 'INPUT_VALIDATION_ERROR',
                ], 422);
            }

            // Check if email or username already exists
            $emailExists = Player::where('email', $request->email)->exists();
            $usernameExists = Player::where('username', $request->username)->exists();

            if ($emailExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email already used',
                    'error_code' => 'EMAIL_ALREADY_USED',
                ], 422);
            }

            if ($usernameExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Username already used',
                    'error_code' => 'USERNAME_ALREADY_USED',
                ], 422);
            }
            $level = HrLevelPlayer::create([
                'level_id' =>"1",
            ]);
            $inventory = HrInventoryPlayer::create();

            // Create the player
            $player = Player::create([
                'email' => $request->email,
                'username' => $request->username,
                'gender' => $request->gender,
                'mobile_number' => $request->mobile_number,
                'password' => Hash::make($request->password),
                'players_ip_address' => $request->players_ip_address,
                'players_mac_address' => $request->players_mac_address,
                'players_os_type' => $request->players_os_type,
                'picture' => $request->picture,
                'level_r_id'=> $level->id,
                'inventory_r_id' => $inventory->id,
                // 'weapon_r_id' => $weapon->id,
            ]);

            $player->makeHidden(['password']);
            $code = strtoupper(Str::random(3)) . rand(100, 999);
            $refferer= HrReferrerCode::create([
                'code'=>$code,
                'player_id'=>$player->id,
                'modified_by'=>$player->id,
                'created_by'=>$player->id,
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $player,
                'level_player' => $level,
                'inventory_player' =>$inventory
            ], 201);
        } catch (\Exception $e) {
            // Rollback any changes if needed
            if (isset($player)) {
                $player->delete();
            }

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {

            $player = Player::find($id);
            if(!$player){
                return response()->json(['status' => 'error', 'message' => 'Player not found', 'error_code' => 'NOT_FOUND'], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $player,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            // Validate request data
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email|max:255',
                'username' => 'required|string|min:4|max:13',
                'gender' => 'required|integer|in:1,2',
                'mobile_number' => 'nullable|string|max:50',
                'password' => 'nullable|string|min:6',
                'players_ip_address' => 'nullable|string|max:30',
                'players_mac_address' => 'nullable|string|max:30',
                'players_os_type' => 'nullable|integer',
                'picture' => 'nullable|string',
                'level_id' => 'nullable|exists:hc_levels,id', // Optional if updating level
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                    'error_code' => 'INPUT_VALIDATION_ERROR',
                ], 422);
            }

            // Check if user is authenticated
            $player = Player::find($id);
            if (!$player) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Player not found',
                    'error_code' => 'PLAYER_NOT_FOUND',
                ], 404);
            }

            // Update player data
            $playerData = $request->only([
                'email',
                'username',
                'gender',
                'mobile_number',
                'players_ip_address',
                'players_mac_address',
                'players_os_type',
                'picture',
            ]);

            if ($request->has('password')) {
                $playerData['password'] = Hash::make($request->password);
            }

            $player->update($playerData);

            // Update LevelPlayer if level_id is provided
            if ($request->has('level_id')) {
                $levelPlayer = LevelPlayer::where('player_id', $player->id)->first();

                if ($levelPlayer) {
                    $levelPlayer->update([
                        'level_id' => $request->level_id,
                    ]);
                } else {
                    LevelPlayer::create([
                        'player_id' => $player->id,
                        'level_id' => $request->level_id,
                        'exp' => 0, // Set default experience or any logic you need
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $player,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $player = Player::find($id);

            if ($player) {
                $player->delete();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Player has been deleted.',
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Player not found.',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function deleteself()
    {
        try {
            $id = Auth::id();
            $player = Player::findOrFail($id);
            if ($player) {
                $player->delete();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Player has been deleted.',
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Player not found.',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function Unauthorized()
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized',
            'error_code' => 'UNAUTHORIZED',
        ], 401);
    }

    public function topup(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$playerId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized, please login again',
                    'error_code' => 'USER_NOT_FOUND',
                ], 401);
            };
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric',
                'currency_id' => 'required|exists:hc_currencies,id',

                // Add more validation rules if necessary
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                    'error_code' => 'INPUT_VALIDATION_ERROR',
                ], 422);
            }

            $wallet = HdWallet::create([
                'player_id' => $playerId,
                'amount' => $request->input('amount'),
                'currency_id' => $request->input('currency_id'),
                'label' => 'topup',
                'category' => 'topup',
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $wallet,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function load(){
        try{
            $map = HcMap::with(['missions', 'rewards'])->get();
            $characterRole =HcCharacterRole::get();
            $weapon = HcWeapon::get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'map' => $map,
                    'character_role' => $characterRole,
                    'weapon' => $weapon,
                ]
            ], 200);



        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
