<?php

declare(strict_types=1);

namespace CyrildeWit\EloquentViewable;

use CyrildeWit\EloquentViewable\Contracts\CrawlerDetector as CrawlerDetectorContract;
use CyrildeWit\EloquentViewable\Contracts\View as ViewContract;
use CyrildeWit\EloquentViewable\Contracts\Views as ViewsContract;
use CyrildeWit\EloquentViewable\Contracts\Visitor as VisitorContract;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\ServiceProvider;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

class EloquentViewableServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $config = $this->app->config['eloquent-viewable'];

            $this->publishes([
                __DIR__ . '/../config/eloquent-viewable.php' => $this->app->configPath('eloquent-viewable.php'),
            ], 'config');

            if (!class_exists('CreateViewsTable')) {
                $timestamp = date('Y_m_d_His', time());

                $this->publishes([
                    __DIR__ . '/../migrations/create_views_table.php.stub' => database_path("/migrations/{$timestamp}_create_views_table.php"),
                ], 'migrations');
            }
        }

        $this->bootMacros();
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/eloquent-viewable.php',
            'eloquent-viewable'
        );

        $this->app->when(Views::class)
            ->needs(CacheRepository::class)
            ->give(function (): CacheRepository {
                return $this->app['cache']->store(
                    $this->app['config']['eloquent-viewable']['cache']['store']
                );
            });

        $this->app->bind(ViewsContract::class, Views::class);

        $this->app->bind(ViewContract::class, View::class);

        $this->app->bind(VisitorContract::class, Visitor::class);

        $this->app->bind(CrawlerDetectAdapter::class, function ($app) {
            $detector = new CrawlerDetect(
                $app['request']->headers->all(),
                $app['request']->server('HTTP_USER_AGENT')
            );

            return new CrawlerDetectAdapter($detector);
        });

        $this->app->singleton(CrawlerDetectorContract::class, CrawlerDetectAdapter::class);
    }

    protected function bootMacros(): void
    {
        Builder::macro('whereBetweenFlexible', function ($column, ?array $values) {
            /** @var Builder $this */
            if (is_null($values[0]) && is_null($values[1])) {
                return $this;
            }
            if (is_null($values[0])) {
                return $this->where($column, '<=', $values[1]);
            }
            if (is_null($values[1])) {
                return $this->where($column, '>=', $values[0]);
            }

            return $this->whereBetween($column, $values);
        });
    }
}
