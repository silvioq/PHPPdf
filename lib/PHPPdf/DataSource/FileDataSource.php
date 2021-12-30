<?php

declare(strict_types=1);

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\DataSource;

use PHPPdf\Exception\InvalidArgumentException;

/**
 * File data source class
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class FileDataSource extends DataSource
{
    private string $filePath;

    public function __construct(string $filePath)
    {
        if (!is_readable($filePath)) {
            throw new InvalidArgumentException(sprintf('File "%s" dosn\'t exist or is unreadable.', $filePath));
        }

        $this->filePath = $filePath;
    }

    public function read(): string
    {
        return file_get_contents($this->filePath);
    }

    public function getId(): string
    {
        return str_replace('-', '_', (string) crc32($this->filePath));
    }
}
