<?php

declare(strict_types=1);

namespace PHPPdf\Stub\Node;

use PHPPdf\Core\Node\Node;

class StubNode extends Node
{
    public function initialize(): void
    {
        parent::initialize();
        $this->addAttribute('name-two');
        $this->addAttribute('name', 'value');
    }

    public function setNameTwo($value): void
    {
        $this->setAttributeDirectly('name-two', $value.' from setter');
    }
}
