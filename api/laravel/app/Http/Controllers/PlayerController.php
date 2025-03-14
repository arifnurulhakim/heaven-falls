<?php

namespace App\Http\Controllers;


use App\Models\Level;
use App\Models\HrLevelPlayer;
use App\Models\HdCharacterPlayer;
use App\Models\HdWeaponPlayer;
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
use App\Models\HrSubscriptionPurchase;
use App\Models\HrExpSubscription;
use App\Models\HdSubscription;
use App\Models\HrBattlepassPurchase;
use App\Models\HrExpBattlepass;
use App\Models\HrPeriodBattlepass;
use App\Models\HrPeriodSubscription;
use App\Models\HdBattlepass;
use App\Models\HrPlayerLastSeen;
use App\Models\HcTypeWeapon;
use App\Models\HcCurrency;
use App\Models\HcMap;
use App\Models\HcCharacterRole;
use App\Events\Friendlist;
use Intervention\Image\Facades\Image;

class PlayerController extends Controller
{

    public function login(Request $request)
    {
        try {
            // Validasi awal untuk memastikan `email_or_username` dan `password` ada
            $validator = Validator::make($request->all(), [
                'email_or_username' => 'required',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                    'error_code' => 'INPUT_VALIDATION_ERROR',
                ], 422);
            }

            // Cek apakah input adalah email atau username
            $input = $request->email_or_username;

            if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
                // Validasi jika input adalah email
                $validator = Validator::make($request->all(), [
                    'email_or_username' => 'email|exists:hd_players,email',
                ], [
                    'email_or_username.exists' => 'The provided email does not exist in our records.',
                ]);
            } else {
                // Validasi jika input adalah username
                $validator = Validator::make($request->all(), [
                    'email_or_username' => 'string|min:4|max:13|exists:hd_players,username',
                ], [
                    'email_or_username.exists' => 'The provided username does not exist in our records.',
                ]);
            }

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                    'error_code' => 'INPUT_VALIDATION_ERROR',
                ], 422);
            }

            // Menentukan kredensial berdasarkan input (email atau username)
            $credentials = filter_var($input, FILTER_VALIDATE_EMAIL)
                ? ['email' => $input, 'password' => $request->password]
                : ['username' => $input, 'password' => $request->password];

            Auth::shouldUse('player'); // Menggunakan guard "player"

            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email/Username or password invalid',
                    'error_code' => 'EMAIL_OR_USERNAME_INVALID',
                ], 401);
            }

            $player = Auth::user();
            $token = JWTAuth::fromUser($player);
            $lastSeen = HrPlayerLastSeen::where('player_id', $player->id)->first();
            if ($lastSeen) {
                // Jika data sudah ada, update last_seen menjadi null
                $lastSeen->update(['last_seen' => null]);
            } else {
                // Jika data belum ada, buat entri baru dengan last_seen = null
                HrPlayerLastSeen::create([
                    'player_id' => $player->id,
                    'last_seen' => null,
                ]);
            }

            $this->pushLastSeenEvent();


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
    public function pushLastSeenEvent()
    {
        $lastSeenData = HrPlayerLastSeen::join('hd_players', 'hr_player_last_seens.player_id', '=', 'hd_players.id')
            ->select(
                'hr_player_last_seens.player_id AS friend_id',
                'hr_player_last_seens.last_seen',
                'hd_players.username AS player_name',
                DB::raw("IF(hf_hr_player_last_seens.player_id IS NULL, 'offline', IF(hf_hr_player_last_seens.last_seen IS NULL, 'online', 'offline')) AS status")

            )
            ->get();

        if ($lastSeenData->isNotEmpty()) {
            event(new Friendlist($lastSeenData));
        } else {
            \Log::info("Tidak ada data last seen yang bisa dikirim.");
        }
    }
    public function offline()
    {
        $player = Auth::user();

        $lastSeen = HrPlayerLastSeen::where('player_id', $player->id)->first();
        if ($lastSeen) {
            // Jika data sudah ada, update last_seen menjadi null
            $lastSeen->update(['last_seen' => now()]);
        } else {
            // Jika data belum ada, buat entri baru dengan last_seen = null
            HrPlayerLastSeen::create([
                'player_id' => $player->id,
                'last_seen' => now(),
            ]);
        }
        $this->pushLastSeenEvent();
        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $player->id,
                'username' => $player->username,
                'last_seen' => now()
            ],
        ], 200);
    }
    public function updateprofile(Request $request)
    {
        try {
            // Validasi input dari permintaan
            $validator = Validator::make($request->all(), [
                'mobile_number' => 'nullable|string|max:50',
                'players_ip_address' => 'nullable|string|max:30',
                'players_mac_address' => 'nullable|string|max:30',
                'players_os_type' => 'nullable|integer',
                'summary' => 'nullable|string|max:255',
                'country_id' => 'nullable|exists:hc_countries,id',
                'state_id' => 'nullable|exists:hc_states,id',
                'real_name' => 'nullable|string|max:255',

            ]);

            // Tanggapi jika validasi gagal
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                    'error_code' => 'INPUT_VALIDATION_ERROR',
                ], 422);
            }

            // Ambil ID pemain yang sedang login
            $playerId = Auth::id();
            if (!$playerId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized, please login again',
                    'error_code' => 'USER_NOT_FOUND',
                ], 401);
            }

            // Temukan pemain berdasarkan ID
            $player = Player::find($playerId);
            if (!$player) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Player not found',
                    'error_code' => 'PLAYER_NOT_FOUND',
                ], 404);
            }

            // Update data pemain
            $player->update([

                'mobile_number' => $request->get('mobile_number', $player->mobile_number),
                'players_ip_address' => $request->get('players_ip_address', $player->players_ip_address),
                'players_mac_address' => $request->get('players_mac_address', $player->players_mac_address),
                'players_os_type' => $request->get('players_os_type', $player->players_os_type),
                'summary' => $request->get('summary', $player->summary),
                'country_id' => $request->get('country_id', $player->country_id),
                'state_id' => $request->get('state_id', $player->state_id),
                'real_name' => $request->get('real_name', $player->real_name),
            ]);

            // Berikan respons sukses
            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => $player,
            ], 200);
        } catch (\Exception $e) {
            // Tanggapi jika ada kesalahan server
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating profile',
                'error_code' => 'INTERNAL_SERVER_ERROR',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function updateAvatar(Request $request)
    {
        try {
            // Validasi input dari permintaan
            $validator = Validator::make($request->all(), [
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                    'error_code' => 'INPUT_VALIDATION_ERROR',
                ], 422);
            }

            // Ambil ID pemain yang sedang login
            $playerId = Auth::id();
            if (!$playerId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized, please login again',
                    'error_code' => 'USER_NOT_FOUND',
                ], 401);
            }

            // Temukan pemain berdasarkan ID
            $player = Player::find($playerId);
            if (!$player) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Player not found',
                    'error_code' => 'PLAYER_NOT_FOUND',
                ], 404);
            }

            $imageUrl = $player->avatar_url;

            if ($request->hasFile('image')) {
                // Hapus gambar lama jika ada
                if ($player->avatar_url) {
                    $oldImagePath = public_path($player->avatar_url);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $player_name = $player->username;

                // Ambil file gambar dari request
                $image = $request->file('image');
                $imageName = 'avatar-' . $player_name . '-' . time() . '.' . $image->getClientOriginalExtension();
                $imagePath = public_path('images/avatars/' . $imageName);

                // Buka gambar menggunakan GD Library
                $sourceImage = null;
                $extension = strtolower($image->getClientOriginalExtension());

                if ($extension == 'jpeg' || $extension == 'jpg') {
                    $sourceImage = imagecreatefromjpeg($image->getRealPath());
                } elseif ($extension == 'png') {
                    $sourceImage = imagecreatefrompng($image->getRealPath());
                } elseif ($extension == 'gif') {
                    $sourceImage = imagecreatefromgif($image->getRealPath());
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Unsupported image format',
                        'error_code' => 'UNSUPPORTED_FORMAT',
                    ], 400);
                }

                if (!$sourceImage) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to process image',
                        'error_code' => 'IMAGE_PROCESS_ERROR',
                    ], 500);
                }

                // Resize gambar ke 128x128
                $newWidth = 128;
                $newHeight = 128;
                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, imagesx($sourceImage), imagesy($sourceImage));

                // Simpan gambar dengan kompresi
                $quality = 80;
                if ($extension == 'jpeg' || $extension == 'jpg') {
                    imagejpeg($resizedImage, $imagePath, $quality);
                } elseif ($extension == 'png') {
                    $quality = 8; // PNG quality (0-9, semakin kecil semakin bagus)
                    imagepng($resizedImage, $imagePath, $quality);
                } elseif ($extension == 'gif') {
                    imagegif($resizedImage, $imagePath);
                }

                // Pastikan gambar kurang dari 150KB
                while (filesize($imagePath) > 150 * 1024 && $quality > 10) {
                    $quality -= 5;
                    if ($extension == 'jpeg' || $extension == 'jpg') {
                        imagejpeg($resizedImage, $imagePath, $quality);
                    } elseif ($extension == 'png') {
                        imagepng($resizedImage, $imagePath, round($quality / 10));
                    }
                }

                // Bersihkan memori
                imagedestroy($sourceImage);
                imagedestroy($resizedImage);

                // Update URL gambar
                $imageUrl = 'images/avatars/' . $imageName;
            }

            $player->update(['avatar_url' => $imageUrl]);

            return response()->json(['status' => 'success', 'data' => $player], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating profile',
                'error_code' => 'INTERNAL_SERVER_ERROR',
                'error' => $e->getMessage(),
            ], 500);
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
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized, please login again',
                    'error_code' => 'USER_NOT_FOUND',
                ], 401);
            }

            // Get user level details
            $level = HrLevelPlayer::where('hr_level_players.id', $user->level_r_id)
                ->leftJoin('hc_levels', 'hr_level_players.level_id', '=', 'hc_levels.id')
                ->select('hr_level_players.id', 'hr_level_players.level_id', 'hr_level_players.exp',  'hc_levels.name as level_name') // Include relevant fields
                ->first();
            $wallet = HcCurrency::leftJoin('hd_wallets', function ($join) use ($user) {
                $join->on('hc_currencies.id', '=', 'hd_wallets.currency_id')
                    ->where('hd_wallets.player_id', $user->id);
            })
                ->select(
                    'hc_currencies.name as currency_name',
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
            $skinCharacter = HdSkinCharacterPlayer::where('inventory_id', $user->inventory_r_id)->get();
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

            $refferer = HrReferrerCode::where('player_id', $user->id)->first();
            // $battlepassLevel = HdSubscription::where('period_battlepass_id', $period->id)->first();

            // Construct the response data
            $responseData = [
                'player' => $user,
                'level' => $level,
                'refferer_code' => $refferer->code,
                'wallet' => $wallet,
                'battlepass' => [
                    'purchase' => $battlepassPurchase ?? false,
                    'exp' => $battlepassExp,
                    'level' => $battlepassLvl,
                ],
                'subscription' => [
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

            $validSortFields = ['id', 'username', 'email', 'mobile_number', 'level_r_id', 'inventory_r_id', 'players_ip_address', 'players_mac_address', 'players_os_type'];

            if (!in_array($sortField, $validSortFields)) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid sort field'
                ], 400);
            }

            $query = Player::query();

            if ($globalFilter) {
                $query->where(function ($query) use ($globalFilter) {
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
                'mobile_number' => 'nullable|string|max:50',
                'players_ip_address' => 'nullable|string|max:30',
                'players_mac_address' => 'nullable|string|max:30',
                'players_os_type' => 'nullable|integer',
                // 'level_id' => 'required|exists:hc_levels,id', // Ensure level_id exists in hc_levels table
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => implode(', ', $validator->errors()->all()), // Menggabungkan semua error menjadi string
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
                'level_id' => "1",
            ]);
            $inventory = HrInventoryPlayer::create();
            // dd($inventory);

            // Create the player
            $player = Player::create([
                'email' => $request->email,
                'username' => $request->username,

                'mobile_number' => $request->mobile_number,
                'password' => Hash::make($request->password),
                'players_ip_address' => $request->players_ip_address,
                'players_mac_address' => $request->players_mac_address,
                'players_os_type' => $request->players_os_type,
                'picture' => $request->picture,
                'level_r_id' => $level->id,
                'inventory_r_id' => $inventory->id,
                // 'weapon_r_id' => $weapon->id,
            ]);
            $getInventory = HrInventoryPlayer::find($inventory->id);
            $weapon = HdWeaponPlayer::create([
                'inventory_id' => $getInventory->id,
                'weapon_id' => $getInventory->weapon_primary_r_id,
            ]);

            $character = HdCharacterPlayer::create([
                'inventory_id' => $getInventory->id,
                'character_id' => $getInventory->character_r_id,
            ]);

            $player->makeHidden(['password']);
            $code = strtoupper(Str::random(3)) . rand(100, 999);
            $refferer = HrReferrerCode::create([
                'code' => $code,
                'player_id' => $player->id,
                'modified_by' => $player->id,
                'created_by' => $player->id,
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $player,
                'level_player' => $level,
                'inventory_player' => $inventory
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
            if (!$player) {
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
            $playerId = $user->id;
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

    public function load()
    {
        try {
            $maps = HcMap::with(['missions.rewards'])->get();
            $characterRoles = HcCharacterRole::with(['characters.stat', 'characters.skins'])->get();
            $typeWeapon = HcTypeWeapon::with(['subType.weapon.stat', 'subType.weapon.skins'])->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'map' => $maps,
                    'character_role' => $characterRoles,
                    'type_weapon' => $typeWeapon,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
