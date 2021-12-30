<?php

namespace PHPPdf\Test\Core\Parser;

use PHPPdf\Core\Node\Paragraph;
use PHPPdf\Core\Node\Text;
use PHPPdf\Core\Parser\Exception\DuplicatedIdException;
use PHPPdf\Core\Parser\Exception\IdNotFoundException;
use PHPPdf\Core\Parser\XmlDocumentParser,
    PHPPdf\Core\Node\NodeFactory,
    PHPPdf\Core\ComplexAttribute\ComplexAttributeFactory,
    PHPPdf\Core\Node\PageCollection;
use PHPPdf\Core\Parser\DocumentParserListener;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Document;
use PHPPdf\Parser\Exception\InvalidTagException;
use PHPPdf\Parser\Exception\ParseException;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Core\Parser\StylesheetConstraint;
use PHPPdf\Core\Parser\StylesheetParser;
use PHPPdf\Core\Parser\BagContainer;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Node\Behaviour\Factory;
use PHPPdf\Core\Node\Behaviour\Behaviour;

class XmlDocumentParserTest extends TestCase
{
    private XmlDocumentParser $parser;
    private                   $documentMock;
    private                   $complexAttributeFactoryMock;

    public function setUp(): void
    {
        $this->documentMock = $this->getMockBuilder(Document::class)
                                   ->disableOriginalConstructor()
                                   ->onlyMethods(['setMetadataValue'])
                                   ->disableOriginalConstructor()
                                   ->getMock();

        $this->complexAttributeFactoryMock = $this->createPartialMock(ComplexAttributeFactory::class, ['create', 'getDefinitionNames']);

        $this->parser = new XmlDocumentParser($this->complexAttributeFactoryMock, $this->documentMock);
    }

    /**
     * @test
     */
    public function settingAndGettingProperties(): void
    {
        $factory = new NodeFactory();

        $this->assertInstanceOf(NodeFactory::class, $this->parser->getNodeFactory());

        $this->parser->setNodeFactory($factory);

        $this->assertSame($factory, $this->parser->getNodeFactory());
    }

    /**
     * @test
     *
     */
    public function invalidRoot(): void
    {
        $this->expectException(InvalidTagException::class);
        $xml = '<invalid-root></invalid-root>';

        $this->parser->parse($xml);
    }

    /**
     * @test
     */
    public function validRoot(): void
    {
        $xml = '<pdf></pdf>';

        $result = $this->parser->parse($xml);

        $this->assertInstanceOf(PageCollection::class, $result);
    }

    /**
     * @test
     *
     */
    public function throwsExceptionIfTagDoesntExistsInFactory(): void
    {
        $this->expectException(ParseException::class);
        $xml = '<pdf><tag1 /></pdf>';

        $this->parser->parse($xml);
    }

    /**
     * @test
     * @dataProvider simpleXmlProvider
     */
    public function parseSingleElement($xml): void
    {
        $tag = 'tag';

        $nodeMock    = $this->getNodeMock();
        $mocks       = [[$tag, $nodeMock]];
        $factoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($factoryMock);

        $pageCollection = $this->parser->parse($xml);

        $this->assertInstanceOf(PageCollection::class, $pageCollection);

        $nodes = $pageCollection->getChildren();

        $this->assertCount(1, $nodes);
        $this->assertSame($nodeMock, current($nodes));
    }

    public function simpleXmlProvider(): array
    {
        $xml    = '<pdf><tag /></pdf>';
        $reader = new \XMLReader();
        $reader->XML($xml);
        $reader->read();
        $reader->read();

        return [
            [$xml],
            ['      '.$xml],
            [$reader],
        ];
    }

    private function getNodeFactoryMock(array $mocks = [], $indexStep = 1)
    {
        $factoryMock = $this->createPartialMock(NodeFactory::class, ['create']);

        $index       = 0;
        $mockArgs    = [];
        $mockReturns = [];
        foreach ($mocks as $mockData) {
            [$tag, $mock] = $mockData;

            $mockArgs[$index]    = [$this->equalTo($tag)];
            $mockReturns[$index] = $mock;

            $index += $indexStep;
        }

        $factoryMock->expects($this->exactly(\count($mocks)))
                    ->method('create')
                    ->withConsecutive(...$mockArgs)
                    ->willReturnOnConsecutiveCalls(...$mockReturns);

        return $factoryMock;
    }

