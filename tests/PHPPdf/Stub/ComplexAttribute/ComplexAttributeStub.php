<?php

declare(strict_types=1);

namespace PHPPdf\Stub\ComplexAttribute;

use PHPPdf\Core\Document;
use PHPPdf\Core\ComplexAttribute\ComplexAttribute,
    PHPPdf\Core\Node\Page,
    PHPPdf\Core\Node\Node;

class ComplexAttributeStub extends ComplexAttribute
{
    private string $color;
    private ?string $someParameter;
    
    public function __construct(string $color, ?string $someParameter = null)
    {
        $this->color = $color;
        $this->someParameter = $someParameter;
    }

    protected function doEnhance($gc, Node $node, Document $document): void
    {
    }
}
