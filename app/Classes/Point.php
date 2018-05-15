<?php

namespace App\Classes;

class Point {
    public $x, $y;

    public function __construct($x, $y) {
        $this->x = $x;
        $this->y = $y;
    }

    public function distanceTo(Point $point) {
        $distanceX = $this->x - $point->x;
        $distanceY = $this->y - $point->y;
        $distance = sqrt($distanceX * $distanceX + $distanceY * $distanceY);
        return $distance;
    }

    public function nearestPoint(Point $point,array $points)
    {

        $curNearestPoint = $points[0];
        $curNearestDistance = $point->distanceTo($curNearestPoint);
        foreach ($points as $point) {
            $distance = $point->distanceTo($point);
            if ($distance < $curNearestDistance) {
                $curNearestDistance = $distance;
                $curNearestPoint = $point;
            }
        }

        return $curNearestPoint;

    }

    public function __toString() {
        return 'x: ' . $this->x . ', y: ' . $this->y;
    }
}