    private function getNodeMock(array $attributes = [], $baseClass = Page::class, $methods = [], $setParentExpectation = true)
    {
        $nodeMock = $this->createNodeMock($baseClass, $methods, $setParentExpectation);
        $this->addNodeAttributesExpectations($nodeMock, $attributes);

        return $nodeMock;
    }

    private function createNodeMock($baseClass = Page::class, $methods = [], $setParentExpectation = true)
    {
        $nodeMock = $this->createPartialMock($baseClass, array_merge(['setParent', 'setAttribute'], $methods));
        if ($setParentExpectation) {
            $nodeMock->expects($this->once())
                     ->method('setParent');
        }

        return $nodeMock;
    }

    private function addNodeAttributesExpectations($node, $attributes, $attributeStartIndex = 0): void
    {
        $index       = $attributeStartIndex;
        $mockArgs    = [];
        $mockReturns = [];
        foreach ($attributes as $name => $value) {
            $mockArgs[$index++]  = [$this->equalTo($name), $this->equalTo($value)];
            $mockReturns[$index] = $node;

        }
        $node->expects($this->exactly(\count($attributes)))
             ->method('setAttribute')
             ->withConsecutive(...$mockArgs)
             ->willReturnOnConsecutiveCalls(...$mockReturns);
    }

    /**
     * @test
     */
    public function parseSingleElementWithAttributes(): void
    {
        $xml = '<pdf><tag someName="someValue" anotherName="anotherValue" /></pdf>';

        $nodeMock = $this->getNodeMock(['someName' => 'someValue', 'anotherName' => 'anotherValue']);

        $mocks       = [['tag', $nodeMock]];
        $factoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($factoryMock);

        $pageCollection = $this->parser->parse($xml);

        $nodes = $pageCollection->getChildren();

        $this->assertCount(1, $nodes);
        $this->assertSame($nodeMock, current($nodes));
    }

    /**
     * @test
     */
    public function parseNeastedElementsWithAttributes()
    {
        $xml       = <<<XML
<pdf>
    <tag1 someName="someValue">
        <tag2 anotherName="anotherValue"></tag2>
    </tag1>
</pdf>
XML;
        $nodeMock1 = $this->getNodeMock(['someName' => 'someValue']);
        $nodeMock2 = $this->getNodeMock(['anotherName' => 'anotherValue']);

        $mocks       = [['tag1', $nodeMock1], ['tag2', $nodeMock2]];
        $factoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($factoryMock);

        $pageCollection = $this->parser->parse($xml);

        $this->assertOnlyChild($nodeMock1, $pageCollection);
        $this->assertOnlyChild($nodeMock2, $nodeMock1);

    }

    private function assertOnlyChild($expectedChild, $parentNode)
    {
        $nodes = $parentNode->getChildren();

        $this->assertCount(1, $nodes);
        $this->assertSame($expectedChild, current($nodes));
    }

    /**
     * @test
     */
    public function parseTextElement(): void
    {
        $xml           = <<<XML
<pdf>
    <tag>
        Some text
    </tag>
</pdf>
XML;
        $nodeMock      = $this->getNodeMock();
        $textMock      = $this->getNodeMock([], Text::class, ['setText', 'getText']);
        $paragraphMock = $this->getNodeMock([], Paragraph::class);

        $textMock->expects($this->atLeastOnce())
                 ->method('setText')
                 ->with($this->stringContains('Some text', false))
                 ->willReturn($textMock);
        $textMock->expects($this->atLeastOnce())
                 ->method('getText')
                 ->willReturn('        Some text');

        $mocks       = [['tag', $nodeMock], ['paragraph', $paragraphMock], ['text', $textMock]];
        $factoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($factoryMock);

        $pageCollection = $this->parser->parse($xml);

        $this->assertOnlyChild($nodeMock, $pageCollection);
        $this->assertOnlyChild($paragraphMock, $nodeMock);
        $this->assertOnlyChild($textMock, $paragraphMock);
    }

