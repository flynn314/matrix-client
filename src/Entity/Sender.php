<?php
declare(strict_types = 1);

namespace Flynn314\Matrix\Entity;

readonly class Sender
{
    public function __construct(
        private string $userId,
        private string $username,
    ) {}

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }
}
