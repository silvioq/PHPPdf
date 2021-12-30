<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Formatter;

use PHPPdf\Core\Formatter\Formatter,
    PHPPdf\Core\Node\Node,
    PHPPdf\Core\Document,
    PHPPdf\Core\Formatter\Chain;

/**
 * Base formatter class
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class BaseFormatter implements Formatter
{
    public function __serialize(): array
    {
        return [];
    }

    public function __unserialize($serialized): void
    {

    }

}