    /**
     * @test
     */
    public function createParagraphForEachSingleText(): void
    {
        $xml = <<<XML
<pdf>
    <tag1>
        Some text
        <tag2></tag2>
        Some text
    </tag1>
</pdf>
XML;

        $tag1Mock       = $this->getNodeMock();
        $tag2Mock       = $this->getNodeMock();
        $paragraph1Mock = $this->getNodeMock([], Paragraph::class);
        $text1Mock      = $this->getNodeMock([], Text::class, ['setText']);
        $paragraph2Mock = $this->getNodeMock([], Paragraph::class);
        $text2Mock      = $this->getNodeMock([], Text::class, ['setText']);

        $mocks = [['tag1', $tag1Mock], ['paragraph', $paragraph1Mock], ['text', $text1Mock], ['tag2', $tag2Mock], ['paragraph', $paragraph2Mock], ['text', $text2Mock]];

        $factoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($factoryMock);

        $pageCollection = $this->parser->parse($xml);
    }

    /**
     * @test
     */
    public function wrapTwoTextSiblingsIntoTheSameParagraph(): void
    {
        $xml           = <<<XML
<pdf>
    Some text <span>Some another text</span>
</pdf>
XML;
        $paragraphMock = $this->getNodeMock([], Paragraph::class);
        $text1Mock     = $this->getNodeMock([], Text::class, ['setText']);
        $text2Mock     = $this->getNodeMock([], Text::class, ['setText']);
        $text3Mock     = $this->getNodeMock([], Text::class, ['setText'], false);

        $mocks = [['paragraph', $paragraphMock], ['text', $text1Mock], ['span', $text2Mock], ['text', $text3Mock]];

        $factoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($factoryMock);

        $pages = $this->parser->parse($xml);

        $this->assertOnlyChild($paragraphMock, $pages);
        $children = $paragraphMock->getChildren();
        $this->assertCount(2, $children);
    }

    /**
     * @test
     */
    public function deepInheritance(): void
    {
        $xml       = <<<XML
<pdf>
    <tag id="node">
        <tag extends="node" />
    </tag>
</pdf>
XML;
        $nodeMock1 = $this->getNodeMock([], Page::class, ['copy']);

        $nodeMock1->expects($this->never())
                  ->method('setAttribute');

        $nodeMock2 = $this->getNodeMock();

        $nodeMock2->expects($this->never())
                  ->method('setAttribute');

        $nodeMock1->expects($this->once())
                  ->method('copy')
                  ->willReturn($nodeMock2);

        $mocks       = [['tag', $nodeMock1]];
        $factoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($factoryMock);

        $pageCollection = $this->parser->parse($xml);
    }

    /**
     * @test
     *
     */
    public function idMustBeUnique(): void
    {
        $this->expectException(DuplicatedIdException::class);
        $xml       = <<<XML
<pdf>
    <tag1 id="node">
        <tag2 id="node" />
    </tag1>
</pdf>
XML;
        $nodeMock1 = $this->getNodeMock([], Page::class, [], false);
        $nodeMock2 = $this->getNodeMock([], Page::class, [], false);

        $mocks       = [['tag1', $nodeMock1], ['tag2', $nodeMock2]];
        $factoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($factoryMock);

        $this->parser->parse($xml);
    }

    /**
     * @test
     *
     */
    public function extendsAfterUnexistedIdIsForbidden(): void
    {
        $this->expectException(IdNotFoundException::class);
        $xml = '<pdf><tag extends="id" /></pdf>';

        $factoryMock = $this->getNodeFactoryMock();

        $this->parser->setNodeFactory($factoryMock);

        $this->parser->parse($xml);
    }

    /**
     * @test
     */
    public function childrenArentInheritedFromNode(): void
    {
        $xml = <<<XML
<pdf>
    <tag1 id="id">
        <tag2 />
    </tag1>
    <tag1 extends="id" />
</pdf>
XML;

        $nodeMock1 = $this->getNodeMock([], Page::class, ['copy']);
        $nodeMock2 = $this->getNodeMock([], Page::class, ['removeAll']);
        $nodeMock3 = $this->getNodeMock();

        $nodeMock1->expects($this->once())
                  ->method('copy')
                  ->willReturn($nodeMock2);

        $nodeMock2->expects($this->once())
                  ->method('removeAll');

        $mocks       = [['tag1', $nodeMock1], ['tag2', $nodeMock3]];
        $factoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($factoryMock);

        $this->parser->parse($xml);
    }

