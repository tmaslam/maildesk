<?php

namespace App\Support;

class Palette
{
    public static function color(int $id): string
    {
        $colors = ['#0b57d0', '#188038', '#d93025', '#8430ce', '#e37400', '#007b83'];
        return $colors[$id % count($colors)];
    }
}
