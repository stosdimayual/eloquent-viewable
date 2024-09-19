<?php

declare(strict_types=1);

namespace CyrildeWit\EloquentViewable;

use App\Providers\EloqentServiceProvider;
use CyrildeWit\EloquentViewable\Contracts\View as ViewContract;
use CyrildeWit\EloquentViewable\Helpers\ModelMorphHelper;
use CyrildeWit\EloquentViewable\Support\Period;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * @method static self|Builder orderByViews(string $direction = 'desc', $period = null, string $collection = null, bool $unique = false, $as = 'views_count')
 * @method static self|Builder recentlyViewedBy(?int $id = null, string $direction = 'desc', ?Period $period = null, ?string $collection = null)
 * @method static self|Builder viewedBy(?int $id = null, string $direction = 'desc', ?Period $period = null)
 * @method static self|Builder orderByUniqueViews(string $direction = 'desc', $period = null, string $collection = null, string $as = 'unique_views_count')
 **/
trait InteractsWithViews
{
    /**
     * Viewable boot logic.
     *
     * @return void
     */
    public static function bootInteractsWithViews(): void
    {
        static::observe(ViewableObserver::class);
    }

    /**
     * Get the views the model has.
     * @throws BindingResolutionException
     */
    public function views(): MorphMany
    {
        return $this->morphMany(
            Container::getInstance()->make(ViewContract::class),
            'viewable'
        );
    }

    /**
     * Scope a query to order records by views count.
     */
    public function scopeOrderByViews(
        Builder $query,
        string  $direction = 'desc',
        ?Period $period = null,
        ?string $collection = null,
        bool    $unique = false,
        string  $as = 'views_count'
    ): Builder
    {
        return $query->withViewsCount($period, $collection, $unique, $as)
            ->orderBy($as, $direction);
    }

    /**
     * Scope a query to order records by unique views count.
     */
    public function scopeOrderByUniqueViews(
        Builder $query,
        string  $direction = 'desc',
                $period = null,
        string  $collection = null,
        string  $as = 'unique_views_count'
    ): Builder
    {
        return $query->orderByViews($direction, $period, $collection, true, $as);
    }

    /**
     * Scope a query to get the views count without loading them.
     */
    public function scopeWithViewsCount(Builder $query, ?Period $period = null, ?string $collection = null, bool $unique = false, string $as = 'views_count'): Builder
    {
        return $query->withCount(["views as ${as}" => function (Builder $query) use ($period, $collection, $unique) {
            if ($period) {
                $query->withinPeriod($period);
            }

            if ($collection) {
                $query->collection($collection);
            }

            if ($unique) {
                $query->select(DB::raw('count(DISTINCT visitor)'));
            }
        }]);
    }

    /**
     * Scope a query to order records by views count for specific user.
     */
    public function scopeRecentlyViewedBy(
        Builder $query,
        ?int    $id = null,
        string  $direction = 'desc',
        ?Period $period = null,
        ?string $collection = null
    ): Builder
    {
        // TODO rework with cache implementation
        return $query->whereHas('views', function (Builder $query) use ($id, $period, $collection) {
            if ($id) {
                $query->where('user_id', $id);
            }

            if ($period) {
                $query->withinPeriod($period);
            }

            if ($collection) {
                $query->collection($collection);
            }
        });
    }

    public function scopeViewedBy(
        Builder $query,
        ?int $id = null,
        string $direction = 'desc',
        ?Period $period = null
    ): Builder {
        $games = static::getTableName();
        $views = (new View())->getTable();
        $gameMorph = ModelMorphHelper::getMorphMapFor(static::class) ?? static::class;

        return $query->join($views, function (JoinClause $join) use ($id, $views, $games, $gameMorph) {
            if ($id) {
                $join->where("{$views}.user_id", $id);
            }

            $join->on("{$views}.viewable_id", "{$games}.id")
                ->where("{$views}.viewable_type", $gameMorph);
        })->select("{$games}.*")
            ->selectRaw("max({$views}.viewed_at) as viewed_at")
            ->groupBy("{$games}.id")
            ->whereBetweenFlexible('viewed_at', [$period->getStartDateTime(), $period->getEndDateTime()])
            ->reorder('viewed_at', $direction);
    }

    protected static function getTableName(): string
    {
        return (new static)->table;
    }
}

