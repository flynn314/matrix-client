<?php
declare(strict_types = 1);

namespace Flynn314\Matrix\Entity;

readonly class Message
{
    public function __construct(
        private Sender $sender,
        private string $body,
        private \DateTimeImmutable $createdAt,
    ) {}

    public function getBody(): string
    {
        return $this->body;
    }

    public function getSender(): Sender
    {
        return $this->sender;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
