<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type'];

    public static $types = [
        'general' => 'General',
        'pant' => 'Pant',
    ];

    /**
     * Sort sizes for API / display: letter sizes (S, M, L, …), then pants by numeric waist when possible.
     */
    public static function compareForDisplay(self $a, self $b): int
    {
        $typeRank = static fn (string $type): int => match ($type) {
            'general' => 0,
            'pant' => 1,
            default => 2,
        };

        $byType = $typeRank($a->type) <=> $typeRank($b->type);
        if ($byType !== 0) {
            return $byType;
        }

        if ($a->type === 'general') {
            $map = static::generalSizeOrderMap();
            $ia = $map[strtolower(trim($a->name))] ?? 1_000;
            $ib = $map[strtolower(trim($b->name))] ?? 1_000;
            if ($ia !== $ib) {
                return $ia <=> $ib;
            }

            return strcasecmp($a->name, $b->name);
        }

        $na = static::leadingInteger($a->name);
        $nb = static::leadingInteger($b->name);
        if ($na !== null && $nb !== null && $na !== $nb) {
            return $na <=> $nb;
        }

        return strcasecmp($a->name, $b->name);
    }

    /**
     * @return array<string, int>
     */
    protected static function generalSizeOrderMap(): array
    {
        static $map = null;

        if ($map !== null) {
            return $map;
        }

        $groups = [
            ['3xs', 'xxxs'],
            ['xxs', '2xs'],
            ['xs', 'extra small'],
            ['s', 'small'],
            ['m', 'medium'],
            ['l', 'large'],
            ['xl', 'extra large'],
            ['xxl', '2xl', '2x'],
            ['xxxl', '3xl', '3x'],
            ['xxxxl', '4xl', '4x'],
            ['5xl', '5x'],
            ['one size', 'os', 'free size'],
        ];

        $map = [];
        foreach ($groups as $order => $aliases) {
            foreach ($aliases as $alias) {
                $map[strtolower($alias)] = $order;
            }
        }

        return $map;
    }

    protected static function leadingInteger(string $name): ?int
    {
        if (preg_match('/^\s*(\d+)/', $name, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
