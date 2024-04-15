<?php
declare(strict_types = 1);

namespace Flynn314\Matrix\Entity;

class Message
{
    public const TYPE_MESSAGE = 'message';
    public const TYPE_ACTION = 'action';
    public const TYPE_EVENT = 'event';
    public const TYPE_FILE = 'file';

    private readonly \DateTimeImmutable $createdAt;
    private string $summary = '';
    private string|null $extraData = null;

    public function __construct(
        readonly private Sender $sender,
        readonly private string $body,
        \DateTimeInterface $createdAt,
        readonly private string $type = self::TYPE_MESSAGE,
    ) {
        $this->createdAt = \DateTimeImmutable::createFromInterface($createdAt);
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function getSender(): Sender
    {
        return $this->sender;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExtraData(): string|null
    {
        return $this->extraData;
    }

    public function setSummary(string $summary): static
    {
        $this->summary = $summary;

        return $this;
    }

    public function setExtraData(string|null $data): static
    {
        $this->extraData = $data;

        return $this;
    }

    public function isAction(): bool
    {
        return static::TYPE_ACTION === $this->type;
    }

    public function isEvent(): bool
    {
        return static::TYPE_EVENT === $this->type;
    }

    public function isFile(): bool
    {
        return static::TYPE_FILE === $this->type;
    }
}
