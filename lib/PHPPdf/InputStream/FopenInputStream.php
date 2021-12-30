<?php

declare(strict_types=1);

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\InputStream;

use PHPPdf\Exception\RuntimeException;

/**
 * Adapter for fopen family functions
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class FopenInputStream implements InputStream
{
    private $fp;

    public function __construct(string $filepath, $mode)
    {
        $this->fp = @\fopen($filepath, $mode);

        if ($this->fp === false) {
            throw new RuntimeException(sprintf('File "%s" can\'t be opened in mode "%s".', $filepath, $mode));
        }
    }

    public function seek($index, $seekType = self::SEEK_CUR): int
    {
        $realMode = match ($seekType) {
            self::SEEK_CUR => SEEK_CUR,
            self::SEEK_SET => SEEK_SET,
            self::SEEK_END => SEEK_END,
            default => null,
        };

        return fseek($this->fp, $index, $realMode);
    }

    public function read($length): bool|string
    {
        return fread($this->fp, $length);
    }

    public function close(): void
    {
        fclose($this->fp);
    }

    public function tell(): bool|int
    {
        return ftell($this->fp);
    }

    public function size(): int
    {
        $fileStats = fstat($this->fp);

        return $fileStats['size'];
    }
}
