<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Node\Container;

use PHPPdf\ObjectMother\NodeObjectMother;

use PHPPdf\Core\Formatter\FirstPointPositionFormatter,
    PHPPdf\Core\Document;
use PHPPdf\PHPUnit\Framework\TestCase;

class FirstPointPositionFormatterTest extends TestCase
{
    private FirstPointPositionFormatter $formatter;
    private NodeObjectMother            $objectMother;

    public function setUp(): void
    {
        $this->formatter    = new FirstPointPositionFormatter();
        $this->objectMother = new NodeObjectMother($this);
    }

    /**
     * @test
     * @dataProvider attributeProvider
     */
    public function designateFirstPointIfNodeHasntPreviousSibling($parentFirstPoint, $marginLeft, $marginTop): void
    {
        $parent = $this->getMockBuilder(Container::class)
                       ->enableOriginalConstructor()
                       ->onlyMethods(['getStartDrawingPoint'])
                       ->getMock();

        $parent->expects($this->atLeastOnce())
               ->method('getStartDrawingPoint')
               ->willReturn($parentFirstPoint);

        $node = $this->getMockBuilder(Container::class)
                     ->enableOriginalConstructor()
                     ->onlyMethods(['getParent', 'getPreviousSibling', 'getMarginLeft', 'getMarginTop'])
                     ->getMock();

        $node->expects($this->atLeastOnce())
             ->method('getParent')
             ->willReturn($parent);
        $node->expects($this->once())
             ->method('getPreviousSibling')
             ->willReturn(null);
        $node->expects($this->once())
             ->method('getMarginLeft')
             ->willReturn($marginLeft);
        $node->expects($this->once())
             ->method('getMarginTop')
             ->willReturn($marginTop);

        $this->formatter->format($node, $this->createDocumentStub());

        $parentFirstPoint[0] += $marginLeft;
        $parentFirstPoint[1] -= $marginTop;
        $this->assertEquals($parentFirstPoint, $node->getBoundary()->getFirstPoint()->toArray());
    }

    public function attributeProvider(): array
    {
        return [
            [[0, 600], 0, 0],
            [[0, 600], 10, 10],
        ];
    }

    /**
     * @test
     * @dataProvider booleanProvider
     */
    public function properlyLineBreaking($lineBreakOfPreviousSibling): void
    {
        $parentFirstPoint = [0, 100];
        $lineHeight       = 20;

        $parent = $this->getMockBuilder(Container::class)
                       ->enableOriginalConstructor()
                       ->onlyMethods(['getStartDrawingPoint'])
                       ->getMock();

        $parent->expects($this->atLeastOnce())
               ->method('getStartDrawingPoint')
               ->willReturn($parentFirstPoint);

        $previousSibling = new Container();
        $boundary        = $this->objectMother->getBoundaryStub($parentFirstPoint[0], $parentFirstPoint[1], 100, 0);
        $this->invokeMethod($previousSibling, 'setBoundary', [$boundary]);
        $previousSibling->setAttribute('line-break', $lineBreakOfPreviousSibling);

        $node = $this->getMockBuilder(Container::class)
                     ->enableOriginalConstructor()
                     ->onlyMethods(['getParent', 'getPreviousSibling', 'getLineHeightRecursively'])
                     ->getMock();
        $node->setAttribute('line-break', true);
        $node->expects($this->atLeastOnce())
             ->method('getParent')
             ->willReturn($parent);
        $node->expects($this->atLeastOnce())
             ->method('getPreviousSibling')
             ->willReturn($previousSibling);
        $node
             ->method('getLineHeightRecursively')
             ->willReturn($lineHeight);

        $this->formatter->format($node, $this->createDocumentStub());

        //break line only when previous sibling also has line-break attribute on
        $expectedYCoord = $lineBreakOfPreviousSibling ? ($parentFirstPoint[1] - $lineHeight) : $parentFirstPoint[1];

        $this->assertEquals($expectedYCoord, $node->getFirstPoint()->getY());
    }

    public function booleanProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }
}
