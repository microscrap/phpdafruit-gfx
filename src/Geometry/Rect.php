<?php

namespace Microscrap\GFX\PhpdaFruit\Geometry;

/**
 * Axis-aligned rectangle in logical draw space (x/y origin, width/height extent).
 */
readonly class Rect
{
    public function __construct(
        public int $x = 0,
        public int $y = 0,
        public int $width = 0,
        public int $height = 0,
    ) {}

    public static function empty(): self
    {
        return new self(0, 0, 0, 0);
    }

    public function isEmpty(): bool
    {
        return $this->width <= 0 || $this->height <= 0;
    }

    public function right(): int
    {
        return $this->x + $this->width;
    }

    public function bottom(): int
    {
        return $this->y + $this->height;
    }

    public function contains(int $px, int $py): bool
    {
        return $px >= $this->x
            && $py >= $this->y
            && $px < $this->right()
            && $py < $this->bottom();
    }

    public function intersect(self $other): self
    {
        $x1 = max($this->x, $other->x);
        $y1 = max($this->y, $other->y);
        $x2 = min($this->right(), $other->right());
        $y2 = min($this->bottom(), $other->bottom());

        if ($x2 <= $x1 || $y2 <= $y1) {
            return self::empty();
        }

        return new self($x1, $y1, $x2 - $x1, $y2 - $y1);
    }
}
