<?php

declare(strict_types=1);

namespace CyrildeWit\EloquentViewable\Helpers;

class ModelMorphHelper
{
    public static function getMorphMap(): array
    {
        return Container::getInstance()
            ->make('config')
            ->get('eloquent-viewable.models.morph_map');
    }

    public static function getMorphMapFor(string $model): string | null
    {
        return static::getMorphMap()[$model] ?? null;
    }
}
