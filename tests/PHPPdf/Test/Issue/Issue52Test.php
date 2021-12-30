<?php

declare(strict_types=1);


namespace PHPPdf\Test\Issue;


use PHPPdf\Core\Document;
use PHPPdf\Core\Engine\ZF\Engine;
use PHPPdf\Core\Facade;
use PHPPdf\Core\FacadeBuilder;
use PHPPdf\Core\Node\DynamicPage;
use PHPPdf\PHPUnit\Framework\TestCase;
use ZendPdf\PdfDocument;

class Issue52Test extends TestCase
{
    public function testDynamicPageAndDocumentTemplate_setPrototypeSizeFromDocumentTemplate(): void
    {
        //given

        $page = new DynamicPage();
        $page->setAttribute('document-template', $this->get200x200DocumentTemplate());

        //when

        $page->format($this->createDocument());

        //then

        $this->assertEquals(200, $page->getPrototypePage()->getWidth());
        $this->assertEquals(200, $page->getPrototypePage()->getHeight());
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testDynamicPageAndDocumentTemplate_placeholdersMissing_useDocumentTemplateNonetheless($placeholdersExists): void
    {
        //given

        $placeholders = $placeholdersExists ? '<placeholders><header><div height="10">placeholders</div></header></placeholders>' : '';

        $xml = <<<XML
<pdf>
    <dynamic-page document-template="{$this->get200x200DocumentTemplate()}">
        {$placeholders}
        some text
    </dynamic-page>
</pdf>
XML;

        //when

        $pdfContent = $this->createFacade()->render($xml);

        //then

        $document = new PdfDocument($pdfContent, null, false);

        $this->assertEquals(200, $document->pages[0]->getWidth());
        $this->assertEquals(200, $document->pages[0]->getHeight());

        if ($placeholders) {
            $this->assertStringContainsString('placeholders', $pdfContent);
        }
    }

    public function booleanProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    private function createDocument(): Document
    {
        return new Document(new Engine());
    }

    private function get200x200DocumentTemplate(): string
    {
        return __DIR__.'/../../Resources/200x200.pdf';
    }

    private function createFacade(): Facade
    {
        return FacadeBuilder::create()->build();
    }
} 
