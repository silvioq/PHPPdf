<?php

namespace PHPPdf\Test\Core;

use PHPPdf\Core\DrawingTaskHeap;
use PHPPdf\Core\Document,
    PHPPdf\Font\Registry as FontRegistry,
    PHPPdf\Core\Node\Page;
use PHPPdf\Core\ComplexAttribute\Border;
use PHPPdf\Core\AttributeBag;
use PHPPdf\Core\ComplexAttribute\ComplexAttributeFactory;

class DocumentTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $document;
    private $engine;

    public function setUp(): void
    {
        $this->engine = $this->createMock('PHPPdf\Core\Engine\Engine');
        $this->document = new Document($this->engine);
    }

    /**
     * @test
     */
    public function invokeDrawingTasksOfPagesWhenDrawMethodIsInvoked($assertArguments = true)
    {
        $tasks = array();
        for($i=0; $i<3; $i++)
        {
            $taskMock = $this->getMockBuilder('PHPPdf\Core\DrawingTask')
                             ->onlyMethods(array('invoke'))
                             ->disableOriginalConstructor()
                             ->getMock();
            $taskMock->expects($this->once())
                     ->method('invoke');
            $tasks[] = $taskMock;
        }
                 
        $mock = $this->createPartialMock('\PHPPdf\Core\Node\PageCollection', array('getAllDrawingTasks', 'format'));

        $matcher = $mock->expects($this->once())
                        ->method('format')
                        ->id('1');
        
        if($assertArguments)
        {
            $matcher->with($this->document);
        }

        $mock->expects($this->once())
             ->after('1')
             ->method('getAllDrawingTasks')
             ->willReturn($tasks);

        $this->document->draw($mock);
    }

    /**
     * @test
     */
    public function drawMethodCanBeMultiplyCalledIfDocumentStatusHaveResetByInitializeMethod()
    {
        $this->invokeDrawingTasksOfPagesWhenDrawMethodIsInvoked(false);
        $this->document->initialize();
        $this->invokeDrawingTasksOfPagesWhenDrawMethodIsInvoked(false);
    }

    /**
     * @test
     *
     */
    public function throwExceptionWhenDocumentIsDrawTwiceWithoutReset()
    {
        $this->expectException(\PHPPdf\Exception\LogicException::class);
        $mock = $this->getMockBuilder('\PHPPdf\Core\Node\Page')
        ->enableOriginalConstructor()
        ->onlyMethods(array('collectOrderedDrawingTasks'))
        ->getMock();

        $mock->expects($this->once())
             ->method('collectOrderedDrawingTasks')
             ->willReturn(array());

        $this->document->draw(array($mock));
        $this->document->draw(array($mock));
    }

    /**
     * @test
     *
     */
    public function drawingArgumentMustBeAnArrayOfPages()
    {
        $this->expectException(\PHPPdf\Core\Exception\DrawingException::class);
        $this->document->draw(array(new \PHPPdf\Core\Node\Container()));
    }

    /**
     * @test
     */
    public function creationOfComplexAttributes()
    {
        $complexAttributesParameters = array('border' => array('color' => 'red', 'name' => 'border'), 'background' => array('name' => 'background', 'color' => 'pink', 'repeat' => 'none'), 'empty' => array('name' => 'empty'));
        $complexAttributeStub = $this->getMockBuilder(Border::class)
                                ->onlyMethods(array('isEmpty'))
                                ->getMock();
                                
        $complexAttributeStub->expects($this->exactly(2))
                        ->method('isEmpty')
                        ->willReturn(false);
                                
        $emptyComplexAttributeStub = $this->getMockBuilder(Border::class)
                                     ->onlyMethods(array('isEmpty'))
                                     ->getMock();
                                     
        $emptyComplexAttributeStub->expects($this->once())
                             ->method('isEmpty')
                             ->willReturn(true);

        $complexAttributeBagMock = $this->createPartialMock(AttributeBag::class, array('getAll'));
        $complexAttributeBagMock->expects($this->once())
                           ->method('getAll')
                           ->willReturn($complexAttributesParameters);
                           
        $complexAttributesMap = array(
            'border' => $complexAttributeStub,
            'background' => $complexAttributeStub,
            'empty' => $emptyComplexAttributeStub,
        );

        $complexAttributeFactoryMock = $this->createPartialMock(ComplexAttributeFactory::class, array('create'));
        
        $at = 0;
        $expectedArguments = [];
        $expectedReturns = [];
        foreach($complexAttributesParameters as $name => $params)
        {
            $expectedArguments[$at++] = [
                $this->equalTo($name), $this->equalTo(array_diff_key($params, array('name' => true)))
        ];
            $expectedReturns[$at++] = $this->returnValue($complexAttributesMap[$name]);
            
        }

        $complexAttributeFactoryMock
            ->method('create')
            ->withConsecutive(...$expectedArguments)
        ->willReturnOnConsecutiveCalls(...$expectedReturns);

        $this->document->setComplexAttributeFactory($complexAttributeFactoryMock);

        $complexAttributes = $this->document->getComplexAttributes($complexAttributeBagMock);

        $this->assertSame(count($complexAttributes), 2, 'empty complexAttribute should not be returned by Document');
    }

    /**
     * @test
     *
     */
    public function failureOfComplexAttributeCreation()
    {
        $this->expectException(\PHPPdf\Exception\InvalidArgumentException::class);
        $complexAttributes = array('some' => array('color' => 'red'));

        $complexAttributeBagMock = $this->createPartialMock('PHPPdf\Core\AttributeBag', array('getAll'));
        $complexAttributeBagMock->expects($this->once())
                           ->method('getAll')
                           ->willReturn($complexAttributes);

        $complexAttributeFactoryMock = $this->createPartialMock('PHPPdf\Core\ComplexAttribute\ComplexAttributeFactory', array('create'));
        $complexAttributeFactoryMock->expects($this->never())
                               ->method('create');

        $this->document->setComplexAttributeFactory($complexAttributeFactoryMock);

        $this->document->getComplexAttributes($complexAttributeBagMock);
    }

    /**
     * @test
     */
    public function createFormatterByClassName()
    {
        $className = 'PHPPdf\Core\Formatter\FloatFormatter';

        $formatter = $this->document->getFormatter($className);

        $this->assertInstanceOf($className, $formatter);

        $this->assertTrue($formatter === $this->document->getFormatter($className));
    }

    /**
     * @test
     *
     * @dataProvider invalidClassNamesProvider
     */
    public function throwExceptionIfPassInvalidFormatterClassName($className)
    {
        $this->expectException(\PHPPdf\Exception\RuntimeException::class);
        $this->document->getFormatter($className);
    }

    public function invalidClassNamesProvider()
    {
        return array(
            array('stdClass'),
            array('UnexistedClass'),
        );
    }
    
    /**
     * @test
     */
    public function useUnitConverterForConversions()
    {
        $unitConverter = $this->getMockBuilder('PHPPdf\Core\UnitConverter')
                              ->getMock();
                              
        $this->document->setUnitConverter($unitConverter);
        
        $actualUnit = '12px';
        $expectedUnit = 123;
        $actualPercent = '10%';
        $width = 120;
        $expectedPercent = 10;
        
        $unitConverter->expects($this->once())
                      ->method('convertUnit')
                      ->with($actualUnit)
                      ->willReturn($expectedUnit);
                      
        $unitConverter->expects($this->once())
                      ->method('convertPercentageValue')
                      ->with($actualPercent, $width)
                      ->willReturn($expectedPercent);
                      
        $this->assertEquals($expectedUnit, $this->document->convertUnit($actualUnit));
        $this->assertEquals($expectedPercent, $this->document->convertPercentageValue($actualPercent, $width));
    }
    
    /**
     * @test
     */
    public function getColorFromPalette()
    {
        $palette = $this->getMockBuilder('PHPPdf\Core\ColorPalette')
                        ->onlyMethods(array('get'))
                        ->getMock();
                        
        $this->document->setColorPalette($palette);
        
        $color = 'color';
        $result = 'result';
        
        $palette->expects($this->once())
                ->method('get')
                ->with($color)
                ->willReturn($result);
                
        $this->assertEquals($result, $this->document->getColorFromPalette($color));
    }
    
    /**
     * @test
     * @dataProvider createFontProvider
     */
    public function filterFontsPathsOnFontCreation($value, array $filterValues)
    {
        $previousValue = $value;
        $filters = array();
        foreach($filterValues as $filterValue)
        {
            $filter = $this->createMock('PHPPdf\Util\StringFilter');
            $filter->expects($this->once())
                   ->method('filter')
                   ->with($previousValue)
                   ->willReturn($filterValue);
            $previousValue = $filterValue;
            $filters[] = $filter;
        }

        $this->document->setStringFilters($filters);   

        $fontDefinition = array('normal' => $value);
        $font = $this->createMock('PHPPdf\Core\Engine\Font');
        
        $this->engine->expects($this->once())
                     ->method('createFont')
                     ->with(array('normal' => $previousValue))
                     ->willReturn($font);

        $this->assertEquals($font, $this->document->createFont($fontDefinition));
    }
    
    public function createFontProvider()
    {
        return array(
            array('some font', array(
                'another font',
                'some another font',
            )),
            array('some font', array()),
        );
    }
}
