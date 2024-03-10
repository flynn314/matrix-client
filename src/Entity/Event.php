<?php
declare(strict_types = 1);

namespace Flynn314\Matrix\Entity;

readonly class Event
{
    public function __construct(private array $data) {}

    public function getTypeName(): string
    {
        return $this->data['type'];
    }

    public function getSenderUsername(): string
    {
        return $this->data['sender'];
    }

    public function getContent(): string
    {
        return $this->data['content'];
    }
}
