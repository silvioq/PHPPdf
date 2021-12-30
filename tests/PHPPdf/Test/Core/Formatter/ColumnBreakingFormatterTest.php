<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Node\ColumnableContainer,
    PHPPdf\Core\Node\Container,
    PHPPdf\Core\Node\Page,
    PHPPdf\Core\Boundary,
    PHPPdf\Core\Document,
    PHPPdf\Core\Formatter\ColumnBreakingFormatter;
use PHPPdf\PHPUnit\Framework\TestCase;

class ColumnBreakingFormatterTest extends TestCase
{
    private Page                    $page;
    private ColumnableContainer     $column;
    private ColumnBreakingFormatter $formatter;

    public function setUp(): void
    {
        $this->page   = new Page();
        $this->column = new ColumnableContainer();

        $this->column->setHeight($this->page->getHeight() * 1.5);
        $this->column->setWidth($this->page->getWidth() / 2);

        $this->formatter = new ColumnBreakingFormatter();
    }

    private function injectBoundary(Container $container, $yStart = 0): void
    {
        $parent = $container->getParent();
        $point  = $parent->getFirstPoint();

        $boundary = new Boundary();
        $boundary->setNext($point->translate(0, $yStart))
                 ->setNext($point->translate($container->getWidth(), $yStart))
                 ->setNext($point->translate($container->getWidth(), $yStart + $container->getHeight()))
                 ->setNext($point->translate(0, $yStart + $container->getHeight()))
                 ->close();

        $this->invokeMethod($container, 'setBoundary', [$boundary]);
    }

    /**
     * @test
     */
    public function formatColumnsAndSetValidPositionOfContainers(): void
    {
        $this->page->add($this->column);

        $pageHeight = $this->page->getHeight();
        $width      = 100;

        $containersHeight = [$pageHeight, $pageHeight / 2, $pageHeight / 3];
        $this->column->setHeight(array_sum($containersHeight));
        $this->injectBoundary($this->column);

        $containers = $this->createContainers($containersHeight);

        $this->formatter->format($this->page, $this->createDocumentStub());

        $this->assertCount(2, $this->column->getChildren());

        foreach ($this->column->getChildren() as $container) {
            $children           = $container->getChildren();
            $firstChild         = current($children);
            $expectedFirstPoint = $firstChild->getFirstPoint();

            $lastIndex              = count($children) - 1;
            $lastChild              = $children[$lastIndex];
            $expectedDiagonalYCoord = min($lastChild->getDiagonalPoint()->getY(), $firstChild->getDiagonalPoint()->getY());
            $actualDiagonalYCoord   = $container->getDiagonalPoint()->getY();

            $this->assertEqualsWithDelta($expectedFirstPoint->getX(), $container->getFirstPoint()->getX(), 0.000001, 'x coord of columns\' first point isn\'t equals');
            $this->assertEqualsWithDelta($expectedFirstPoint->getY(), $container->getFirstPoint()->getY(), 0.000001, 'y coord of columns\' first point isn\'t equals');
            $this->assertEqualsWithDelta($expectedDiagonalYCoord, $actualDiagonalYCoord, 0.000001, 'two columns haven\'t the same height');
        }

        $this->assertEqualsWithDelta($containers[1]->getFirstPoint()->getX(), $containers[2]->getFirstPoint()->getX(), 0.000001, 'x coord of two containers within the same column is not equal');

        $this->assertEqualsWithDelta($pageHeight, $this->column->getHeight(), 0.000001, 'height of page isn\'t equals to the highest column');
    }

    private function createContainers(array $heights, $parent = null)
    {
        $parent = $parent ?: $this->column;
        $width  = 100;

        $yStart = 0;

        $containers = [];
        foreach ($heights as $height) {
            $container = new Container();
            $container->setHeight($height);
            $container->setWidth($width);

            $parent->add($container);

            $this->injectBoundary($container, $yStart);
            $yStart += $height;

            $containers[] = $container;
        }

        return $containers;
    }

    /**
     * @test
     */
    public function secondRowOfColumnsShouldBeDirectlyUnderFirstRow(): void
    {
        $this->page->add($this->column);

        $pageHeight = $this->page->getHeight();

        $containersHeights = [$pageHeight, $pageHeight, $pageHeight];
        $this->column->setHeight(array_sum($containersHeights));
        $this->injectBoundary($this->column);

        $containers = $this->createContainers($containersHeights);

        $this->formatter->format($this->page, $this->createDocumentStub());

        $columns = $this->column->getChildren();

        $this->assertEquals($columns[0]->getDiagonalPoint()->getY(), $columns[2]->getFirstPoint()->getY());

        $this->assertEquals(2 * $pageHeight, $this->column->getHeight());
    }

