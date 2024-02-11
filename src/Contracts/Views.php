<?php

declare(strict_types=1);

namespace CyrildeWit\EloquentViewable\Contracts;

use CyrildeWit\EloquentViewable\Support\Period;

interface Views
{
    /**
     * Set the viewable model.
     *
     * @param \CyrildeWit\EloquentViewable\Contracts\Viewable
     * @return $this
     */
    public function forViewable(Viewable $viewable): self;

    /**
     * Get the views count.
     */
    public function count(): int;

    /**
     * Get sum of views value
     *
     * @return float
     */
    public function valueSum(): float;

    /**
     * Record a view.
     */
    public function record(float $value = null, int $id = null): bool;

    /**
     * Destroy all views of the viewable model.
     */
    public function destroy(): void;

    /**
     * Set the cooldown.
     *
     * @param \DateTimeInterface|int|null $cooldown
     */
    public function cooldown($cooldown): self;

    /**
     * Set the period.
     */
    public function period(?Period $period): self;

    /**
     * Set the collection.
     */
    public function collection(?string $name): self;

    /**
     * Fetch only unique views.
     */
    public function unique(bool $state = true): self;

    /**
     * Cache the current views count.
     *
     * @param \DateTimeInterface|int|null $lifetime
     */
    public function remember($lifetime = null): self;
}
