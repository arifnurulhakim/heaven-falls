<?php

namespace App\Http\Controllers;

use App\Models\HcTopup;
use App\Models\HcTopupCurrency;
use App\Models\HdWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class HcTopupController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('pageSize', 10);
            $sortDirection = $request->input('sortDirection', 'asc');
            $globalFilter = $request->input('globalFilter', '');
            $sortField = $request->input('sortField', 'id');

            $validSortFields = ['id', 'image','name_topup', 'amount', 'currency_id','product_code'];
            if (!in_array($sortField, $validSortFields)) {
                return response()->json(['status' => 'ERROR', 'message' => 'Invalid sort field'], 400);
            }

            $query = HcTopup::with(['currency', 'creator', 'modifier', 'topupCurrencies']);

            if ($globalFilter) {
                $query->where('name_topup', 'like', "%{$globalFilter}%");
            }

            $topups = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

            $topups->transform(function ($topup) {
                return [
                    'id' => $topup->id,
                    'name_topup' => $topup->name_topup,
                    'amount' => $topup->amount,
                    'image' => $topup->image,
                    'image_url' => env("ASSET_URL").$topup->image,
                    'product_code' => $topup->product_code,
                    'currency' => $topup->currency ? $topup->currency->name : null,
                    'creator' => $topup->creator ? $topup->creator->name : null,
                    'modifier' => $topup->modifier ? $topup->modifier->name : null,
                    'topup_currencies' => $topup->topupCurrencies->map(function ($topupCurrency) {
                        return [
                            'currency_id' => $topupCurrency->currency_id,
                            'name' => $topupCurrency->currency->name ?? null,
                            'code' => $topupCurrency->currency->code ?? null,
                            'price' => $topupCurrency->price_topup,
                        ];
                    }),
                ];
            });
            // dd(env("ASSET_URL"));

            return response()->json([
                'status' => 'success',
                'data' => $topups,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function show($id)
    {
        try {
            $topup = HcTopup::with('currency')->find($id);
            if(!$topup){
                return response()->json([
                    'status' => 'error',
                    'message' => 'topup not found.',
                ], 404);
            }


            return response()->json([
                'status' => 'success',
                'data' => $topup,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function store(Request $request)
    {
        try {
            // Validasi request
            $validator = Validator::make($request->all(), [
                'is_active' => 'required|boolean',
                'is_in_shop' => 'required|boolean',
                'name_topup' => 'required|string|max:255',
                'description' => 'nullable|string',
                'product_code' => 'nullable|string|max:255',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5048',
                'amount' => 'required|integer|min:1',
                'currency_id' => 'required|exists:hc_currencies,id',
                'topup_currencies' => 'required|array|min:1',
                'topup_currencies.*.currency_id' => 'required|exists:hc_currencies,id',
                'topup_currencies.*.price_topup' => 'required|numeric|min:0',
                'created_by' => 'nullable|integer',
                'modified_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }
            $imageUrl = null;

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = 'topup-' . time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/topup'), $imageName);

                // Save image URL in the database
                $imageUrl = 'images/topup/' . $imageName;
            }
            $product_code = $request->product_code;

            if(!$product_code){
                $type = $request->currency_id == 1 ? 'buycoin_' : 'buycrystal_';
                $product_code = 'com.plexustechdev.heavenfalls.'.$type.$request->amount;
            }


            // Simpan data ke tabel hc_topup
            $topup = HcTopup::create([
                'is_active' => $request->is_active,
                'is_in_shop' => $request->is_in_shop,
                'name_topup' => $request->name_topup,
                'description' => $request->description,
                'product_code' => $product_code,
                'image' => $imageUrl,
                'amount' => $request->amount,
                'currency_id' => $request->currency_id,
                'created_by' => $request->created_by,
                'modified_by' => $request->modified_by,
            ]);

            // Simpan data ke tabel hc_topup_currency
            foreach ($request->topup_currencies as $currency) {
                HcTopupCurrency::create([
                    'topup_id' => $topup->id,
                    'currency_id' => $currency['currency_id'],
                    'price_topup' => $currency['price_topup'],
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Topup created successfully',
                'data' => $topup->load('topupCurrencies')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred.',
                'error_code' => 'INTERNAL_ERROR',
                'error_details' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // Validasi request
            $validator = Validator::make($request->all(), [
                'is_active' => 'nullable|boolean',
                'is_in_shop' => 'nullable|boolean',
                'name_topup' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'product_code' => 'nullable|string|max:255',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5048',
                'amount' => 'nullable|integer|min:1',
                'currency_id' => 'nullable|exists:hc_currencies,id',
                'topup_currencies' => 'nullable|array|min:1',
                'topup_currencies.*.currency_id' => 'nullable|exists:hc_currencies,id',
                'topup_currencies.*.price_topup' => 'nullable|numeric|min:0',
                'modified_by' => 'nullable|integer',
            ]);
              if (!$request->all()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'At least one field must be provided for update.',
                    'error_code' => 'VALIDATION_ERROR'
                ], 422);
            }


            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Cari data topup
            $topup = HcTopup::find($id);
            if (!$topup) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Topup not found'
                ], 404);
            }
            $imageUrl = $topup->image;

            if ($request->hasFile('image')) {
                // Delete the old image if it exists
                if ($topup->image) {
                    $oldImagePath = public_path($topup->image);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                // Upload and update the new image
                $image = $request->file('image');
                $imageName = 'topup-' . time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/topup'), $imageName);

                // Update the image URL
                $imageUrl = 'images/topup/' . $imageName;
            }

            // Update data topup
            $topup->update([
                'is_active' => $request->is_active,
                'is_in_shop' => $request->is_in_shop,
                'name_topup' => $request->name_topup,
                'description' => $request->description,
                'product_code' => $request->product_code,
                'image' => $imageUrl,
                'amount' => $request->amount,
                'currency_id' => $request->currency_id,
                'modified_by' => $request->modified_by,
            ]);

            // Hapus semua data lama di hc_topup_currencies
            HcTopupCurrency::where('topup_id', $topup->id)->delete();

            // Simpan data baru ke hc_topup_currencies
            foreach ($request->topup_currencies as $currency) {
                HcTopupCurrency::create([
                    'topup_id' => $topup->id,
                    'currency_id' => $currency['currency_id'],
                    'price_topup' => $currency['price_topup'],
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Topup updated successfully',
                'data' => $topup->load('topupCurrencies')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred.',
                'error_code' => 'INTERNAL_ERROR',
                'error_details' => $e->getMessage()
            ], 500);
        }
    }
    public function destroy($id)
    {
        try {
            $topup = HcTopup::find($id);
            if(!$topup){
                return response()->json([
                    'status' => 'error',
                    'message' => 'topup not found.',
                ], 404);
            }
            $topup->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'topup deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function shopTopup()
    {
        try {
            // Ambil data dengan relasi
            $topups = HcTopup::with(['currency', 'topupCurrencies.currency'])->get();

            // Transformasi hasil
            $topups = $topups->map(function ($topup) {
                return [
                    'id' => $topup->id,
                    'name_topup' => $topup->name_topup,
                    'amount' => $topup->amount,
                    'product_code' => $topup->product_code,
                    'currency' => $topup->currency ? $topup->currency->name : null,
                    'topup_currencies' => $topup->topupCurrencies->map(function ($topupCurrency) {
                        return [
                            'id' => $topupCurrency->id,
                            'currency_id' => $topupCurrency->currency_id,
                            'name' => $topupCurrency->currency->name ?? null,
                            'code' => $topupCurrency->currency->code ?? null,
                            'price' => $topupCurrency->price_topup,
                        ];
                    }),
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $topups,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function purchaseTopup(Request $request)
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
            }

            // Validasi request
            $validator = Validator::make($request->all(), [
                'topup_currency_id' => 'required|exists:hc_topup_currencies,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                    'error_code' => 'INPUT_VALIDATION_ERROR',
                ], 422);
            }

            // Ambil data topup currency beserta relasinya
            $topupCurrency = HcTopupCurrency::with('topup')->find($request->topup_currency_id);

            if (!$topupCurrency) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Topup currency not found',
                    'error_code' => 'TOPUP_CURRENCY_NOT_FOUND',
                ], 404);
            }

            $topup = $topupCurrency->topup;
            if (!$topup) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Topup data not found',
                    'error_code' => 'TOPUP_NOT_FOUND',
                ], 404);
            }

            // Jika currency_id bukan 4, cek saldo
            if ($topupCurrency->currency_id != 4) {
                $totalBalance = HdWallet::where('player_id', $playerId)
                    ->where('currency_id', $topupCurrency->currency_id)
                    ->sum('amount');

                if ($totalBalance < $topupCurrency->price_topup) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Insufficient balance',
                        'error_code' => 'INSUFFICIENT_BALANCE',
                        'available_balance' => $totalBalance,
                        'required_balance' => $topupCurrency->price_topup,
                    ], 400);
                }
            }

            // Simpan transaksi penambahan (topup)
            HdWallet::create([
                'player_id' => $playerId,
                'amount' => $topup->amount,
                'currency_id' => $topup->currency_id,
                'label' => 'Purchase from shop',
                'category' => 'shop',
                'created_by'  => $playerId,
                'modified_by' => $playerId,
            ]);

            // Simpan transaksi pengurangan (pembayaran)
            if ($topupCurrency->currency_id != 4) {
            HdWallet::create([
                'player_id' => $playerId,
                'amount' => $topupCurrency->price_topup * -1, // Nilai negatif
                'currency_id' => $topupCurrency->currency_id,
                'label' => 'Purchase from shop',
                'category' => 'shop',
                'created_by'  => $playerId,
                'modified_by' => $playerId,
            ]);
        }

            return response()->json([
                'status' => 'success',
                'message' => 'Topup successfully purchased',
                'remaining_balance' => HdWallet::where('player_id', $playerId)
                    ->where('currency_id', $topupCurrency->currency_id)
                    ->sum('amount'),
                'topup_currency' => $topupCurrency,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred.',
                'error_code' => 'INTERNAL_ERROR',
                'error_details' => $e->getMessage(),
            ], 500);
        }
    }

}
