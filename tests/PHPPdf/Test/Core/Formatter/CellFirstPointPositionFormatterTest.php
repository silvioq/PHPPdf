<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Formatter\CellFirstPointPositionFormatter,
    PHPPdf\Core\Document,
    PHPPdf\Core\Point;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Boundary;

class CellFirstPointPositionFormatterTest extends TestCase
{
    private CellFirstPointPositionFormatter $formatter;

    public function setUp(): void
    {
        $this->formatter = new CellFirstPointPositionFormatter();
    }

    /**
     * @test
     */
    public function setFirstPointAsFirstPointOfParent(): void
    {
        $firstPoint = Point::getInstance(0, 500);

        $parent = $this->createPartialMock(Container::class, ['getFirstPoint']);
        $parent->expects($this->atLeastOnce())
               ->method('getFirstPoint')
               ->willReturn($firstPoint);

        $boundary = $this->createPartialMock(Boundary::class, ['setNext']);
        $boundary->expects($this->once())
                 ->method('setNext')
                 ->with($firstPoint);

        $node = $this->createPartialMock(Container::class, ['getParent', 'getBoundary']);
        $node->expects($this->atLeastOnce())
             ->method('getParent')
             ->willReturn($parent);
        $node->expects($this->atLeastOnce())
             ->method('getBoundary')
             ->willReturn($boundary);

        $this->formatter->format($node, $this->createDocumentStub());
    }
}
