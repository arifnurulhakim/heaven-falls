<?php

namespace App\Http\Controllers;

use App\Models\HdFriendlist;
use App\Models\HrReferrerCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Events\FriendListUpdated;
use App\Events\FriendInvitesUpdated;



class HdFriendlistController extends Controller
{
    // public function friendList()
    // {
    //     try {
    //         $user = Auth::user();
    //         $offset = request()->get('offset', 0);
    //         $limit = request()->get('limit', 10);
    //         $search = request()->get('search', '');
    //         $getOrder = request()->get('order', '');
    //         $defaultOrder = $getOrder ? $getOrder : "id ASC";
    //         $orderMappings = [
    //             'idASC' => 'id ASC',
    //             'idDESC' => 'id DESC',
    //             'nameASC' => 'hd_players.username ASC',
    //             'nameDESC' => 'hd_players.username DESC',
    //         ];

    //         $order = $orderMappings[$getOrder] ?? $defaultOrder;
    //         $validOrderValues = implode(',', array_keys($orderMappings));
    //         $rules = [
    //             'offset' => 'integer|min:0',
    //             'limit' => 'integer|min:1',
    //             'order' => "in:$validOrderValues",
    //         ];

    //         $validator = Validator::make([
    //             'offset' => $offset,
    //             'limit' => $limit,
    //             'order' => $getOrder,
    //         ], $rules);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'status' => 'ERROR',
    //                 'message' => 'Invalid input parameters',
    //                 'errors' => $validator->errors(),
    //             ], 400);
    //         }

    //         // Build the query
    //         $query = HdFriendList::orderByRaw($order)
    //             ->leftJoin('hd_players', 'hd_friend_lists.player_id', '=', 'hd_players.id')
    //             ->where(function ($query) use ($user) {
    //                 $query->where('hd_friend_lists.player_id', $user->id)
    //                     ->orWhere('hd_friend_lists.friend_id', $user->id);
    //             })
    //             ->where('hd_friend_lists.accepted', true)
    //             ->select(
    //                 'hd_friend_lists.*',
    //                 'hd_players.username as friend_name'
    //             );

    //         if ($search !== '') {
    //             $query->where('hd_players.username', 'like', "%$search%");
    //         }

    //         // Get the total count before applying offset/limit
    //         $total_data = $query->count();

    //         // Apply offset and limit
    //         $friendlist = $query->offset($offset)->limit($limit)->get();

