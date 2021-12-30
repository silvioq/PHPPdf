<?php

declare(strict_types=1);


namespace PHPPdf\Test\Issue;

use PHPPdf\Core\Configuration\LoaderImpl;
use PHPPdf\Core\Facade;
use PHPPdf\Core\FacadeBuilder;
use PHPPdf\PHPUnit\Framework\TestCase;

//https://github.com/psliwa/PHPPdf/issues/77
class Issue77Test extends TestCase
{
    private Facade $facade;

    protected function setUp(): void
    {
        $loader = new LoaderImpl();
        $builder = FacadeBuilder::create($loader);
        $this->facade = $builder->build();
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testFooterRender($useTemplate): void
    {
        $this->renderDocumentWithFooter('Document 1', $useTemplate);
        $content = $this->renderDocumentWithFooter('Document 2', $useTemplate);

        $this->assertStringContainsString('Document 2', $content);
        $this->assertStringNotContainsString('Document 1', $content);
    }

    public function booleanProvider(): array
    {
        return array(
            array(true),
            array(false),
        );
    }

    private function renderDocumentWithFooter($footer, $useTemplate): string
    {
        $template = $useTemplate ? 'document-template="'.__DIR__.'/../../Resources/test.pdf"' : '';
        return $this->facade->render('<pdf>
    <dynamic-page encoding="UTF-8" '.$template.'>
                <placeholders>
                    <header>
                        <div height="50px" width="100%" color="green">
                            Some header
                        </div>
                    </header>
                    <footer>
                        <div height="50px" width="100%" color="green">
                             '.$footer.'
                        </div>
                    </footer>
                </placeholders>

                <p>Lorum ipsum</p>

            </dynamic-page>
        </pdf>',

            '<stylesheet></stylesheet>'
        );
    }
}