    /**
     * @test
     */
    public function parseAttributeSubDocument(): void
    {
        $xml    = <<<XML
<pdf>
    <tag>
        <stylesheet>
            <attribute someName1="someValue1" />
            <attribute someName2="someValue2" />
            <complex-attribute name="someName" attribute="value" />
        </stylesheet>
    </tag>
</pdf>
XML;
        $reader = new \XMLReader();
        $reader->XML($xml);
        $reader->read();
        $reader->read();

        $constraintMock = $this->createPartialMock(StylesheetConstraint::class, ['apply']);
        $constraintMock->expects($this->once())
                       ->method('apply')
                       ->with($this->isInstanceOf(Page::class));

        $parserMock = $this->createPartialMock(StylesheetParser::class, ['parse']);
        $parserMock->expects($this->once())
                   ->method('parse')
            //move after stylesheet close tag and return constraint
                   ->will($this->returnCompose([
                                                   $this->returnCallback(function () use ($reader) {
                                                       while ($reader->name != XmlDocumentParser::STYLESHEET_TAG) {
                                                           $reader->next();
                                                       }
                                                   }), $this->returnValue($constraintMock),
                                               ]));


        $nodeMock = $this->createNodeMock(Page::class, ['mergeComplexAttributes']);

        $nodeFactoryMock = $this->getNodeFactoryMock([['tag', $nodeMock]]);

        $this->parser->setStylesheetParser($parserMock);
        $this->parser->setNodeFactory($nodeFactoryMock);

        $pageCollection = $this->parser->parse($reader);
    }

    /**
     * @test
     */
    public function useStylesheetConstraintToRetrieveStylesheet(): void
    {
        $xml = <<<XML
<pdf>
    <tag1></tag1>
    <tag2>
        <tag3 class="class"></tag3>
    </tag2>
</pdf>
XML;

        $constraintMock    = $this->createPartialMock(StylesheetConstraint::class, ['find']);
        $bagContainerMock1 = $this->getBagContainerMock(['someName1' => 'someValue1']);
        $bagContainerMock2 = $this->getBagContainerMock(['someName4' => ['someAttribute1' => 'someValue1']]);
        $bagContainerMock3 = $this->getBagContainerMock(['someName2' => 'someValue2', 'someName3' => ['someAttribute2' => 'someValue2']]);

        $this->addExpectationToStylesheetConstraint(
            $constraintMock,
            [
                [['tag' => 'tag1', 'classes' => [],]],
                [['tag' => 'tag2', 'classes' => [],]],
                [
                    ['tag' => 'tag2', 'classes' => [],],
                    ['tag' => 'tag3', 'classes' => ['class']],
                ],
            ],
            [
                $bagContainerMock1,
                $bagContainerMock2,
                $bagContainerMock3,
            ]);

        $nodeMock1 = $this->getNodeMock(['someName1' => 'someValue1'], Page::class, ['mergeComplexAttributes']);
        $nodeMock2 = $this->getNodeMock([], Page::class, ['mergeComplexAttributes']);
        $nodeMock3 = $this->getNodeMock(['someName2' => 'someValue2'], Page::class, ['mergeComplexAttributes']);

        $nodeMock1->expects($this->never())
                  ->method('mergeComplexAttributes');

        $this->addComplexAttributeExpectationToNodeMock($nodeMock2, ['someName4' => ['someAttribute1' => 'someValue1']], 0);
        $this->addComplexAttributeExpectationToNodeMock($nodeMock3, ['someName3' => ['someAttribute2' => 'someValue2']], 1);

        $mocks           = [['tag1', $nodeMock1], ['tag2', $nodeMock2], ['tag3', $nodeMock3]];
        $nodeFactoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($nodeFactoryMock);

        $this->parser->parse($xml, $constraintMock);
    }

    private function getBagContainerMock(array $attributes = [], array $complexAttributes = [])
    {
        $attributes = array_merge($attributes, $complexAttributes);

        $mock = $this->createPartialMock(BagContainer::class, ['getAll']);
        $mock->expects($this->once())
             ->method('getAll')
             ->willReturn($attributes);

        return $mock;
    }

    private function addExpectationToStylesheetConstraint($constraint, $queryArgs, $returnArgs): void
    {
        $mockArgs = [];
        foreach ($queryArgs as $queryArg) {
            $mockArgs[] = [$this->equalTo($queryArg)];
        }

        $constraint->expects($this->exactly(\count($mockArgs)))
                   ->method('find')
                   ->withConsecutive(...$mockArgs)
                   ->willReturnOnConsecutiveCalls(...$returnArgs);
    }