    /**
     * @test
     */
    public function containerInSecondColumnHasTheSameYCoordAsFirstContainer(): void
    {
        $stubs = $this->createContainers([20], $this->page);

        $pageHeight = $this->page->getHeight();

        $this->page->add($this->column);
        $this->injectBoundary($this->column, 20);
        $containers = $this->createContainers([$pageHeight * 1.5]);

        $this->formatter->format($this->page, $this->createDocumentStub());

        $columns = $this->column->getChildren();

        $this->assertEquals($stubs[0]->getDiagonalPoint()->getY(), $columns[0]->getFirstPoint()->getY());
        $this->assertEquals($columns[0]->getFirstPoint()->getY(), $columns[1]->getFirstPoint()->getY());

        foreach ($this->column->getChildren() as $container) {
            foreach ($container->getChildren() as $child) {
                $this->assertEquals($container->getFirstPoint()->getY(), $child->getFirstPoint()->getY());
            }
        }
    }

    /**
     * @test
     */
    public function calculateCorrectBreakPointWhenColumnStartsInMiddleOfPage(): void
    {
        $pageHeight = $this->page->getHeight();

        $heightOfFirstContainer = 20;

        [$fistContainer, $columnableContainerChild] = $this->createContainers([$heightOfFirstContainer, 4 * $pageHeight - 2 * $heightOfFirstContainer], $this->page);
        $this->column->add($columnableContainerChild);
        $this->page->add($this->column);

        $this->column->setHeight($columnableContainerChild->getHeight());
        $this->injectBoundary($this->column, $heightOfFirstContainer);

        $this->formatter->format($this->page, $this->createDocumentStub());

        $this->assertCount(4, $this->column->getChildren());

        foreach ($this->column->getChildren() as $container) {
            $this->assertEquals(0, $container->getDiagonalPoint()->getY() % $pageHeight, 'y coord of one container\'s bottom is not equal to page bottom');
        }
    }

    /**
     * @test
     */
    public function columnsShouldBeEqualIfEqualsColumnsAttributeIsSet(): void
    {
        $this->page->add($this->column);
        $pageHeight = $this->page->getHeight();
        $this->column->setAttribute('equals-columns', true);

        $containerHeights = [$pageHeight / 4, $pageHeight / 4];
        $this->column->setHeight(array_sum($containerHeights));
        $this->injectBoundary($this->column);

        $containers = $this->createContainers($containerHeights);

        $this->formatter->format($this->page, $this->createDocumentStub());

        $this->assertCount(2, $this->column->getChildren());
    }

    /**
     * @test
     */
    public function calculateValidColumnsPositionAndDimensionWhenEqualsColumnsAttributeIsSetAndColumnStartsInMiddleOnPage(): void
    {
        $pageHeight = $this->page->getHeight();
        $this->column->setAttribute('equals-columns', true);

        $heightOfFirstContainer = 20;

        [$fistContainer, $columnableContainerChild] = $this->createContainers([$heightOfFirstContainer, 2 * $pageHeight], $this->page);
        $this->column->add($columnableContainerChild);
        $this->page->add($this->column);

        $this->column->setHeight($columnableContainerChild->getHeight());
        $this->injectBoundary($this->column, $heightOfFirstContainer);

        $this->formatter->format($this->page, $this->createDocumentStub());

        $this->assertCount(4, $this->column->getChildren());

        foreach ($this->column->getChildren() as $i => $container) {
            if ($i < 2) {
                $expectedYCoord = $this->page->getFirstPoint()->getY() - $heightOfFirstContainer;
            } else {
                $expectedYCoord = $this->page->getDiagonalPoint()->getY();
            }

            $this->assertEquals($expectedYCoord, $container->getFirstPoint()->getY());
        }
    }

    /**
     * @test
     */
    public function breakColumnIfChildHasBreakAttributeOn(): void
    {
        $pageHeight = $this->page->getHeight();
        $this->page->add($this->column);

        $containerHeights = [$pageHeight / 4, $pageHeight / 4, 1, $pageHeight / 4, $pageHeight / 4, 1, $pageHeight * 2.5, 1, $pageHeight / 4];
        $totalHeight      = array_sum($containerHeights);
        $this->column->setHeight($totalHeight);
        $this->injectBoundary($this->column);

        $containers = $this->createContainers($containerHeights);

        foreach ([2, 5, 7] as $index) {
            $containers[$index]->setAttribute('break', true);
        }

        $this->formatter->format($this->page, $this->createDocumentStub());

        $this->assertCount(6, $this->column->getChildren());

        $columns = $this->column->getChildren();

        for ($i = 0, $count = count($columns); $i < $count; $i++) {
            if ($i < ($count - 1)) {
                $this->assertEquals($pageHeight, $columns[$i]->getHeight());
            }
        }
    }
}
