<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    //
    /** Role every new account starts with (what used to be split into Seller and Customer). */
    public const INDIVIDUAL = 'Individual';

    /** Business accounts that must be verified before they can buy or sell. */
    public const TRADER = 'Trader';

    protected $fillable = [
        'name',
    ];

    /** Role for a signup account type ("individual" or "trader"); never a role the client names directly. */
    public static function forAccountType(string $accountType): ?self
    {
        if (strtolower($accountType) === 'trader') {
            return static::query()->whereRaw('LOWER(name) = ?', ['trader'])->first();
        }

        return static::defaultForNewAccounts();
    }

    /**
     * Role given to new accounts. Falls back to the old names so environments that have not run the
     * seller/customer merge migration yet keep working.
     */
    public static function defaultForNewAccounts(): ?self
    {
        foreach (['individual', 'user', 'customer', 'seller'] as $name) {
            $role = static::query()->whereRaw('LOWER(name) = ?', [$name])->first();
            if ($role) {
                return $role;
            }
        }

        return null;
    }
}
