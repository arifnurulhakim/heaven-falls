<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Foreign key relation for hd_players.id < hd_wallets.player_id
        Schema::table('hd_wallets', function (Blueprint $table) {
            $table->foreign('player_id')->references('id')->on('hd_players')->onDelete('cascade');
        });

        // Foreign key relation for hc_currency.id < hd_wallets.currency_id
        Schema::table('hd_wallets', function (Blueprint $table) {
            $table->foreign('currency_id')->references('id')->on('hc_currency')->onDelete('cascade');
        });

        // Foreign key relation for hc_currency.id < hr_currencies_shop.currency_id
        Schema::table('hr_currencies_shop', function (Blueprint $table) {
            $table->foreign('currency_id')->references('id')->on('hc_currency')->onDelete('cascade');
        });

        // Foreign key relation for hc_character.id < hr_skin_character_players.character_id
        Schema::table('hr_skin_character_players', function (Blueprint $table) {
            $table->foreign('character_id')->references('id')->on('hc_character')->onDelete('cascade');
        });

        // Foreign key relation for hr_level_players.level_r_id < hd_players.level_r_id
        Schema::table('hd_players', function (Blueprint $table) {
            $table->foreign('level_r_id')->references('id')->on('hr_level_players')->onDelete('set null');
        });

        // Foreign key relation for hc_level.id < hr_level_players.level_id
        Schema::table('hr_level_players', function (Blueprint $table) {
            $table->foreign('level_id')->references('id')->on('hc_level')->onDelete('cascade');
        });

        // Foreign key relation for hr_inventory_players.id < hd_players.inventory_r_id
        Schema::table('hd_players', function (Blueprint $table) {
            $table->foreign('inventory_r_id')->references('id')->on('hr_inventory_players')->onDelete('set null');
        });

        // Foreign key relation for hr_skin_character_players.players_id < hd_players.id
        Schema::table('hr_skin_character_players', function (Blueprint $table) {
            $table->foreign('players_id')->references('id')->on('hd_players')->onDelete('cascade');
        });

        // Foreign key relation for hr_skin_character_players.skin_id < hr_skin_character.id
        Schema::table('hr_skin_character_players', function (Blueprint $table) {
            $table->foreign('skin_id')->references('id')->on('hr_skin_character')->onDelete('cascade');
        });

        // Foreign key relation for hc_character.character_role_id < hc_character_role.id
        Schema::table('hc_character', function (Blueprint $table) {
            $table->foreign('character_role_id')->references('id')->on('hc_character_role')->onDelete('cascade');
        });

        // Foreign key relation for hc_weapons.id < hr_inventory_players.weapon_primary_r_id
        Schema::table('hr_inventory_players', function (Blueprint $table) {
            $table->foreign('weapon_primary_r_id')->references('id')->on('hc_weapons')->onDelete('set null');
        });

        // Foreign key relation for hc_weapons.id < hr_inventory_players.weapon_secondary_r_id
        Schema::table('hr_inventory_players', function (Blueprint $table) {
            $table->foreign('weapon_secondary_r_id')->references('id')->on('hc_weapons')->onDelete('set null');
        });

        // Foreign key relation for hc_weapons.id < hr_inventory_players.weapon_melee_r_id
        Schema::table('hr_inventory_players', function (Blueprint $table) {
            $table->foreign('weapon_melee_r_id')->references('id')->on('hc_weapons')->onDelete('set null');
        });

        // Foreign key relation for hc_weapons.id < hr_inventory_players.weapon_explosive_r_id
        Schema::table('hr_inventory_players', function (Blueprint $table) {
            $table->foreign('weapon_explosive_r_id')->references('id')->on('hc_weapons')->onDelete('set null');
        });

        // Foreign key relation for hc_type_weapons.id < hc_weapons.weapon_r_type
        Schema::table('hc_weapons', function (Blueprint $table) {
            $table->foreign('weapon_r_type')->references('id')->on('hc_type_weapons')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop foreign key relations in reverse order
        Schema::table('hc_weapons', function (Blueprint $table) {
            $table->dropForeign(['weapon_r_type']);
        });

        Schema::table('hr_inventory_players', function (Blueprint $table) {
            $table->dropForeign(['weapon_explosive_r_id']);
            $table->dropForeign(['weapon_melee_r_id']);
            $table->dropForeign(['weapon_secondary_r_id']);
            $table->dropForeign(['weapon_primary_r_id']);
        });

        Schema::table('hc_character', function (Blueprint $table) {
            $table->dropForeign(['character_role_id']);
        });

        Schema::table('hr_skin_character_players', function (Blueprint $table) {
            $table->dropForeign(['skin_id']);
            $table->dropForeign(['players_id']);
        });

        Schema::table('hd_players', function (Blueprint $table) {
            $table->dropForeign(['inventory_r_id']);
            $table->dropForeign(['level_r_id']);
        });

        Schema::table('hr_level_players', function (Blueprint $table) {
            $table->dropForeign(['level_id']);
        });

        Schema::table('hr_skin_character_players', function (Blueprint $table) {
            $table->dropForeign(['character_id']);
        });

        Schema::table('hr_currencies_shop', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
        });

        Schema::table('hd_wallets', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropForeign(['player_id']);
        });
    }
};
