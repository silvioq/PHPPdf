<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\ComplexAttribute;

use PHPPdf\Core\ComplexAttribute\ComplexAttribute;
use PHPPdf\Core\Document;
use PHPPdf\Core\Point;
use PHPPdf\Core\Engine\GraphicsContext;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Core\Node\Circle;


abstract class ComplexAttributeTest extends TestCase
{
    protected Document $document;

    protected function assertDrawCircle(ComplexAttribute $attribute, $color, $radius, Point $centerPoint, $fillType): void
    {
        $gc = $this->createMock(GraphicsContext::class);

        $node = $this->getMockBuilder(Circle::class)
                     ->onlyMethods(['getGraphicsContext', 'getMiddlePoint'])
                     ->getMock();
        $node->setAttribute('radius', $radius);

        $node->expects($this->atLeastOnce())
             ->method('getGraphicsContext')
             ->willReturn($gc);
        $node->expects($this->atLeastOnce())
             ->method('getMiddlePoint')
             ->willReturn($centerPoint);

        $gc->expects($this->once())
           ->method('drawEllipse')
           ->with($centerPoint->getX(), $centerPoint->getY(), $radius * 2, $radius * 2, $fillType);

        $attribute->enhance($node, $this->document);
    }
}
