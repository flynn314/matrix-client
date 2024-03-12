<?php
declare(strict_types = 1);

namespace Flynn314\Matrix\Entity;

readonly class Message
{
    public const TYPE_MESSAGE = 'message';
    public const TYPE_ACTION = 'action';

    private \DateTimeImmutable $createdAt;

    public function __construct(
        private Sender $sender,
        private string $body,
        \DateTimeInterface $createdAt,
        private string $type = self::TYPE_MESSAGE,
    ) {
        $this->createdAt = \DateTimeImmutable::createFromInterface($createdAt);
    }

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

    public function isAction(): bool
    {
        return static::TYPE_ACTION === $this->type;
    }
}
