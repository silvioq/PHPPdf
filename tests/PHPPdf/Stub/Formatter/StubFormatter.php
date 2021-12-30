<?php

declare(strict_types=1);

namespace PHPPdf\Stub\Formatter;

use PHPPdf\Core\Document;
use PHPPdf\Core\Formatter\BaseFormatter;
use PHPPdf\Core\Node\Node;

class StubFormatter extends BaseFormatter
{
    public function format(Node $node, Document $document)
    {
    }
}