    private function addComplexAttributeExpectationToNodeMock($node, $complexAttributes, $initSequence): void
    {
        $mockArgs = [];
        foreach ($complexAttributes as $name => $parameters) {
            $mockArgs[$initSequence++] = [$this->equalTo($name), $this->equalTo($parameters)];
        }
        $node->expects($this->exactly(\count($complexAttributes)))
             ->method('mergeComplexAttributes')
             ->withConsecutive(...$mockArgs);
    }

    /**
     * @test
     */
    public function parsePlaceholders(): void
    {
        $xml = <<<XML
<pdf>
    <tag1>
        <placeholders>
            <placeholder>
                <tag2>
                    <tag3 />
                </tag2>
            </placeholder>
        </placeholders>
    </tag1>
</pdf>
XML;

        $height           = 50;
        $placeholderMock1 = $this->createPartialMock(Container::class, ['getHeight']);
        $placeholderMock2 = $this->createPartialMock(Container::class, ['getHeight']);

        $nodeMock = $this->createPartialMock(Container::class, ['hasPlaceholder', 'setPlaceholder']);

        $nodeMock->expects($this->once())
                 ->method('hasPlaceholder')
                 ->with($this->equalTo('placeholder'))
                 ->willReturn(true);

        $nodeMock->expects($this->once())
                 ->method('setPlaceholder')
                 ->with($this->equalTo('placeholder'), $this->equalTo($placeholderMock1));

        $mocks           = [['tag1', $nodeMock], ['tag2', $placeholderMock1], ['tag3', $placeholderMock2]];
        $nodeFactoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($nodeFactoryMock);

        $this->parser->parse($xml);
    }

    /**
     * @test
     */
    public function setAttributesBeforeParent(): void
    {
        $xml = <<<XML
<pdf>
    <tag1 someAttribute="someValue"></tag1>
</pdf>
XML;

        $nodeMock = $this->createPartialMock(Container::class, ['setAttribute', 'setParent']);
        $nodeMock->expects($this->once())
                 ->method('setAttribute')
                 ->id('attribute')
                 ->with('someAttribute', 'someValue');
        $nodeMock->expects($this->once())
                 ->method('setParent')
                 ->after('attribute');


        $nodeFactoryMock = $this->getNodeFactoryMock([['tag1', $nodeMock]]);

        $this->parser->setNodeFactory($nodeFactoryMock);

        $this->parser->parse($xml);
    }

    /**
     * @test
     * @dataProvider unknownTagProvider
     *
     */
    public function throwParseExceptionOnUnknownTag($unknownTag): void
    {
        $this->expectException(ParseException::class);
        $xml = <<<XML
<pdf>
    <{$unknownTag} someAttribute="someValue"></{$unknownTag}>
</pdf>
XML;
        $this->parser->parse($xml);
    }

    public function unknownTagProvider(): array
    {
        return [
            ['some-tag'],
            ['attribute'],
            ['enhancement'],
        ];
    }

    /**
     * @test
     */
    public function readComplexAttributesInAttributeStyle(): void
    {
        $xml = <<<XML
<pdf>
	<tag someAttribute="someValue" someComplexAttribute.property="propertyValue"></tag>
</pdf>
XML;

        $nodeMock = $this->createPartialMock(Container::class, ['setAttribute', 'mergeComplexAttributes']);
        $nodeMock->expects($this->once())
                 ->method('setAttribute')
                 ->id('attribute')
                 ->with('someAttribute', 'someValue');
        $nodeMock->expects($this->once())
                 ->method('mergeComplexAttributes')
                 ->with('someComplexAttribute', ['name' => 'someComplexAttribute', 'property' => 'propertyValue']);

        $nodeFactoryMock = $this->getNodeFactoryMock([['tag', $nodeMock]]);

        $this->complexAttributeFactoryMock->expects($this->atLeastOnce())
                                          ->method('getDefinitionNames')
                                          ->willReturn(['someComplexAttribute']);

        $this->parser->setNodeFactory($nodeFactoryMock);

        $this->parser->parse($xml);
    }

