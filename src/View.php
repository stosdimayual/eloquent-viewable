<?php

declare(strict_types=1);

namespace CyrildeWit\EloquentViewable;

use CyrildeWit\EloquentViewable\Contracts\View as ViewContract;
use CyrildeWit\EloquentViewable\Support\Period;
use Eloquent;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @mixin Eloquent
 *
 * Note: user_id column corresponds to the id of the person, not user.
 */
class View extends Model implements ViewContract
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the table associated with the model.
     *
     * @return string
     * @throws BindingResolutionException
     */
    public function getTable(): string
    {
        return Container::getInstance()
            ->make('config')
            ->get('eloquent-viewable.models.view.table_name', parent::getTable());
    }

    /**
     * Get the current connection name for the model.
     *
     * @return string
     * @throws BindingResolutionException
     */
    public function getConnectionName(): string
    {
        return Container::getInstance()
            ->make('config')
            ->get('eloquent-viewable.models.view.connection', parent::getConnectionName());
    }

    /**
     * Get the viewable model to which this View belongs.
     *
     * @return MorphTo
     */
    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include views within the period.
     *
     * @param Builder $query
     * @param Period $period
     * @return Builder
     */
    public function scopeWithinPeriod(Builder $query, Period $period): Builder
    {
        $startDateTime = $period->getStartDateTime();
        $endDateTime = $period->getEndDateTime();

        if ($startDateTime !== null && $endDateTime === null) {
            $query->where('viewed_at', '>=', $startDateTime);
        } elseif ($startDateTime === null && $endDateTime !== null) {
            $query->where('viewed_at', '<=', $endDateTime);
        } elseif ($startDateTime !== null && $endDateTime !== null) {
            $query->whereBetween('viewed_at', [$startDateTime, $endDateTime]);
        }

        return $query;
    }

    /**
     * Scope a query to only include views withing the collection.
     *
     * @param Builder $query
     * @param string|null $collection
     * @return Builder
     */
    public function scopeCollection(Builder $query, string $collection = null): Builder
    {
        return $query->where('collection', $collection);
    }
}
