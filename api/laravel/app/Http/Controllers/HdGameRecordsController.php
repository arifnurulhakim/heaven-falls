<?php

namespace App\Http\Controllers;

use App\Models\HcMap;
use App\Models\HdGameRecords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class HdGameRecordsController extends Controller
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
                'timeASC' => 'time ASC',
                'timeDESC' => 'time DESC',
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
            $records = HdGameRecords::orderByRaw($order)->where('player_id', $user->id);
            $total_data = $records->count();

            if ($search !== '') {
                $records->where(function ($query) use ($search) {
                    $query->where('id', 'like', "%$search%")
                          ->orWhere('time', 'like', "%$search%");
                });
            }

            $records = $records->offset($offset)->limit($limit)->get();

            return response()->json([
                'status' => 'success',
                'offset' => $offset,
                'limit' => $limit,
                'order' => $getOrder,
                'search' => $search,
                'total_data' => $total_data,
                'data' => $records,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'kill' => 'required|integer',
                'time' => 'required|date_format:H:i:s', // Validasi format durasi HH:MM:SS
                'map_id' => 'required|exists:hc_maps,id',
                'win_or_lose' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }

            $user = Auth::user(); // Mendapatkan pengguna yang sedang login
            $data = $request->all();
            $data['player_id'] = $user->id;

            // Membuat record baru
            $record = HdGameRecords::create($data);

            // Update win_liberation_int atau lose_liberation_int berdasarkan win_or_lose
            $maps = HcMap::find($request->map_id);
            if ($maps) {
                if ($request->win_or_lose) {
                    $maps->increment('win_liberation');
                } else {
                    $maps->increment('lose_liberation');
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $record,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    private function convertDurationToSeconds($duration)
    {
        // Pisahkan durasi berdasarkan ":"
        $timeParts = explode(':', $duration);

        // Pastikan jumlah bagian yang dihasilkan adalah 3 (HH:MM:SS)
        if (count($timeParts) !== 3) {
            throw new \Exception('Format durasi tidak valid. Harus dalam format HH:MM:SS');
        }

        // Ambil bagian jam, menit, dan detik
        $hours = (int) $timeParts[0];     // Jam
        $minutes = (int) $timeParts[1];   // Menit
        $seconds = (int) $timeParts[2];   // Detik

        // Cek apakah bagian jam, menit, dan detik dalam rentang yang valid
        if ($hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59 || $seconds < 0 || $seconds > 59) {
            throw new \Exception('Durasi tidak valid. Pastikan jam antara 00-23, menit dan detik antara 00-59');
        }

        // Mengonversi durasi ke detik
        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }
    public function indexPlayer(Request $request)
    {
        try {
            $user = Auth::user();

            // Ambil semua data berdasarkan player_id
            $records = HdGameRecords::where('player_id', $user->id)
                ->with('map') // Include map relationship
                ->get();

            if ($records->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No records found for this player.',
                    'data' => [],
                ], 200);
            }

            // Kelompokkan data berdasarkan map_id
            $groupedRecords = $records->groupBy('map_id');

            // Format data untuk setiap map
            $mapsData = $groupedRecords->map(function ($mapRecords, $mapId) {
                $bestKill = $mapRecords->max('kill');
                $bestTime = $mapRecords->min('time');
                $mapName = $mapRecords->first()->map->name; // Ambil nama map dari salah satu record

                return [
                    'map_id' => $mapId,
                    'maps_name' => $mapName,
                    'best_kill' => $bestKill,
                    'best_time' => $bestTime,
                    'records' => $mapRecords->toArray(),
                ];
            })->values();

            return response()->json([
                'status' => 'success',
                'data' => $mapsData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function showPlayer(Request $request)
    {
        try {
            $user = Auth::user();

            // Validasi input
            $validator = Validator::make($request->all(), [
                'map_id' => 'required|exists:hc_maps,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 422);
            }

            $mapId = $request->map_id;

            // Get all records for the player on the specified map
            $records = HdGameRecords::where('player_id', $user->id)
                ->where('map_id', $mapId)
                ->with('map') // Include map relationship
                ->get();

            if ($records->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No records found for this map.',
                    'data' => [],
                ], 200);
            }

            // Latest record
            $latestRecord = $records->sortByDesc('time')->first();
            $latestKill = $latestRecord->kill;
            $latestTime = $latestRecord->time;

            // Best kill (highest kill)
            $bestKill = $records->max('kill');

            // Best time (earliest time)
            $bestTime = $records->min('time');

            // Get map name from the latest record
            $mapName = $latestRecord->map->name;

            // Response structure
            $gameRecord = [
                'map' => $latestRecord->map, // Include map details
                'maps_name' => $mapName,
                'latest_kill' => $latestKill,
                'best_kill' => $bestKill,
                'latest_time' => $latestTime,
                'best_time' => $bestTime,
                'records' => $records, // All player records on this map
            ];

            return response()->json([
                'status' => 'success',
                'data' => $gameRecord,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
