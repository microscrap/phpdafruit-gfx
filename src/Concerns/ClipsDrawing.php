<?php

namespace Microscrap\GFX\PhpdaFruit\Concerns;

use Microscrap\GFX\PhpdaFruit\Geometry\Rect;

/**
 * Nested clip stack for logical draw space. Intersected before rotation so
 * rejected pixels never reach Managed setPixel/setSegment (and never dirty).
 */
trait ClipsDrawing
{
    /**
     * @var array<int, Rect>
     */
    protected array $clip_stack = [];

    public function pushClip(int $x, int $y, int $width, int $height): static
    {
        $rect = new Rect($x, $y, $width, $height);
        $current = $this->clip();

        if (! is_null($current)) {
            $rect = $current->intersect($rect);
        }

        $this->clip_stack[] = $rect;

        return $this;
    }

    public function popClip(): static
    {
        if ($this->clip_stack !== []) {
            array_pop($this->clip_stack);
        }

        return $this;
    }

    public function clearClips(): static
    {
        $this->clip_stack = [];

        return $this;
    }

    public function clip(): ?Rect
    {
        if ($this->clip_stack === []) {
            return null;
        }

        return $this->clip_stack[array_key_last($this->clip_stack)];
    }

    public function withClip(int $x, int $y, int $width, int $height, callable $callback): static
    {
        $this->pushClip($x, $y, $width, $height);

        try {
            $callback($this);
        } finally {
            $this->popClip();
        }

        return $this;
    }

    protected function clipAllows(int $x, int $y): bool
    {
        $clip = $this->clip();

        if (is_null($clip)) {
            return true;
        }

        return $clip->contains($x, $y);
    }

    protected function clipSegment(int $x, int $y, int $width, int $height): ?Rect
    {
        $segment = new Rect($x, $y, $width, $height);

        if ($segment->isEmpty()) {
            return null;
        }

        $clip = $this->clip();

        if (is_null($clip)) {
            return $segment;
        }

        $intersected = $clip->intersect($segment);

        return $intersected->isEmpty() ? null : $intersected;
    }
}
