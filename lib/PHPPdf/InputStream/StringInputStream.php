<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\InputStream;

/**
 * Input stream that is able to read data form string
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class StringInputStream implements InputStream
{
    private ?int    $currentIndex = 0;
    private ?string $content;
    private ?int    $contentLength;

    public function __construct(?string $content)
    {
        $this->content       = $content;
        $this->contentLength = strlen($this->content);
    }

    public function seek($index, $seekType = self::SEEK_CUR): int
    {
        $newIndex = match ($seekType) {
            self::SEEK_CUR => $this->currentIndex + $index,
            self::SEEK_SET => $index,
            self::SEEK_END => $this->contentLength + $index,
            default => 0,
        };

        $this->currentIndex = $newIndex;

        return 0;
    }

    public function read($length): string
    {
        if ($this->currentIndex >= $this->contentLength) {
            return '';
        }

        $last = $this->currentIndex + $length;

        if ($last > $this->contentLength) {
            $last = $this->contentLength - $this->currentIndex;
        }

        $data = substr($this->content, $this->currentIndex, $length);
        $this->seek($length);

        return $data;
    }

    public function close(): void
    {
        $this->content = $this->contentLength = $this->currentIndex = null;
    }

    public function tell(): int
    {
        return $this->currentIndex;
    }

    public function size(): int
    {
        return $this->contentLength;
    }
}