    //         return response()->json([
    //             'status' => 'success',
    //             'offset' => $offset,
    //             'limit' => $limit,
    //             'order' => $getOrder,
    //             'search' => $search,
    //             'total_data' => $total_data,
    //             'data' => $friendlist,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }
   
public function friendList()
{
    try {
        $user = Auth::user();
        $offset = request()->get('offset', 0);
        $limit = request()->get('limit', 10);
        $search = request()->get('search', '');
        $getOrder = request()->get('order', '');
        $defaultOrder = $getOrder ? $getOrder : "id ASC";
        $orderMappings = [
            'idASC' => 'id ASC',
            'idDESC' => 'id DESC',
            'nameASC' => 'hd_players.username ASC',
            'nameDESC' => 'hd_players.username DESC',
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

        // Query untuk friendlist
        $query = HdFriendlist::orderByRaw($order)
            ->leftJoin('hd_players', 'hd_friend_lists.player_id', '=', 'hd_players.id')
            ->where(function ($query) use ($user) {
                $query->where('hd_friend_lists.player_id', $user->id)
                    ->orWhere('hd_friend_lists.friend_id', $user->id);
            })
            ->where('hd_friend_lists.accepted', true)
            ->select(
                'hd_friend_lists.*',
                'hd_players.username as friend_name'
            );

        if ($search !== '') {
            $query->where('hd_players.username', 'like', "%$search%");
        }

        // Hitung total data
        $total_data = $query->count();

        // Ambil data friendlist
        $friendlist = $query->offset($offset)->limit($limit)->get();

        // **Broadcast ke WebSocket**
        event(new FriendListUpdated($friendlist, $user->id));

        return response()->json([
            'status' => 'success',
            'offset' => $offset,
            'limit' => $limit,
            'order' => $getOrder,
            'search' => $search,
            'total_data' => $total_data,
            'data' => $friendlist,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function invites()
{
    try {
        $user = Auth::user();
        $offset = request()->get('offset', 0);
        $limit = request()->get('limit', 10);
        $search = request()->get('search', '');
        $getOrder = request()->get('order', '');
        $defaultOrder = $getOrder ? $getOrder : "id ASC";
        $orderMappings = [
            'idASC' => 'id ASC',
            'idDESC' => 'id DESC',
            'nameASC' => 'hd_players.username ASC',
            'nameDESC' => 'hd_players.username DESC',
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

        // Build the query
        $query = HdFriendList::orderByRaw($order)
            ->leftJoin('hd_players', 'hd_friend_lists.player_id', '=', 'hd_players.id')
            ->where('hd_friend_lists.friend_id', $user->id)  // Only filter by friend_id
            ->where('hd_friend_lists.invited', true)
            ->select(
                'hd_friend_lists.*',
                'hd_players.username as friend_name'
            );

        if ($search !== '') {
            $query->where('hd_players.username', 'like', "%$search%");
        }

        // Get the total count before applying offset/limit
        $total_data = $query->count();

        // Apply offset and limit
        $invites = $query->offset($offset)->limit($limit)->get();

        // Trigger the event for friend invites update
        event(new FriendInvitesUpdated($invites, $user->id));

        return response()->json([
            'status' => 'success',
            'offset' => $offset,
            'limit' => $limit,
            'order' => $getOrder,
            'search' => $search,
            'total_data' => $total_data,
            'data' => $invites,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    public function invited()
    {
        try {
            $user = Auth::user();
            $offset = request()->get('offset', 0);
            $limit = request()->get('limit', 10);
            $search = request()->get('search', '');
            $getOrder = request()->get('order', '');
            $defaultOrder = $getOrder ? $getOrder : "id ASC";
            $orderMappings = [
                'idASC' => 'id ASC',
                'idDESC' => 'id DESC',
                'nameASC' => 'hd_players.username ASC',
                'nameDESC' => 'hd_players.username DESC',
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

            // Build the query
            $query = HdFriendList::orderByRaw($order)
                ->leftJoin('hd_players', 'hd_friend_lists.player_id', '=', 'hd_players.id')
                ->where('hd_friend_lists.player_id', $user->id)  // Only filter by player_id
                ->where('hd_friend_lists.invited', true)
                ->select(
                    'hd_friend_lists.*',
                    'hd_players.username as friend_name'
                );

            if ($search !== '') {
                $query->where('hd_players.username', 'like', "%$search%");
            }

            // Get the total count before applying offset/limit
            $total_data = $query->count();

            // Apply offset and limit
            $invited = $query->offset($offset)->limit($limit)->get();

            return response()->json([
                'status' => 'success',
                'offset' => $offset,
                'limit' => $limit,
                'order' => $getOrder,
                'search' => $search,
                'total_data' => $total_data,
                'data' => $invited,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function blocked()
    {
        try {
            $user = Auth::user();
            $offset = request()->get('offset', 0);
            $limit = request()->get('limit', 10);
            $search = request()->get('search', '');
            $getOrder = request()->get('order', '');
            $defaultOrder = $getOrder ? $getOrder : "id ASC";
            $orderMappings = [
                'idASC' => 'id ASC',
                'idDESC' => 'id DESC',
                'nameASC' => 'hd_players.username ASC',
                'nameDESC' => 'hd_players.username DESC',
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

            // Build the query
            $query = HdFriendList::orderByRaw($order)
                ->leftJoin('hd_players', 'hd_friend_lists.player_id', '=', 'hd_players.id')
                ->where('hd_friend_lists.blocked_by', $user->id)
                ->select(
                    'hd_friend_lists.*',
                    'hd_players.username as friend_name'
                );

            if ($search !== '') {
                $query->where('hd_players.username', 'like', "%$search%");
            }

            // Get the total count before applying offset/limit
            $total_data = $query->count();

            // Apply offset and limit
            $blocked = $query->offset($offset)->limit($limit)->get();

            return response()->json([
                'status' => 'success',
                'offset' => $offset,
                'limit' => $limit,
                'order' => $getOrder,
                'search' => $search,
                'total_data' => $total_data,
                'data' => $blocked,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function share()
    {
        try {
            $user = Auth::user();

            // Check if the user already has a referral code
            $referrer = HrReferrerCode::where('player_id', $user->id)->first();

            if (!$referrer) {
                // Generate a unique referral code
                do {
                    $code = strtoupper(Str::random(3)) . rand(100, 999);
                } while (HrReferrerCode::where('code', $code)->exists());

                // Create a new referral code entry
                $referrer = HrReferrerCode::create([
                    'code' => $code,
                    'player_id' => $user->id,
                    'modified_by' => $user->id,
                    'created_by' => $user->id,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Referral code generated successfully.',
                    'data' => [
                        'username' => $user->username,
                        'code' => $referrer->code,
                        'url' => env('APP_URL') . "referrer/" . $referrer->code,
                    ],
                ], 201); // Status code 201 for created
            }

            // Referral code already exists
            return response()->json([
                'status' => 'info',
                'message' => 'Referral code already exists.',
                'data' => [
                    'username' => $user->username,
                    'code' => $referrer->code,
                    'url' => env('APP_URL') . "referrer/" . $referrer->code,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while processing the referral code.',
                'error_code' => 'INTERNAL_ERROR',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
    public function search($code)
    {
        try {
            $user = HrReferrerCode::where('code',$code)->leftJoin('hd_players', 'hd_friend_lists.friend_id', '=', 'hd_players.id')->first();

            return response()->json([
                'status' => 'success',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function invite(Request $request)
    {
        try {
            // Validasi permintaan
            $validator = Validator::make($request->all(), [
                'code' => 'required|string|max:255',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 400);
            }
    
            // Dapatkan user yang sedang login
            $user = Auth::user();
    
            // Temukan player berdasarkan kode referral
            $player = HrReferrerCode::where('player_id', $user->id)->first();
            if (!$player) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Player not found.',
                ], 404);
            }
    
            // Temukan teman berdasarkan kode referral
            $friend = HrReferrerCode::where('code', $request->code)->first();
            if (!$friend) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Friend not found.',
                ], 404);
            }
    
            // Pastikan player tidak menambahkan dirinya sendiri sebagai teman
            if ($player->player_id === $friend->player_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You cannot add yourself as a friend.',
                ], 400);
            }
    
            // Cek apakah sudah ada hubungan pertemanan
            $existingFriendship = HdFriendList::where(function ($query) use ($player, $friend) {
                $query->where('player_id', $player->player_id)
                      ->where('friend_id', $friend->player_id)
                      ->orWhere('player_id', $friend->player_id)
                      ->where('friend_id', $player->player_id);
            })->first();
    
            if ($existingFriendship) {
                // Tangani beberapa kondisi berdasarkan status pertemanan saat ini
                if ($existingFriendship->invited == true) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Friendship already requesting.',
                    ], 400);
                }
                if ($existingFriendship->accepted == true) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Already friends.',
                    ], 400);
                }
                if ($existingFriendship->blocked_by == $friend->player_id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'This player blocked you.',
                    ], 400);
                }
                if ($existingFriendship->blocked_by == $player->player_id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You blocked this player.',
                    ], 400);
                }
                if ($existingFriendship->ignored == true) {
                    $existingFriendship->invited = true;
                    $existingFriendship->ignored = false;
                    $existingFriendship->save();
                }
                if ($existingFriendship->removed == true) {
                    $existingFriendship->invited = true;
                    $existingFriendship->removed = false;
                    $existingFriendship->save();
                }
            } else {
                // Jika tidak ada hubungan pertemanan, buat entri baru
                $existingFriendship = HdFriendList::create([
                    'player_id' => $player->player_id,
                    'friend_id' => $friend->player_id,
                    'lobby_code' => $player->code,
                    'invited' => true,
                    'created_by' => $user->id,
                    'modified_by' => $user->id,
                ]);
            }
    
            // Ambil daftar undangan terbaru setelah menambah teman
            $invites = HdFriendList::leftJoin('hd_players', 'hd_friend_lists.player_id', '=', 'hd_players.id')
            ->where('hd_friend_lists.friend_id', $friend->player_id)  // Only filter by friend_id
            ->where('hd_friend_lists.invited', true)
            ->select(
                'hd_friend_lists.*',
                'hd_players.username as friend_name'
            )->get();

            // dd($invites);
            $userId =$friend->player_id;
    
            // Kirimkan event Pusher dengan daftar undangan terbaru
            event(new FriendInvitesUpdated($invites, $userId));
    
            return response()->json([
                'status' => 'success',
                'message' => 'Friend added successfully.',
                'data' => $existingFriendship,
            ], 201); // Status code 201 untuk data yang baru dibuat
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while adding the friend.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function accept($id)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Find the friend list entry by the provided ID
            $friendlist = HdFriendList::find($id);
            if (!$friendlist) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Friend list entry not found.',
                ], 404);
            }

            // Validate that the authenticated user is the one who was invited (friend_id)
            if ($friendlist->friend_id !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => "It's not your invitation.",
                ], 400);
            }

            // Update the invitation status to accepted
            $friendlist->invited = false;
            $friendlist->accepted = true;
            $friendlist->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Invitation accepted successfully.',
                'data' => $friendlist,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while accepting the invitation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function ignore($id)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Find the friend list entry by the provided ID
            $friendlist = HdFriendList::find($id);
            if (!$friendlist) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Friend list entry not found.',
                ], 404);
            }

            // Validate that the authenticated user is the one who was invited (friend_id)
            if ($friendlist->friend_id !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => "It's not your invitation.",
                ], 400);
            }

            // Update the invitation status to accepted
            $friendlist->invited = false;
            $friendlist->ignored = true;
            $friendlist->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Invitation ignored successfully.',
                'data' => $friendlist,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while ignoring the invitation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function block($id)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Find the friend list entry by the provided ID
            $friendlist = HdFriendList::find($id);
            if (!$friendlist) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Friend list entry not found.',
                ], 404);
            }
            if($friendlist->accepted == false){
                return response()->json([
                    'status' => 'error',
                    'message' => 'this player is not your friend, you cannot block it',
                ], 404);
            }
            // Update the invitation status to accepted
            $friendlist->accepted = false;
            $friendlist->blocked_by = $user->id;
            $friendlist->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Invitation ignored successfully.',
                'data' => $friendlist,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while ignoring the invitation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function unblock($id)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Find the friend list entry by the provided ID
            $friendlist = HdFriendList::find($id);
            if (!$friendlist) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Friend list entry not found.',
                ], 404);
            }
            if($friendlist->accepted == true){
                return response()->json([
                    'status' => 'error',
                    'message' => 'this player is not blocked',
                ], 404);
            }
            // Update the invitation status to accepted
            $friendlist->accepted = true;
            $friendlist->blocked_by = null;
            $friendlist->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Invitation ignored successfully.',
                'data' => $friendlist,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while ignoring the invitation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function unblockAll()
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Find all friend list entries where the user is the friend (friend_id)
            $friendlist = HdFriendList::where('blocked_by', $user->id)
                                      ->get();

            // Check if any friend list entries were found
            if ($friendlist->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No invitations to ignore.',
                ], 404);
            }

            // Loop through all friend list entries and mark them as ignored
            foreach ($friendlist as $entry) {
                // Update the invitation status
                $entry->blocked_by = null;
                $entry->accepted = true;
                $entry->save();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'All invitations ignored successfully.',
                'data' => $friendlist,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while ignoring the invitations.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function remove($id)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Find the friend list entry by the provided ID
            $friendlist = HdFriendList::find($id);
            if (!$friendlist) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Friend list entry not found.',
                ], 404);
            }
            if($friendlist->accepted == false){
                return response()->json([
                    'status' => 'error',
                    'message' => 'this player is not on your friendlist',
                ], 404);
            }
            // Update the invitation status to accepted
            $friendlist->accepted = false;
            $friendlist->removed = true;
            $friendlist->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Invitation ignored successfully.',
                'data' => $friendlist,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while ignoring the invitation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function ignoreAll()
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Find all friend list entries where the user is the friend (friend_id)
            $friendlist = HdFriendList::where('friend_id', $user->id)
                                      ->where('invited', true)  // Only process invitations that are active
                                      ->get();

            // Check if any friend list entries were found
            if ($friendlist->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No invitations to ignore.',
                ], 404);
            }

            // Loop through all friend list entries and mark them as ignored
            foreach ($friendlist as $entry) {
                // Update the invitation status
                $entry->invited = false;
                $entry->ignored = true;
                $entry->save();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'All invitations ignored successfully.',
                'data' => $friendlist,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while ignoring the invitations.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


}
