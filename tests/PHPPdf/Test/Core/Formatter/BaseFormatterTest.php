<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Formatter\BaseFormatter;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Stub\Formatter\StubFormatter;

abstract class BaseFormatterTest extends TestCase
{
    private StubFormatter $formatter;

    public function setUp(): void
    {
        $this->formatter = new StubFormatter();
    }

    /**
     * @test
     *
     */
    public function throwExceptionIfTryToGetUnsettedDocument(): void
    {
        $this->expectException(\LogicException::class);
        $this->formatter->getDocument();
    }

    /**
     * @test
     */
    public function dontThrowExceptionIfDocumentIsSet(): void
    {
        $document = $this->createDocumentStub();
        $this->formatter->setDocument($document);

        $this->assertSame($document, $this->formatter->getDocument());
    }

    /**
     * @test
     *
     */
    public function unserializedFormatterHaveDocumentDetached(): void
    {
        $this->expectException(\LogicException::class);
        $document = $this->createDocumentStub();
        $this->formatter->setDocument($document);

        $unserializedFormatter = unserialize(serialize($this->formatter));

        $unserializedFormatter->getDocument();
    }
}
