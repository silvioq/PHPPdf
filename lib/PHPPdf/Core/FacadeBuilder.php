<?php

declare(strict_types=1);

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core;

use PHPPdf\Util\AbstractStringFilterContainer;
use PHPPdf\Util\StringFilter;
use PHPPdf\Util\ResourcePathStringFilter;
use PHPPdf\Exception\InvalidArgumentException;
use PHPPdf\Core\Engine\EngineFactoryImpl;
use PHPPdf\Core\Engine\EngineFactory;
use PHPPdf\Core\Engine\ZF\Engine;
use PHPPdf\Core\Parser\XmlDocumentParser;
use PHPPdf\Core\Parser\MarkdownDocumentParser;
use PHPPdf\Core\Parser\StylesheetParser;
use PHPPdf\Core\Configuration\LoaderImpl;
use PHPPdf\Core\Configuration\Loader;
use PHPPdf\Cache\CacheImpl;

/**
 * Facade builder.
 *
 * Object of this class is able to configure and build specyfic Facade object.
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class FacadeBuilder extends AbstractStringFilterContainer
{
    public const PARSER_XML      = 'xml';
    public const PARSER_MARKDOWN = 'markdown';

    private Loader  $configurationLoader;
    private ?string $cacheType                        = null;
    private array   $cacheOptions                     = [];
    private bool    $useCacheForStylesheetConstraint  = true;
    private bool    $useCacheForConfigurationLoader   = true;
    private string  $documentParserType               = self::PARSER_XML;
    private ?string $markdownStylesheetFilepath       = null;
    private ?string $markdownDocumentTemplateFilepath = null;

    private EngineFactory $engineFactory;
    private string        $engineType    = EngineFactoryImpl::TYPE_PDF;
    private array         $engineOptions = [];

    private function __construct(Loader $configurationLoader = null, EngineFactory $engineFactory = null)
    {
        $this->stringFilters[] = new ResourcePathStringFilter();

        if ($configurationLoader === null) {
            $configurationLoader = new LoaderImpl();
        }

        if ($engineFactory === null) {
            $engineFactory = new EngineFactoryImpl();
        }

        $this->engineFactory = $engineFactory;
        $this->setConfigurationLoader($configurationLoader);
    }

    /**
     * @return FacadeBuilder
     */
    public function addStringFilter(StringFilter $filter): static
    {
        parent::addStringFilter($filter);

        return $this;
    }

    /**
     * @return FacadeBuilder
     */
    public function setStringFilters(array $filters): static
    {
        parent::setStringFilters($filters);

        return $this;
    }

    /**
     * Static constructor
     *
     * @return FacadeBuilder
     */
    public static function create(Loader $configuration = null, EngineFactory $engineFactory = null): FacadeBuilder
    {
        return new self($configuration, $engineFactory);
    }

    public function setConfigurationLoader(Loader $configurationLoader): static
    {
        $this->configurationLoader = $configurationLoader;

        return $this;
    }

    public function setUseCacheForConfigurationLoader(bool $flag): static
    {
        $this->useCacheForConfigurationLoader = $flag;

        return $this;
    }

    /**
     * Create Facade object
     *
     * @return Facade
     */
    public function build(): Facade
    {
        $documentParser   = $this->createDocumentParser();
        $stylesheetParser = new StylesheetParser();
        $stylesheetParser->setComplexAttributeFactory($this->configurationLoader->createComplexAttributeFactory());

        $engine = $this->engineFactory->createEngine($this->engineType, $this->engineOptions);

        $document = new Document($engine);
        $this->addStringFiltersTo($document);

        $facade = new Facade($this->configurationLoader, $document, $documentParser, $stylesheetParser);
        $this->addStringFiltersTo($facade);
        $facade->setEngineType($this->engineType);

        if ($documentParser instanceof FacadeAware) {
            $documentParser->setFacade($facade);
        }

        $facade->setUseCacheForStylesheetConstraint($this->useCacheForStylesheetConstraint);

        if ($this->cacheType && $this->cacheType !== 'Null') {
            $cache = new CacheImpl($this->cacheType, $this->cacheOptions);
            $facade->setCache($cache);

            if ($this->useCacheForConfigurationLoader) {
                $this->configurationLoader->setCache($cache);
            }
        }

        return $facade;
    }

    private function addStringFiltersTo($obj): void
    {
        $obj->setStringFilters($this->stringFilters);
    }

    private function createDocumentParser(): MarkdownDocumentParser|XmlDocumentParser
    {
        $parser = new XmlDocumentParser($this->configurationLoader->createComplexAttributeFactory());

        if ($this->documentParserType === self::PARSER_MARKDOWN) {
            $parser = new MarkdownDocumentParser($parser);
            $parser->setStylesheetFilepath($this->markdownStylesheetFilepath);
            $parser->setDocumentTemplateFilepath($this->markdownDocumentTemplateFilepath);
        }

        return $parser;
    }

    /**
     * Set cache type and options for facade
     *
     * @param string $type    Type of cache, see {@link \PHPPdf\Cache\CacheImpl} engine constants
     * @param array  $options Options for cache
     *
     * @return FacadeBuilder
     */
    public function setCache($type, array $options = []): static
    {
        $this->cacheType    = $type;
        $this->cacheOptions = $options;

        return $this;
    }

    /**
     * Switch on/off cache for stylesheet.
     *
     * If you switch on cache for stylesheet constraints,
     * you should set cache parameters by method setCache(), otherwise NullCache as default will
     * be used.
     *
     * @param boolean $useCache Cache for Stylesheets should by used?
     *
     * @return FacadeBuilder
     * @see setCache()
     */
    public function setUseCacheForStylesheetConstraint(bool $useCache): static
    {
        $this->useCacheForStylesheetConstraint = $useCache;

        return $this;
    }

    /**
     * @return FacadeBuilder
     */
    public function setDocumentParserType($type): static
    {
        $parserTypes = [self::PARSER_XML, self::PARSER_MARKDOWN];
        if (!in_array($type, $parserTypes, true)) {
            throw new InvalidArgumentException(sprintf('Unknown parser type "%s", expected one of: %s.', $type, implode(', ', $parserTypes)));
        }

        $this->documentParserType = $type;

        return $this;
    }

    /**
     * Sets stylesheet filepath for markdown document parser
     *
     * @param string|null $filepath Filepath
     *
     * @return FacadeBuilder
     */
    public function setMarkdownStylesheetFilepath(?string $filepath): static
    {
        $this->markdownStylesheetFilepath = $filepath;

        return $this;
    }

    /**
     * Sets document template for markdown document parser
     *
     * @param string|null $filepath Filepath to document template
     *
     * @return FacadeBuilder
     */
    public function setMarkdownDocumentTemplateFilepath(?string $filepath): static
    {
        $this->markdownDocumentTemplateFilepath = $filepath;

        return $this;
    }

    /**
     * @return FacadeBuilder
     */
    public function setEngineType($type): static
    {
        $this->engineType = $type;

        return $this;
    }

    /**
     * @return FacadeBuilder
     */
    public function setEngineOptions(array $options): static
    {
        $this->engineOptions = $options;

        return $this;
    }
}
