<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Node\Container;
use PHPPdf\ObjectMother\NodeObjectMother;
use PHPPdf\Core\Document,
    PHPPdf\Core\Formatter\VerticalAlignFormatter;
use PHPPdf\PHPUnit\Framework\TestCase;

class VerticalAlignFormatterTest extends TestCase
{
    private VerticalAlignFormatter $formatter;
    private Document               $document;

    private NodeObjectMother $objectMother;

    protected function init(): void
    {
        $this->objectMother = new NodeObjectMother($this);
    }

    public function setUp(): void
    {
        $this->formatter = new VerticalAlignFormatter();
        $this->document  = $this->createDocumentStub();
    }

    /**
     * @test
     * @dataProvider alignSingleNodeProvider
     */
    public function alignSingleNode($parentHeight, $childHeight, $childYCoord, $align, $expectedYCoord): void
    {
        $container = new Container();
        $this->invokeMethod($container, 'setBoundary', [$this->objectMother->getBoundaryStub(0, $parentHeight, 500, $parentHeight)]);

        $child = new Container();
        $this->invokeMethod($child, 'setBoundary', [$this->objectMother->getBoundaryStub(0, $childYCoord, 500, $childHeight)]);
        $container->add($child);

        $container->setAttribute('vertical-align', $align);

        $this->formatter->format($container, $this->document);

        $this->assertEquals($expectedYCoord, $child->getFirstPoint()->getY());
    }

    public function alignSingleNodeProvider(): array
    {
        return [
            [500, 300, 500, 'top', 500],
            [500, 300, 500, 'middle', 400],
            [500, 300, 500, 'bottom', 300],
        ];
    }
}
