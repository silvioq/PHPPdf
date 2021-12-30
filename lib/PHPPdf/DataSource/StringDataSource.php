<?php

declare(strict_types=1);

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\DataSource;

/**
 * String data source class
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class StringDataSource extends DataSource
{
    private string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function read(): string
    {
        return $this->content;
    }

    public function getId(): string
    {
        return str_replace('-', '_', (string) crc32($this->content));
    }
}
