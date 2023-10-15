<?php
declare(strict_types = 1);

namespace Flynn314\Matrix\Entity;

readonly class VideoData
{
    public function __construct(
        private int $width,
        private int $height,
        private int $duration
    ) {}

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }
}
