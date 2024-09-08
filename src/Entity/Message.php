<?php
declare(strict_types = 1);

namespace Flynn314\Matrix\Entity;

class Message
{
    public const TYPE_MESSAGE = 'message';
    public const TYPE_ACTION = 'action';
    public const TYPE_EVENT = 'event';
    public const TYPE_FILE = 'file';
    public const TYPE_IMAGE = 'image';

    private readonly \DateTimeImmutable $createdAt;
    private string|null $binary = null;
    private string|null $datauri = null;
    private array $info = [];
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

    public function getBinary(): string|null
    {
        if (!$this->getDataUri()) {
            return null;
        }

        return $this->binary; // todo decode from datauri
    }

    public function getDataUri(): string|null
    {
        return $this->datauri;
    }

    public function getInfo(): array
    {
        return $this->info;
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

    public function setDataUri(string $data, string $mime): static
    {
        $this->binary = $data; // todo remove
        $this->datauri = 'data:' . $mime . ';base64,' . base64_encode($data);

        return $this;
    }

    public function setInfo(array $info): static
    {
        $this->info = $info;

        return $this;
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

    public function isImage(): bool
    {
        return static::TYPE_IMAGE === $this->type;
    }
}
