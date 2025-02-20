<?php


use App\Http\Controllers\CodeCheckController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\HcCharacterController;
use App\Http\Controllers\HcCharacterPlayersController;
use App\Http\Controllers\HcInventoryController;
use App\Http\Controllers\HcPartSkinController;
use App\Http\Controllers\HcSubPartSkinController;
use App\Http\Controllers\HcCharacterRoleController;
use App\Http\Controllers\HrInventoryPlayersController;
use App\Http\Controllers\HrSkinCharacterPlayersController;
use App\Http\Controllers\HrSkinCharacterController;
use App\Http\Controllers\HcCurrencyController;
use App\Http\Controllers\HrCurrenciesShopController;
use App\Http\Controllers\HcWeaponController;
use App\Http\Controllers\HcTypeWeaponController;
use App\Http\Controllers\HdCharacterPlayersController;
use App\Http\Controllers\HdSkinCharacterPlayersController;
use App\Http\Controllers\HdMissionRewardController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\HdMissionMapController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\HcMapController;
use App\Http\Controllers\HdKdaController;
use App\Http\Controllers\AssetsController;
use App\Http\Controllers\HdFriendlistController;
use App\Http\Controllers\HrPeriodBattlepassController;
use App\Http\Controllers\HcQuestBattlepassController;
use App\Http\Controllers\HcBattlepassRewardsController;
use App\Http\Controllers\HdBattlepassController;
use App\Http\Controllers\HdBattlepassQuestsController;
use App\Http\Controllers\HdBattlepassRewardsController;
use App\Http\Controllers\HrPlayerBattlepassController;
use App\Http\Controllers\HdSubscriptionRewardsController;
use App\Http\Controllers\HrExpBattlepassController;
use App\Http\Controllers\HrBattlepassPurchaseController;
use App\Http\Controllers\HrProgressBattlepassController;
use App\Http\Controllers\HrPeriodSubscriptionController;
use App\Http\Controllers\HcSubscriptionRewardsController;
use App\Http\Controllers\HdSubscriptionController;
use App\Http\Controllers\HrPlayerSubscriptionController;
use App\Http\Controllers\HrExpSubscriptionController;
use App\Http\Controllers\HrSubscriptionPurchaseController;
use App\Http\Controllers\HrProgressSubscriptionController;
use App\Http\Controllers\HrStatCharacterPlayerController;
use App\Http\Controllers\HdGameRecordsController;
use App\Http\Controllers\HcCountriesController;
use App\Http\Controllers\HcStatesController;

use Illuminate\Support\Facades\Route;

use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */


 Route::post('/broadcasting/auth', function (Illuminate\Http\Request $request) {
    // Ensure the user is authenticated
    $user = $request->user();  // This will retrieve the authenticated user

    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    return Broadcast::auth($request);
})->middleware('checktokenplayer'); // Apply the auth:api middleware here

Route::post('/password/email', ForgotPasswordController::class);

Route::post('/password/code/check', CodeCheckController::class);

Route::post('/password/reset', ResetPasswordController::class);

Route::post('/reset-first-password', [ResetPasswordController::class, 'resetFirstPassword'])->name('reset-first-password');
// DEBUG
Route::get('/clear-cache', function () {
    $exitCode = Artisan::call('cache:clear');
    return '<h1>Cache cleared</h1>';
})->name('clear-cache');

Route::get('/route-clear', function () {
    $exitCode = Artisan::call('route:clear');
    return '<h1>Route cache cleared</h1>';
})->name('route-clear');

Route::get('/config-cache', function () {
    $exitCode = Artisan::call('config:cache');
    return '<h1>Configuration cached</h1>';
})->name('config-cache');

Route::get('/optimize', function () {
    $exitCode = Artisan::call('optimize:clear');
    return '<h1>Configuration cached</h1>';
})->name('optimize');

Route::get('/storage-link', function () {
    $exitCode = Artisan::call('storage:link');
    return '<h1>storage linked</h1>';
})->name('storage-link');
Route::get('/unauthorized', function () {
    abort(401, 'Unauthorized');
})->name('Unauthorized');

