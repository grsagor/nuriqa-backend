<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Seller and Customer behaved identically, so they become one role: "Individual".
 *
 * The Customer role is kept (same id) and renamed, every Seller account moves onto it, and the empty
 * Seller role is removed. Matching is by name so it also works where the ids differ. Safe to run twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $byName = fn (string $name) => DB::table('roles')->whereRaw('LOWER(name) = ?', [$name])->first();

            $individual = $byName('individual');
            $customer = $byName('customer');
            $seller = $byName('seller');

            $target = $individual ?? $customer ?? $seller;
            if (! $target) {
                return;
            }

            if (strtolower($target->name) !== 'individual') {
                DB::table('roles')->where('id', $target->id)->update(['name' => 'Individual', 'updated_at' => now()]);
            }

            foreach ([$customer, $seller] as $old) {
                if (! $old || (int) $old->id === (int) $target->id) {
                    continue;
                }

                $movedUserIds = DB::table('users')->where('role_id', $old->id)->pluck('id')->all();
                DB::table('users')->where('role_id', $old->id)->update(['role_id' => $target->id]);
                DB::table('roles')->where('id', $old->id)->delete();

                // Which accounts used to be sellers cannot be recovered afterwards, so keep a record.
                Log::info("Merged role \"{$old->name}\" into Individual", ['moved_user_ids' => $movedUserIds]);
            }
        });
    }

    /**
     * Restores the two role names. Accounts that were Sellers cannot be told apart any more, so they
     * all stay on Customer; use the logged `moved_user_ids` if they need to be moved back.
     */
    public function down(): void
    {
        DB::table('roles')->whereRaw('LOWER(name) = ?', ['individual'])->update(['name' => 'Customer', 'updated_at' => now()]);

        if (! DB::table('roles')->whereRaw('LOWER(name) = ?', ['seller'])->exists()) {
            DB::table('roles')->insert(['name' => 'Seller', 'created_at' => now(), 'updated_at' => now()]);
        }
    }
};
