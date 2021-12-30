<?php

declare(strict_types=1);

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\InputStream;

/**
 * Input stream interface
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface InputStream
{
    public const SEEK_CUR = 1;
    public const SEEK_SET = 2;
    public const SEEK_END = 3;

    public function read($length): bool|string;

    public function close(): void;

    public function seek($index, $seekType = self::SEEK_CUR): int;

    public function tell(): bool|int;

    public function size(): int;
}
