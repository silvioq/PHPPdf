<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\DrawingTaskHeap;

use PHPPdf\Core\Node\Node;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Document;
use PHPPdf\Core\Node\BasicList;
use PHPPdf\ObjectMother\NodeObjectMother;
use PHPPdf\Core\Node\BasicList\EnumerationStrategy;
use PHPPdf\Core\Engine\GraphicsContext;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Node\BasicList\EnumerationStrategyFactory;

class BasicListTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $list;
    private $objectMother;
    
    public function init(): void
    {
        $this->objectMother = new NodeObjectMother($this);
    }
    
    public function setUp(): void
    {
        $this->list = new BasicList();
    }
    
    /**
     * @test
     * @dataProvider sizesProvider
     */
    public function renderListTypeForEachChildren($numberOfChildren)
    {
        $page = $this->createPartialMock(Page::class, array('getGraphicsContext', 'getAttribute'));
        
        $gc = $this->getMockBuilder(GraphicsContext::class)
                   ->getMock();
        
        $page->expects($this->atLeastOnce())
             ->method('getGraphicsContext')
             ->willReturn($gc);
             
        $this->list->setParent($page);
        $enumerationStrategy = $this->getMockBuilder(EnumerationStrategy::class)
                                    ->getMock();
        $enumerationStrategy->expects($this->once())
                            ->method('setIndex')
                            ->with(0);
        $document = $this->createDocumentStub();
        
        $this->list->setEnumerationStrategy($enumerationStrategy);

        $expectedArguments = [];
        for($i=0; $i<$numberOfChildren; $i++)
        {
            $this->list->add(new Container());
            $expectedArguments[$i+1] = [$document, $this->list, $gc, $i];
        }

        $enumerationStrategy
            ->method('drawEnumeration')
            ->withConsecutive(...$expectedArguments);

        $enumerationStrategy->expects($this->once())
                            ->method('reset');
        
        $tasks = new DrawingTaskHeap();
        $this->list->collectOrderedDrawingTasks($document, $tasks);
        
        foreach($tasks as $task)
        {
            $task->invoke();
        }
    }
    
    public function sizesProvider(): array
    {
        return array(
            array(5),
            array(10),
        );
    }
    
    /**
     * @test
     */
    public function acceptHumanReadableTypeAttributeValue(): void
    {
        $types = array(
            'circle' => BasicList::TYPE_CIRCLE,
            'disc' => BasicList::TYPE_DISC,
            'square' => BasicList::TYPE_SQUARE,
            'none' => BasicList::TYPE_NONE,
        );
        
        foreach($types as $name => $value)
        {
            $this->list->setAttribute('type', $name);
            
            $this->assertEquals($value, $this->list->getAttribute('type'));
        }
    }
    
    /**
     * @test
     * @dataProvider enumerationProvider
     */
    public function determineEnumerationStrategyOnType($type, $expectedEnumerationStrategyClass): void
    {
        $this->list->setAttribute('type', $type);
        
        $factory = $this->createPartialMock(EnumerationStrategyFactory::class, array('create'));
        
        $expectedStrategy = new $expectedEnumerationStrategyClass();
        $factory->expects($this->once())
                ->method('create')
                ->with($type)
                ->willReturn($expectedStrategy);
                
        $this->list->setEnumerationStrategyFactory($factory);
        
        $this->list->assignEnumerationStrategyFromFactory();
        
        $enumerationStrategy = $this->list->getEnumerationStrategy();
        
        $this->assertTrue($expectedStrategy === $enumerationStrategy);
    }
    
    public function enumerationProvider()
    {
        return array(
            array(BasicList::TYPE_CIRCLE, 'PHPPdf\Core\Node\BasicList\UnorderedEnumerationStrategy'),
            array(BasicList::TYPE_SQUARE, 'PHPPdf\Core\Node\BasicList\UnorderedEnumerationStrategy'),
            array(BasicList::TYPE_DISC, 'PHPPdf\Core\Node\BasicList\UnorderedEnumerationStrategy'),
            array(BasicList::TYPE_NONE, 'PHPPdf\Core\Node\BasicList\UnorderedEnumerationStrategy'),
            array(BasicList::TYPE_DECIMAL, 'PHPPdf\Core\Node\BasicList\OrderedEnumerationStrategy'),
        );
    }
    
    /**
     * @test
     */
    public function createNewEnumerationStrategyOnlyWhenTypeWasChanged()
    {
        $font = $this->getMockBuilder('PHPPdf\Core\Engine\Font')
                     ->getMock();
        $this->list->setAttribute('font-type', $font);
        
        $type = BasicList::TYPE_CIRCLE;
        $this->list->setAttribute('type', $type);
        
        $factory = $this->createPartialMock('PHPPdf\Core\Node\BasicList\EnumerationStrategyFactory', array('create'));
        
        $strategyStub = 'some-stub1';
        $factory->expects($this->once())
                ->method('create')
                ->with($type)
                ->willReturn($strategyStub);
        $this->list->setEnumerationStrategyFactory($factory);
        
        $this->assertTrue($strategyStub === $this->list->getEnumerationStrategy());
        $this->assertTrue($strategyStub === $this->list->getEnumerationStrategy());
        
        $enumerationStrategy = $this->list->getEnumerationStrategy();
        
        $type = BasicList::TYPE_DECIMAL;
        $strategyStub = 'some-stub2';
        
        $factory = $this->createPartialMock('PHPPdf\Core\Node\BasicList\EnumerationStrategyFactory', array('create'));
        $factory->expects($this->once())
                ->method('create')
                ->with($type)
                ->willReturn($strategyStub);
        $this->list->setEnumerationStrategyFactory($factory);
        
        $this->list->setAttribute('type', $type);
        
        $this->assertFalse($enumerationStrategy === $this->list->getEnumerationStrategy());
    }
}
