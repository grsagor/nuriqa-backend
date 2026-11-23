<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    //
    /** Role every new account starts with (what used to be split into Seller and Customer). */
    public const INDIVIDUAL = 'Individual';

    protected $fillable = [
        'name',
    ];

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
