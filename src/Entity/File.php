<?php
declare(strict_types = 1);

namespace Flynn314\Matrix\Entity;

readonly class File
{
    public function __construct(
        private string $uri,
        private string $fileName,
        private int $fileSize,
        private int $width,
        private int $height,
        private int $duration,
        private string $mime,
        private ?string $blurHash = null,
    ) {}

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

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

    public function getMime(): string
    {
        return $this->mime;
    }

    public function getBlurHash(): ?string
    {
        return $this->blurHash;
    }

    public static function createThumb(string $uri, int $width, int $height, int $fileSize, string $mime): static
    {
        return new static(
            uri: $uri,
            fileName: '',
            fileSize: $fileSize,
            width: $width,
            height: $height,
            duration: 0,
            mime: $mime,
        );
    }
}