    /**
     * @test
     */
    public function allowShortTagsWithAttributes(): void
    {
        $xml          = <<<XML
<pdf>
	<tag1 attribute="value" />
	<tag2></tag2>
</pdf>
XML;
        $tag1NodeMock = $this->createPartialMock(Container::class, ['setPriorityFromParent', 'setAttribute']);
        $tag2NodeMock = $this->createPartialMock(Container::class, ['setPriorityFromParent', 'setAttribute']);

        $mocks           = [['tag1', $tag1NodeMock], ['tag2', $tag2NodeMock]];
        $nodeFactoryMock = $this->getNodeFactoryMock($mocks);

        $this->parser->setNodeFactory($nodeFactoryMock);

        $pages = $this->parser->parse($xml);

        $this->assertCount(2, $pages->getChildren());
        $children = $pages->getChildren();
        $this->assertSame($tag1NodeMock, $children[0]);
        $this->assertSame($tag2NodeMock, $children[1]);
    }

    /**
     * @test
     */
    public function wrapTextIntoParagraphObject(): void
    {
        $xml           = <<<XML
<pdf>
	Some text
</pdf>
XML;
        $textNode      = $this->createPartialMock(Text::class, ['setPriorityFromParent']);
        $paragraphNode = $this->createPartialMock(Paragraph::class, ['setPriorityFromParent']);

        $nodeFactoryMock = $this->getNodeFactoryMock([['paragraph', $paragraphNode], ['text', $textNode]]);

        $this->parser->setNodeFactory($nodeFactoryMock);

        $pages = $this->parser->parse($xml);

        $children = $pages->getChildren();
        $this->assertCount(1, $children);
    }

    /**
     * @test
     */
    public function parseSignificantWhitespaces(): void
    {
        $xml = <<<XML
<pdf>
<tag1></tag1> <tag2></tag2>
</pdf>
XML;

        $textNodeTag1  = $this->createPartialMock(Text::class, ['setPriorityFromParent']);
        $textNodeSpace = $this->createPartialMock(Text::class, ['setPriorityFromParent', 'setText', 'getText']);
        $paragraphNode = $this->createPartialMock(Paragraph::class, ['setPriorityFromParent']);
        $textNodeTag2  = $this->createPartialMock(Text::class, ['setPriorityFromParent']);

        $textNodeSpace->expects($this->atLeastOnce())
                      ->method('setText')
                      ->with(' ');
        $textNodeSpace->expects($this->atLeastOnce())
                      ->method('getText')
                      ->willReturn(' ');

        $nodeFactoryMock = $this->getNodeFactoryMock([['tag1', $textNodeTag1], ['paragraph', $paragraphNode], ['text', $textNodeSpace], ['tag2', $textNodeTag2]]);

        $this->parser->setNodeFactory($nodeFactoryMock);

        $pages = $this->parser->parse($xml);
    }

    /**
     * @test
     */
    public function validInterpretationOfParagraphs(): void
    {
        $xml = <<<XML
<pdf>
	<tag1><text1></text1></tag1>
	<text2></text2>
</pdf>
XML;

        $text1Node      = $this->createPartialMock(Text::class, ['setPriorityFromParent']);
        $text2Node      = $this->createPartialMock(Text::class, ['setPriorityFromParent']);
        $tag1Node       = $this->createPartialMock(Container::class, ['setPriorityFromParent']);
        $paragraph1Node = $this->createPartialMock(Paragraph::class, ['setPriorityFromParent']);
        $paragraph2Node = $this->createPartialMock(Paragraph::class, ['setPriorityFromParent']);

        $nodeFactoryMock = $this->getNodeFactoryMock([['tag1', $tag1Node], ['text1', $text1Node], ['paragraph', $paragraph1Node], ['text2', $text2Node], ['paragraph', $paragraph2Node]]);

        $this->parser->setNodeFactory($nodeFactoryMock);

        $pages = $this->parser->parse($xml);

        $this->assertCount(2, $pages->getChildren());

        $this->assertInstanceOf(Container::class, $pages->getChild(0));
        $this->assertInstanceOf(Paragraph::class, $pages->getChild(1));
    }

    /**
     * @test
     */
    public function dontTrimLastSpaceOfTextIfNextElementAlsoIsTextNode(): void
    {
        $xml = <<<XML
<pdf>
	Some text <text1>another text</text1>
</pdf>
XML;

        $text1Node     = new Text();
        $text2Node     = new Text();
        $text3Node     = new Text();
        $paragraphNode = new Paragraph();

        $nodeFactoryMock = $this->getNodeFactoryMock([['paragraph', $paragraphNode], ['text', $text1Node], ['text1', $text2Node], ['text', $text3Node]]);

        $this->parser->setNodeFactory($nodeFactoryMock);

        $pages = $this->parser->parse($xml);

        foreach ([$text1Node, $text2Node] as $textNode) {
            $textNode->preFormat($this->documentMock);
        }

        $this->assertEquals('another text', $text2Node->getText());
        $this->assertEquals('Some text ', $text1Node->getText());
    }

