<?php

declare(strict_types=1);

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */


namespace PHPPdf\Core;

/**
 * Color palette
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ColorPalette
{
    private array $colors;

    public function __construct(array $colors = [])
    {
        $this->colors = $colors;
    }

    public function get(?string $name = '')
    {
        if ($name !== null) {
            $name = strtolower($name);

            return $this->colors[$name] ?? $name;
        }

        return null;
    }

    public function merge(array $colors): void
    {
        $this->colors = $colors + $this->colors;
    }

    public function getAll(): array
    {
        return $this->colors;
    }
}
