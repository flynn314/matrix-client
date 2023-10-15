<?php
declare(strict_types = 1);

namespace Flynn314\Matrix\Entity;

readonly class RelatesTo
{
    public function __construct(
        private string $eventId
    ) {}

    public function toArray(): array
    {
        return [
            'rel_type' => 'm.thread',
            'event_id' => $this->eventId,
            'is_falling_back' => true,
            'm.in_reply_to' => [
                'event_id' => $this->eventId,
            ],
        ];
    }
}
