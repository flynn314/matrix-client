<?php
declare(strict_types = 1);

namespace Flynn314\Matrix\Entity;

readonly class Thumbnail
{
    public function __construct(
        private string $filepath,
        private string $uploadedFileUrl
    ) {}

    public function toArray(): array
    {
        $src = getimagesize($this->filepath);

        return [
            'thumbnail_info' => [
                'mimetype' => $src['mime'],
                'w' => $src[0],
                'h' => $src[1],
                'size' => filesize($this->filepath),
            ],
            'thumbnail_url' => $this->uploadedFileUrl,
        ];
    }
}