    /**
     * @test
     */
    public function zeroStrindIsNotTreatedAsEmptyString(): void
    {
        $xml           = <<<XML
<pdf>0</pdf>
XML;
        $textNode      = new Text();
        $paragraphNode = new Paragraph();

        $nodeFactoryMock = $this->getNodeFactoryMock([['paragraph', $paragraphNode], ['text', $textNode]]);

        $this->parser->setNodeFactory($nodeFactoryMock);

        $pages = $this->parser->parse($xml);
        $this->assertEquals('0', $textNode->getText());
    }

    /**
     * @test
     * @dataProvider recogniceBahaviourAttributeProvider
     */
    public function recognizeBehaviourAttribute($behaviourValue): void
    {
        $xml = <<<XML
<pdf>
	<tag behaviour="$behaviourValue" />
</pdf>
XML;

        $behaviour        = $this->getMockBuilder(Behaviour::class)
                                 ->onlyMethods(['doAttach', 'attach'])
                                 ->getMock();
        $behavoiurFactory = $this->getMockBuilder(Factory::class)
                                 ->onlyMethods(['create', 'getSupportedBehaviourNames'])
                                 ->getMock();

        $behavoiurFactory->expects($this->atLeastOnce())
                         ->method('getSupportedBehaviourNames')
                         ->willReturn(['behaviour']);
        $behavoiurFactory->expects($this->once())
                         ->method('create')
                         ->with('behaviour', $behaviourValue)
                         ->willReturn($behaviour);

        $node = $this->getNodeMock([], Container::class, ['addBehaviour']);
        $node->expects($this->once())
             ->method('addBehaviour')
             ->with($behaviour);
        $nodeFactoryMock = $this->getNodeFactoryMock([['tag', $node]]);

        $this->parser->setBehaviourFactory($behavoiurFactory);
        $this->parser->setNodeFactory($nodeFactoryMock);

        $this->parser->parse($xml);
    }

    public function recogniceBahaviourAttributeProvider(): array
    {
        return [
            ['arg'],
            //utf-8 chars are valid parsed in xml attributes?
            ['ąęść'],
        ];
    }


    /**
     * @test
     */
    public function parseBehaviours(): void
    {
        $xml      = <<<XML
<pdf>
    <tag1>
        <behaviours>
            <note>some text 1</note>
            <bookmark option1="value1" option2="value2">some text 2</bookmark>
        </behaviours>
    </tag1>
</pdf>
XML;
        $nodeMock = $this->createPartialMock(Container::class, ['addBehaviour']);

        $mocks                = [['tag1', $nodeMock]];
        $nodeFactoryMock      = $this->getNodeFactoryMock($mocks);
        $behaviourFactoryMock = $this->getMockBuilder(Factory::class)
                                     ->onlyMethods(['getSupportedBehaviourNames', 'create'])
                                     ->getMock();

        $args             = ['some text 1', 'some text 2'];
        $behaviourNames   = ['note', 'bookmark'];
        $behaviourOptions = [[], ['option1' => 'value1', 'option2' => 'value2']];

        $behaviourFactoryMock->expects($this->atLeastOnce())
                             ->method('getSupportedBehaviourNames')
                             ->willReturn($behaviourNames);

        //first two invocations are getSupportedBehaviourNames method calls
        $behaviourFactoryCallIndex = 2;

        $mockCreateArgs      = [];
        $mockCreateReturns   = [];
        $mockAddBehaviourArg = [];
        foreach ($behaviourNames as $i => $behaviourName) {
            $behaviour = $this->getMockBuilder(Behaviour::class)
                              ->onlyMethods(['doAttach'])
                              ->getMock();

            $mockCreateArgs[$behaviourFactoryCallIndex]    = [$behaviourName, $args[$i], $behaviourOptions[$i]];
            $mockCreateReturns[$behaviourFactoryCallIndex] = $behaviour;

            $mockAddBehaviourArg[$i] = [$behaviour];


            $mockCreateArgs[$behaviourFactoryCallIndex]    = [$behaviourName, $args[$i], $behaviourOptions[$i]];
            $mockCreateReturns[$behaviourFactoryCallIndex] = $behaviour;

            $behaviourFactoryCallIndex++;
        }

        $behaviourFactoryMock->expects($this->exactly(\count($behaviourNames)))
                             ->method('create')
                             ->withConsecutive(...$mockCreateArgs)
                             ->willReturnOnConsecutiveCalls(...$mockCreateReturns);

        $nodeMock->expects($this->exactly(\count($behaviourNames)))
                 ->method('addBehaviour')
                 ->withConsecutive(...$mockAddBehaviourArg);

        $this->parser->setBehaviourFactory($behaviourFactoryMock);
        $this->parser->setNodeFactory($nodeFactoryMock);

        $this->parser->parse($xml);
    }

