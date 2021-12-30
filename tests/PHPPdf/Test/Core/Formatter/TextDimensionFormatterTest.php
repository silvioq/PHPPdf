<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Engine\Font;

use PHPPdf\Core\Node\Text;
use PHPPdf\Core\Document;
use PHPPdf\Core\Node\Node;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Formatter\TextDimensionFormatter;
use PHPPdf\PHPUnit\Framework\TestCase;

class TextDimensionFormatterTest extends TestCase
{
    const TEXT_WIDTH = 100;
    const FONT_SIZE  = 12;

    private TextDimensionFormatter $formatter;

    public function setUp(): void
    {
        $this->formatter = new TextDimensionFormatter();
        $this->document  = $this->createDocumentStub();
    }

    /**
     * For simplification in this test each character width = 1
     *
     * @test
     * @dataProvider textProvider
     */
    public function textParentHasWidth_calculateWordsSize($text, $expectedWords, $expectedSizes): void
    {
        $textMock = $this->createText($text, self::TEXT_WIDTH);

        $this->formatter->format($textMock, $this->document);

//        $this->verifyMockObjects();

        $this->assertEquals($expectedWords, $textMock->getWords());
        $this->assertEquals($expectedSizes, $textMock->getWordsSizes());
    }

    /**
     * @test
     * @dataProvider textProvider
     */
    public function textParentHaxMaxWidth_calculateWordsSize($text, $expectedWords, $expectedSizes): void
    {
        $textMock = $this->createText($text, null, self::TEXT_WIDTH);

        $this->formatter->format($textMock, $this->document);

//        $this->verifyMockObjects();

        $this->assertEquals($expectedWords, $textMock->getWords());
        $this->assertEquals($expectedSizes, $textMock->getWordsSizes());
    }

    private function createText($text, $parentWidth = null, $parentMaxWidth = null): TextDimensionFormatterTest_Text
    {
        $page      = new Page();
        $container = new Container([
                                       'width'     => $parentWidth,
                                       'max-width' => $parentMaxWidth,
                                   ]);

        $textNode = new TextDimensionFormatterTest_Text($text, new TextDimensionFormatterTest_Font());
        $textNode->setFontSize(self::FONT_SIZE);

        $container->add($textNode);
        $page->add($container);

        return $textNode;
    }

    public function textProvider(): array
    {
        return [
            [
                'some text with some words',
                ['some ', 'text ', 'with ', 'some ', 'words'],
                [5, 5, 5, 5, 5],
            ],
            [
                'some text with some words ',
                ['some ', 'text ', 'with ', 'some ', 'words ', ''],
                [5, 5, 5, 5, 6, 0],
            ],
            //very long word, split it!
            [
                str_repeat('a', self::TEXT_WIDTH + 5),
                [str_repeat('a', self::TEXT_WIDTH), 'aaaaa'],
                [self::TEXT_WIDTH, 5],
            ],
            //very very long word, split it to 3 words!
            [
                str_repeat('a', self::TEXT_WIDTH * 2 + 5),
                [str_repeat('a', self::TEXT_WIDTH), str_repeat('a', self::TEXT_WIDTH), 'aaaaa'],
                [self::TEXT_WIDTH, self::TEXT_WIDTH, 5],
            ],
            //very long word - exacly 2 * maxPossibleWidth
            [
                str_repeat('a', self::TEXT_WIDTH * 2),
                [str_repeat('a', self::TEXT_WIDTH), str_repeat('a', self::TEXT_WIDTH)],
                [self::TEXT_WIDTH, self::TEXT_WIDTH],
            ],
        ];
    }
}

class TextDimensionFormatterTest_Text extends Text
{
    private Font $font;

    public function __construct($text, Font $font)
    {
        parent::__construct($text);
        $this->font = $font;
    }

    public function getFont(Document $document): Font
    {
        return $this->font;
    }
}

class TextDimensionFormatterTest_Font implements Font
{
    public function hasStyle($style)
    {
        throw new \BadMethodCallException();
    }

    public function setStyle($style)
    {
        throw new \BadMethodCallException();
    }

    public function getCurrentStyle()
    {
        throw new \BadMethodCallException();
    }

    public function getCurrentResourceIdentifier()
    {
        throw new \BadMethodCallException();
    }

    public function getWidthOfText($text, $fontSize)
    {
        return strlen($text);
    }
}
