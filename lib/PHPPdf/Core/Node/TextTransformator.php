<?php

declare(strict_types=1);

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class TextTransformator
{
    private array $replacements = [];

    public function __construct(array $replacements = [])
    {
        $this->setReplacements($replacements);
    }

    public function setReplacements(array $replacements): void
    {
        $this->replacements = $replacements;
    }

    public function transform($text): string
    {
        return strtr($text, $this->replacements);
    }
}
