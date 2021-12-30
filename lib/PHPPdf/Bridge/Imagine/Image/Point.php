<?php

declare(strict_types=1);

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Bridge\Imagine\Image;

use Imagine\Image\BoxInterface;
use Imagine\Image\PointInterface;

/**
 * Point, coordinates might to have negative values
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Point implements PointInterface
{
    private int|float $x;
    private int|float $y;

    public function __construct(int|float $x, int|float $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function getX(): int|float
    {
        return $this->x;
    }

    public function getY(): int|float
    {
        return $this->y;
    }

    public function in(BoxInterface $box): bool
    {
        return $this->x < $box->getWidth() && $this->y < $box->getHeight();
    }

    public function move($amount): PointInterface
    {
        return new self($this->x + $amount, $this->y + $amount);
    }

    public function __toString(): string
    {
        return sprintf('(%d, %d)', $this->x, $this->y);
    }
}
