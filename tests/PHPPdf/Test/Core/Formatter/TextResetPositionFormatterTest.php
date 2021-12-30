<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Document,
    PHPPdf\Core\Point,
    PHPPdf\Core\Boundary,
    PHPPdf\Core\Formatter\TextResetPositionFormatter;
use PHPPdf\Core\Node\Text;
use PHPPdf\PHPUnit\Framework\TestCase;

class TextResetPositionFormatterTest extends TestCase
{
    private TextResetPositionFormatter $formatter;

    public function setUp(): void
    {
        $this->formatter = new TextResetPositionFormatter();
    }

    /**
     * @test
     */
    public function clearBoundaryAndAddOldFirstPoint(): void
    {
        $nodeMock = $this->createPartialMock(Text::class, array('getBoundary'));

        $boundary = new Boundary();
        $boundary->setNext(0, 100)
                 ->setNext(100, 100)
                 ->setNext(100, 0)
                 ->setNext(0, 0)
                 ->close();

        $firstPoint = $boundary->getFirstPoint();

        $nodeMock->expects($this->atLeastOnce())
                  ->method('getBoundary')
                  ->willReturn($boundary);

        $this->formatter->format($nodeMock, $this->createDocumentStub());

        $this->assertFalse($boundary->isClosed());
        $this->assertEquals($firstPoint, $boundary->getFirstPoint());
        $this->assertCount(1, $boundary);
    }
}
