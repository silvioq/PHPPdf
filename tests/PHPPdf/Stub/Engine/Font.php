<?php

declare(strict_types=1);

namespace PHPPdf\Stub\Engine;

use PHPPdf\Core\Engine\AbstractFont;

class Font extends AbstractFont
{
    public function getWidthOfText($text, $fontSize): int
    {
        return 0;
    }
    
    public function getCurrentResourceIdentifier(): string
    {
        return 'abc';
    }
}
