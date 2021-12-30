<?php

declare(strict_types=1);

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core;


use PHPPdf\Core\Exception\DrawingException;

/**
 * Encapsulate drawing task (callback + arguments + priority + order)
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class DrawingTask
{
    private       $callback;
    private array $arguments;
    private int   $priority;
    private int   $order = 0;

    public function __construct(callable $callback, array $arguments = [], $priority = Document::DRAWING_PRIORITY_FOREGROUND2)
    {
        $this->callback  = $callback;
        $this->arguments = $arguments;
        $this->priority  = $priority;
    }

    /**
     * @throws DrawingException If error occurs while drawing
     */
    public function invoke()
    {
        return call_user_func_array($this->callback, $this->arguments);
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): void
    {
        $this->order = $order;
    }

    public function compareTo(DrawingTask $task): int
    {
        $diff = ($this->priority - $task->priority);

        if ($diff === 0) {
            return ($task->order - $this->order);
        }

        return $diff;
    }
}
