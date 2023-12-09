<?php

declare(strict_types=1);

namespace Raidboxes\Domain\Raidboxes\DTO;

class Event
{
    public function __construct(
        public string $name = 'default.streamer.event',
        public array $payload = [],
        public string $type = \Prwnr\Streamer\Contracts\Event::TYPE_EVENT
    ) {
    }
}
