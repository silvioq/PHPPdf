<?php

declare(strict_types=1);

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core;

use PHPPdf\Exception\OutOfBoundsException;
use PHPPdf\Exception\BadMethodCallException;
use PHPPdf\Exception\InvalidArgumentException;
use PHPPdf\Exception\LogicException;

/**
 * Set of ordered points whom determine boundary and shape of node element.
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Boundary implements \Countable, \Iterator, \ArrayAccess
{
    private array $points              = [];
    private int   $numberOfPoints      = 0;
    private bool  $closed              = false;
    private int   $current             = 0;
    private ?int  $diagonalPointXIndex = null;
    private ?int  $diagonalPointYIndex = null;

    /**
     * Add next point to boundary
     *
     * @return Boundary Self
     */
    public function setNext($param1, $param2 = null): static
    {
        if ($this->closed) {
            throw new LogicException('Boundary has been already closed.');
        }

        $numberOfArgs = func_num_args();

        if ($numberOfArgs === 2) {
            $point = Point::getInstance($param1, $param2);
        } elseif ($param1 instanceof Point) {
            $point = $param1;
        } else {
            throw new InvalidArgumentException('Passed argument(s) should be coordinations or Point object.');
        }

        $oldNumberOfPoints                = $this->numberOfPoints;
        $this->points[$oldNumberOfPoints] = $point;
        $this->numberOfPoints++;

        $diagonalPoint = $this->getDiagonalPoint();

        if (!$diagonalPoint || $diagonalPoint->compareYCoord($point) >= 0) {
            $this->diagonalPointYIndex = $oldNumberOfPoints;
        }

        if (!$diagonalPoint || $diagonalPoint->compareXCoord($point) <= 0) {
            $this->diagonalPointXIndex = $oldNumberOfPoints;
        }

        return $this;
    }

    /**
     * Close boundary. Adding next points occurs LogicException
     */
    public function close(): void
    {
        if ($this->numberOfPoints <= 2) {
            throw new LogicException('Boundary must have at last three points.');
        }

        $this->points[$this->numberOfPoints] = $this->getFirstPoint();
        $this->numberOfPoints++;

        $this->closed = true;
    }

    /**
     * Checks if boundaries have common points
     *
     * @param Boundary $boundary
     *
     * @return boolean
     */
    public function intersects(Boundary $boundary): bool
    {
        $firstPoint    = $this->getFirstPoint();
        $diagonalPoint = $this->getDiagonalPoint();

        $compareFirstPoint    = $boundary->getFirstPoint();
        $compareDiagonalPoint = $boundary->getDiagonalPoint();

        foreach ($boundary->points as $point) {
            if ($this->contains($point)) {
                return true;
            }
        }

        foreach ($this->points as $point) {
            if ($boundary->contains($point)) {
                return true;
            }
        }

        $centerPoint = $this->getPointBetween($firstPoint, $diagonalPoint);

        if ($boundary->contains($centerPoint)) {
            return true;
        }

        $centerPoint = $this->getPointBetween($compareFirstPoint, $compareDiagonalPoint);

        if ($this->contains($centerPoint)) {
            return true;
        }

        $centerPoint = $this->getPointBetween($firstPoint, $compareDiagonalPoint);

        if ($this->contains($centerPoint) && $boundary->contains($centerPoint)) {
            return true;
        }

        $centerPoint = $this->getPointBetween($compareFirstPoint, $diagonalPoint);

        if ($this->contains($centerPoint) && $boundary->contains($centerPoint)) {
            return true;
        }

        return false;
    }

    private function contains(Point $point, $include = false): bool
    {
        $firstPoint    = $this->getFirstPoint();
        $diagonalPoint = $this->getDiagonalPoint();

        return ($firstPoint->getX() < $point->getX() && $firstPoint->getY() > $point->getY() && $diagonalPoint->getX() > $point->getX() && $diagonalPoint->getY() < $point->getY() || $include && $point);
    }

    private function getPointBetween(Point $point1, Point $point2)
    {
        $x = $point1->getX() + ($point2->getX() - $point1->getX()) / 2;
        $y = $point2->getY() + ($point1->getY() - $point2->getY()) / 2;

        return Point::getInstance($x, $y);
    }

    /**
     * @return integer Number of points in boundary
     */
    public function count(): int
    {
        return $this->numberOfPoints;
    }

    public function current(): mixed
    {
        $points = $this->getPoints();

        return $this->valid() ? $points[$this->current] : null;
    }

    /**
     * @return array Array of Point objects
     */
    public function getPoints(): array
    {
        return $this->points;
    }

    public function getPoint($i)
    {
        return $this->offsetGet($i);
    }

    public function key(): mixed
    {
        return $this->current;
    }

    public function next(): void
    {
        $this->current++;
    }

    public function rewind(): void
    {
        $this->current = 0;
    }

    public function valid(): bool
    {
        $points = $this->getPoints();

        return isset($points[$this->current]);
    }

    /**
     * Translate boundary by vector ($x, $y)
     *
     * @param integer $x First vector's coordinate
     * @param integer $y Second vector's coordinate
     */
    public function translate($x, $y): static
    {
        if (!$x && !$y) {
            return $this;
        }

        for ($i = 0; $i < $this->numberOfPoints; $i++) {
            $this->points[$i] = $this->points[$i]->translate($x, $y);
        }

        return $this;
    }

    /**
     * Translate and replace Point within boundary (@param integer $pointIndex Index of the point
     *
     * @param integer $x First vector's coordinate
     * @param integer $y Second vector's coordinate
     *
     * @see translate())
     *
     */
    public function pointTranslate($pointIndex, $x, $y)
    {
        if ($x || $y) {
            $this->points[$pointIndex] = $this->points[$pointIndex]->translate($x, $y);
        }

        return $this;
    }

    /**
     * @return Point|null First added point or null if boundary is empty
     */
    public function getFirstPoint(): ?Point
    {
        return $this->points[0] ?? null;
    }

    /**
     * @return Point|null Point diagonally to first point (@see getFirstPoint()) or null if boundary is empty
     */
    public function getDiagonalPoint(): ?Point
    {
        if ($this->diagonalPointXIndex !== null && $this->diagonalPointYIndex !== null) {
            return Point::getInstance($this->points[$this->diagonalPointXIndex]->getX(), $this->points[$this->diagonalPointYIndex]->getY());
        }

        return null;
    }

    /**
     * @return Point|null Point that divides line between first and diagonal points on half
     */
    public function getMiddlePoint(): ?Point
    {
        $diagonalPoint = $this->getDiagonalPoint();

        if ($diagonalPoint === null) {
            return null;
        }

        $x = $this->getFirstPoint()->getX() + ($diagonalPoint->getX() - $this->getFirstPoint()->getX()) / 2;
        $y = $diagonalPoint->getY() + ($this->getFirstPoint()->getY() - $diagonalPoint->getY()) / 2;

        return Point::getInstance($x, $y);
    }

    /**
     * Clears points and status of the object
     */
    public function reset(): void
    {
        $this->closed = false;
        $this->points = [];
        $this->rewind();
        $this->numberOfPoints      = 0;
        $this->diagonalPointXIndex = null;
        $this->diagonalPointYIndex = null;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function offsetExists($offset): bool
    {
        return (is_int($offset) && $offset < $this->numberOfPoints);
    }

    public function offsetGet($offset): mixed
    {
        if (!$this->offsetExists($offset)) {
            throw new OutOfBoundsException(sprintf('Point of index "%s" dosn\'t exist. Index should be in range 0-%d.', $offset, $this->numberOfPoints - 1));
        }

        return $this->points[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        throw new BadMethodCallException('You can not set point directly.');
    }

    public function offsetUnset($offset): void
    {
        throw new BadMethodCallException('You can not unset point directly.');
    }

    public function __clone()
    {
    }

    public function __serialize(): array
    {
        $points = [];
        foreach ($this->getPoints() as $point) {
            $points[] = $point->toArray();
        }

        return [
            'closed' => $this->closed,
            'points' => $points,
        ];
    }

    public function __unserialize($serialized): void
    {
        $points = $serialized['points'];

        foreach ($points as $point) {
            $this->setNext($point[0], $point[1]);
        }

        $this->closed = (bool) $serialized['closed'];
    }
}