    /**
     * @test
     */
    public function parseMetadataFromDocumentRoot(): void
    {
        $xml = <<<XML
<pdf Subject="some subject" Title="some title">
</pdf>
XML;
        $this->documentMock->expects($this->exactly(2))
                           ->method('setMetadataValue')
                           ->withConsecutive(['Subject', 'some subject'], ['Title', 'some title']);

        $this->parser->parse($xml);
    }

    /**
     * @test
     *
     */
    public function throwExceptionOnParseError(): void
    {
        $this->expectException(ParseException::class);


        $xml = <<<XML
<pdf></pdfaa>    
XML;
        try {
            $this->parser->parse($xml);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @test
     */
    public function notifyParserListeners(): void
    {
        $xml   = <<<XML
<pdf>
	<tag1>
		<tag2 />
	</tag1>
</pdf>
XML;
        $node1 = $this->createPartialMock(Container::class, ['setPriorityFromParent']);
        $node2 = $this->createPartialMock(Container::class, ['setPriorityFromParent']);

        $nodeFactoryMock = $this->getNodeFactoryMock([['tag1', $node1], ['tag2', $node2]]);

        $listener = $this->createMock(DocumentParserListener::class);

        $listener->expects($this->exactly(2))
                 ->method('onStartParseNode')
                 ->withConsecutive(
                     [$this->documentMock, $this->isInstanceOf(PageCollection::class), $node1],
                     [$this->documentMock, $this->isInstanceOf(PageCollection::class), $node2]
                 );

        $listener->expects($this->exactly(2))
                 ->method('onEndParseNode')
                 ->withConsecutive(
                     [$this->documentMock, $this->isInstanceOf(PageCollection::class), $node2],
                     [$this->documentMock, $this->isInstanceOf(PageCollection::class), $node1]
                 );

        $listener->expects($this->once())
                 ->method('onEndParsing')
                 ->with($this->documentMock, $this->isInstanceOf(PageCollection::class));

        $this->parser->setNodeFactory($nodeFactoryMock);
        $this->parser->addListener($listener);

        $this->parser->parse($xml);
    }

    /**
     * @test
     */
    public function notifyParserListenersOnParagraph(): void
    {
        $xml = <<<XML
<pdf>
	Some text
</pdf>
XML;

        $node      = new Text();
        $paragraph = new Paragraph();

        $nodeFactoryMock = $this->getNodeFactoryMock([['paragraph', $paragraph], ['text', $node]]);

        $listener = $this->createMock(DocumentParserListener::class);

        $listener->expects($this->exactly(2))
                 ->method('onStartParseNode')
                 ->withConsecutive(
                     [$this->documentMock, $this->isInstanceOf(PageCollection::class), $paragraph],
                     [$this->documentMock, $this->isInstanceOf(PageCollection::class), $node]
                 );

        $listener->expects($this->exactly(2))
                 ->method('onEndParseNode')
                 ->withConsecutive(
                     [$this->documentMock, $this->isInstanceOf(PageCollection::class), $node],
                     [$this->documentMock, $this->isInstanceOf(PageCollection::class), $paragraph]
                 );

        $listener->expects($this->once())
                 ->method('onEndParsing')
                 ->with($this->documentMock, $this->isInstanceOf(PageCollection::class));

        $this->parser->setNodeFactory($nodeFactoryMock);
        $this->parser->addListener($listener);

        $this->parser->parse($xml);
    }
}