// AUTHENTICATION
Route::post('/playersLogin', [PlayerController::class, 'login'])->middleware('player');
Route::post('/adminLogin', [PlayerController::class, 'adminLogin'])->middleware('user');
Route::post('/adminRegister', [PlayerController::class, 'AdminRegister'])->middleware('user');

Route::get('/getprofile', [PlayerController::class, 'getprofile'])->name('getprofile');
Route::get('/getprofileadmin', [PlayerController::class, 'getprofileadmin'])->name('getprofileadmin');
Route::get('/logout', [PlayerController::class, 'logout']);
Route::get('/offline', [PlayerController::class, 'offline']);
Route::get('/logoutAdmin', [PlayerController::class, 'logoutAdmin']);



// Route::post('/rewardwallet', [WalletController::class, 'rewardwallet']);
Route::get('players', [PlayerController::class, 'index']);
Route::post('players', [PlayerController::class, 'store']);
Route::get('players/{player}', [PlayerController::class, 'show']);

Route::middleware('otentikasi')->group(function () {
    Route::get('maps', [HcMapController::class, 'index']);
    Route::get('assets', [AssetsController::class, 'index']);
    Route::get('missions', [HdMissionMapController::class, 'index']);

    Route::controller(HdBattlepassController::class)->group(function () {
        // Route::post('/battlepass', 'store');
        // Route::post('/battlepass-update/{id}', 'update');
        Route::get('/battlepass', 'index');
        // Route::delete('/battlepass', 'destroy');
        Route::get('/battlepass/{id}', 'show');
    });

    Route::controller(HcCountriesController::class)->group(function () {
        Route::get('/countries', 'index');
        Route::get('/country/{id}', 'show');
    });
    Route::controller(HcStatesController::class)->group(function () {
        Route::get('/states', 'index');
        Route::get('/states-country/{id}', 'showBycountry');
        Route::get('/state/{id}', 'show');
    });

    Route::middleware('checktokenuser')->group(function () {
        Route::put('players/{player}', [PlayerController::class, 'update']);
        Route::delete('players/{player}', [PlayerController::class, 'destroy']);
        // LevelController
        Route::apiResource('levels', LevelController::class);
        Route::resource('characters', HcCharacterController::class);
        Route::resource('character-roles', HcCharacterRoleController::class);
        Route::resource('character-players', HcCharacterPlayersController::class);
        Route::resource('inventories', HcInventoryController::class);
        Route::resource('part-skins', HcPartSkinController::class);
        Route::resource('sub-part-skins', HcSubPartSkinController::class);
        Route::resource('inventory-players', HrInventoryPlayersController::class);
        Route::resource('skin-character-players', HrSkinCharacterPlayersController::class);
        Route::resource('currencies', HcCurrencyController::class);
        Route::resource('currencies-shop', HrCurrenciesShopController::class);
        Route::resource('weapon-types', HcTypeWeaponController::class);
        Route::resource('mission-reward', HdMissionRewardController::class);
        // Route::resource('missions', HdMissionMapController::class);
        // Route::resource('maps', HcMapController::class);

        Route::controller(HrSkinCharacterController::class)->group(function () {
            Route::post('/skin-characters', 'store');
            Route::post('/skin-characters-update/{id}', 'update');
            Route::get('/skin-characters', 'index');
            Route::delete('/skin-characters', 'destroy');
            Route::get('/skin-characters/{id}', 'show');
        });
        Route::controller(HcWeaponController::class)->group(function () {
            Route::post('/weapons', 'store');
            Route::post('/weapons-update/{id}', 'update');
            Route::get('/weapons', 'index');
            Route::delete('/weapons', 'destroy');
            Route::get('/weapons/{id}', 'show');
        });

        Route::controller(HcMapController::class)->group(function () {
            Route::post('/maps', 'store');
            Route::put('/maps/{id}', 'update');
            Route::delete('/maps', 'destroy');
            Route::get('/maps/{id}', 'show');
        });
        Route::controller(HdMissionMapController::class)->group(function () {
            Route::post('/missions', 'store');
            Route::put('/missions/{id}', 'update');
            Route::delete('/missions', 'destroy');
            Route::get('/missions/{id}', 'show');
        });

        Route::controller(HdKdaController::class)->group(function () {
            Route::post('/kda', 'index');
            Route::post('/kda', 'store');
        });

        Route::controller(AssetsController::class)->group(function () {
            Route::post('/assets', 'store');
            Route::put('/assets/{id}', 'update');
            Route::delete('/assets', 'destroy');
            Route::get('/assets/{id}', 'show');
        });

        Route::controller(HrPeriodBattlepassController::class)->group(function () {
            Route::post('/period-battlepass', 'store');
            Route::post('/period-battlepass-update/{id}', 'update');
            Route::get('/period-battlepass', 'index');
            Route::delete('/period-battlepass', 'destroy');
            Route::get('/period-battlepass/{id}', 'show');
        });

        Route::controller(HcQuestBattlepassController::class)->group(function () {
            Route::post('/quest-battlepass', 'store');
            Route::post('/quest-battlepass-update/{id}', 'update');
            Route::get('/quest-battlepass', 'index');
            Route::delete('/quest-battlepass', 'destroy');
            Route::get('/quest-battlepass/{id}', 'show');
        });

        Route::controller(HcBattlepassRewardsController::class)->group(function () {
            Route::post('/battlepass-rewards', 'store');
            Route::post('/battlepass-rewards-update/{id}', 'update');
            Route::get('/battlepass-rewards', 'index');
            Route::delete('/battlepass-rewards', 'destroy');
            Route::get('/battlepass-rewards/{id}', 'show');
        });

        Route::controller(HdBattlepassController::class)->group(function () {
            Route::post('/battlepass', 'store');
            Route::post('/battlepass-update/{id}', 'update');
            // Route::get('/battlepass', 'index');
            Route::delete('/battlepass', 'destroy');
            // Route::get('/battlepass/{id}', 'show');
        });

        Route::controller(HdBattlepassQuestsController::class)->group(function () {
            Route::post('/battlepass-quests', 'store');
            Route::post('/battlepass-quests-update/{id}', 'update');
            Route::get('/battlepass-quests', 'index');
            Route::delete('/battlepass-quests', 'destroy');
            Route::get('/battlepass-quests/{id}', 'show');
        });

        Route::controller(HdBattlepassRewardsController::class)->group(function () {
            Route::post('/rewards-battlepass', 'store');
            Route::post('/rewards-battlepass-update/{id}', 'update');
            Route::get('/rewards-battlepass', 'index');
            Route::delete('/rewards-battlepass', 'destroy');
            Route::get('/rewards-battlepass/{id}', 'show');
        });

        Route::controller(HrPlayerBattlepassController::class)->group(function () {
            Route::post('/player-battlepass', 'store');
            Route::post('/player-battlepass-update/{id}', 'update');
            Route::get('/player-battlepass', 'index');
            Route::delete('/player-battlepass', 'destroy');
            Route::get('/player-battlepass/{id}', 'show');
        });

        Route::controller(HrExpBattlepassController::class)->group(function () {
            // Route::post('/exp-battlepass', 'store');
            Route::post('/exp-battlepass-update/{id}', 'update');
            Route::get('/exp-battlepass', 'index');
            Route::delete('/exp-battlepass', 'destroy');
            Route::get('/exp-battlepass/{id}', 'show');
        });

        Route::controller(HrBattlepassPurchaseController::class)->group(function () {
            Route::post('/battlepass-purchases', 'store');
            // Route::post('/battlepass-purchases-update/{id}', 'update');
            Route::get('/battlepass-purchases', 'index');
            Route::delete('/battlepass-purchases', 'destroy');
            Route::get('/battlepass-purchases/{id}', 'show');
        });

        Route::controller(HrProgressBattlepassController::class)->group(function () {
            Route::post('/progress-battlepass-update/{id}', 'update');
            Route::get('/progress-battlepass', 'index');
            Route::delete('/progress-battlepass', 'destroy');
            Route::get('/progress-battlepass/{id}', 'show');
        });

        Route::controller(HrPeriodSubscriptionController::class)->group(function () {
            Route::post('/period-subscriptions', 'store');
            Route::post('/period-subscriptions-update/{id}', 'update');
            Route::get('/period-subscriptions', 'index');
            Route::delete('/period-subscriptions', 'destroy');
            Route::get('/period-subscriptions/{id}', 'show');
        });

        Route::controller(HcSubscriptionRewardsController::class)->group(function () {
            Route::post('/subscription-reward', 'store');
            Route::post('/subscription-reward-update/{id}', 'update');
            Route::get('/subscription-reward', 'index');
            Route::delete('/subscription-reward', 'destroy');
            Route::get('/subscription-reward/{id}', 'show');
        });

        Route::controller(HdSubscriptionRewardsController::class)->group(function () {
            Route::post('/rewards-subscription', 'store');
            Route::post('/rewards-subscription-update/{id}', 'update');
            Route::get('/rewards-subscription', 'index');
            Route::delete('/rewards-subscription', 'destroy');
            Route::get('/rewards-subscription/{id}', 'show');
        });


        Route::controller(HdSubscriptionController::class)->group(function () {
            Route::post('/subscriptions', 'store');
            Route::post('/subscriptions-update/{id}', 'update');
            Route::get('/subscriptions', 'index');
            Route::delete('/subscriptions', 'destroy');
            Route::get('/subscriptions/{id}', 'show');
        });

        Route::controller(HrPlayerSubscriptionController::class)->group(function () {
            Route::post('/player-subscriptions', 'store');
            Route::post('/player-subscriptions-update/{id}', 'update');
            Route::get('/player-subscriptions', 'index');
            Route::delete('/player-subscriptions', 'destroy');
            Route::get('/player-subscriptions/{id}', 'show');
        });

        Route::controller(HrExpSubscriptionController::class)->group(function () {
            Route::post('/exp-subscriptions', 'store');
            Route::post('/exp-subscriptions-update/{id}', 'update');
            Route::get('/exp-subscriptions', 'index');
            Route::delete('/exp-subscriptions', 'destroy');
            Route::get('/exp-subscriptions/{id}', 'show');
        });

        Route::controller(HrSubscriptionPurchaseController::class)->group(function () {
            Route::post('/subscription-purchases-update/{id}', 'update');
            Route::get('/subscription-purchases', 'index');
            Route::delete('/subscription-purchases', 'destroy');
            Route::get('/subscription-purchases/{id}', 'show');
        });

        Route::controller(HrProgressSubscriptionController::class)->group(function () {
            Route::post('/progress-subscriptions', 'store');
            Route::post('/progress-subscriptions-update/{id}', 'update');
            Route::get('/progress-subscriptions', 'index');
            Route::delete('/progress-subscriptions', 'destroy');
            Route::get('/progress-subscriptions/{id}', 'show');
        });

        Route::controller(HrStatCharacterPlayerController::class)->group(function () {
            Route::post('/stat-character-player/{id}', 'update');
            Route::get('/stat-character-player', 'index');
            Route::delete('/stat-character-player', 'destroy');
            Route::get('/stat-character-player/{id}', 'show');
        });

        Route::controller(HcCountriesController::class)->group(function () {
            Route::post('/country', 'store');
            Route::put('/country/{id}', 'update');
            Route::delete('/country', 'destroy');
        });
        Route::controller(HcStatesController::class)->group(function () {
            Route::post('/state', 'store');
            Route::put('/state/{id}', 'update');
            Route::delete('/state', 'destroy');
        });

    });

    Route::middleware('checktokenplayer')->group(function () {
        Route::put('/update-profile', [PlayerController::class, 'updateprofile']);
        Route::get('levelsPlayer', [LevelController::class, 'showbyplayer']);
        Route::post('reward', [RewardController::class, 'reward']);
        Route::get('inventory-weapon', [HrInventoryPlayersController::class, 'inventoryWeapon']);
        Route::get('shop-weapon', [HrInventoryPlayersController::class, 'shopWeapon']);
        Route::post('purchase-weapon', [HrInventoryPlayersController::class, 'purchaseWeapon']);
        Route::post('use-weapon', [HrInventoryPlayersController::class, 'useWeapon']);

        Route::get('inventory-character', [HdCharacterPlayersController::class, 'inventoryCharacter']);
        Route::get('shop-character', [HdCharacterPlayersController::class, 'shopCharacter']);
        Route::post('purchase-character', [HdCharacterPlayersController::class, 'purchaseCharacter']);
        Route::post('use-character', [HdCharacterPlayersController::class, 'useCharacter']);

        Route::get('inventory-skin', [HdSkinCharacterPlayersController::class, 'inventorySkin']);
        Route::get('shop-skin', [HdSkinCharacterPlayersController::class, 'shopSkin']);
        Route::post('purchase-skin', [HdSkinCharacterPlayersController::class, 'purchaseSkin']);
        Route::post('use-skin', [HdSkinCharacterPlayersController::class, 'useSkin']);

        Route::post('add-mission', [HdMissionRewardController::class, 'addMission']);
        Route::post('claim-reward', [HdMissionRewardController::class, 'claimReward']);
        Route::post('list-mission', [HdMissionRewardController::class, 'listMissionPlayer']);

        Route::controller(HdFriendlistController::class)->group(function () {
            Route::post('/add-friend', 'addFriend');
            Route::post('/share-friend', 'share');

            Route::post('/acc-friend/{id}', 'accFriend');
            Route::delete('/delete-friend', 'deleteFriend');
            Route::get('/friendlist', 'index');
            Route::get('/friendlist-invite', 'inviteAll');
            Route::get('/friendlist-invites', 'invites');
            Route::get('/friendlist-invited', 'invited');
            Route::get('/friendlist-blocked', 'blocked');

        });


        Route::controller(HrBattlepassPurchaseController::class)->group(function () {
            Route::post('/battlepass-purchases', 'store');
            Route::get('/battlepass-purchases-player', 'showPlayer');
        });

        Route::controller(HrProgressBattlepassController::class)->group(function () {
            Route::post('/progress-battlepass', 'store');
            Route::get('/progress-battlepass-player', 'showPlayer');
        });
        Route::controller(HdBattlepassController::class)->group(function () {
            Route::get('/battlepass-player', 'showPlayer');
            Route::post('/claim-reward-battlepass', 'claim');

        });

        Route::controller(HrSubscriptionPurchaseController::class)->group(function () {
            Route::post('/subscription-purchases', 'store');
            Route::get('/subscription-purchases-player', 'showPlayer');
        });

        Route::controller(HrExpSubscriptionController::class)->group(function () {
            Route::post('/exp-subscription', 'store');
            Route::get('/exp-subscription-player', 'showPlayer');
        });
        Route::controller(HdSubscriptionController::class)->group(function () {
            Route::get('/subscription-player', 'showPlayer');
            Route::post('/claim-reward-subscription', 'claim');

        });

        Route::post('/topup', [PlayerController::class, 'topup']);

        Route::post('/upgrade-stat-character', [HrStatCharacterPlayerController::class, 'store']);

        Route::get('/load', [PlayerController::class, 'load']);

        Route::controller(HdFriendListController::class)->group(function () {
            Route::post('/friendlist-invite', 'invite');
            Route::post('/friendlist-accept/{id}', 'accept');
            Route::post('/friendlist-ignore/{id}', 'ignore');
            Route::post('/friendlist-block/{id}', 'block');
            Route::post('/friendlist-unblock/{id}', 'unblock');
            Route::post('/friendlist-unblockAll', 'unblockAll');
            Route::post('/friendlist-remove/{id}', 'remove');
            Route::post('/friendlist-ignoreAll', 'ignoreAll');
            Route::get('/friendlist-share', 'share');

            Route::get('/friendlist', 'friendlist');
            Route::get('/friendlist-invites', 'invites');
            Route::get('/friendlist-invited', 'invited');
            Route::get('/friendlist-blocked', 'blocked');
            Route::get('/friendlist-search/{code}', 'search');
        });

        Route::controller(HdGameRecordsController::class)->group(function () {
            Route::get('/game-records-player', 'indexPlayer'); // Untuk mengambil semua game records by player
            Route::get('/game-records-player-detail', 'showPlayer'); // Untuk mengambil detail records per player
            Route::post('/game-records-player', 'store'); // Untuk menyimpan game record baru
        });


    });
});

