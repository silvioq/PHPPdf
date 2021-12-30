<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Document;
use PHPPdf\Core\Boundary;
use PHPPdf\Core\Node\Node;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Formatter\ContainerDimensionFormatter;
use PHPPdf\ObjectMother\NodeObjectMother;
use PHPPdf\PHPUnit\Framework\TestCase;

class ContainerDimensionFormatterTest extends TestCase
{
    private ContainerDimensionFormatter $formatter;
    private NodeObjectMother            $objectMother;

    protected function init(): void
    {
        $this->objectMother = new NodeObjectMother($this);
    }

    public function setUp(): void
    {
        $this->formatter = new ContainerDimensionFormatter();
    }

    /**
     * @test
     */
    public function nodeFormatter(): void
    {
        $composeNode = new Container();
        $composeNode->setWidth(140);
        $children   = [];
        $children[] = $this->objectMother->getNodeStub(0, 500, 100, 200);
        $children[] = $this->objectMother->getNodeStub(0, 300, 200, 200);

        foreach ($children as $child) {
            $composeNode->add($child);
        }

        $this->formatter->format($composeNode, $this->createDocumentStub());

        $height = 0;
        foreach ($children as $child) {
            $height += $child->getHeight();
        }

        $this->assertEquals($height, $composeNode->getHeight());
        $this->assertEquals(200, $composeNode->getWidth());
    }
}
