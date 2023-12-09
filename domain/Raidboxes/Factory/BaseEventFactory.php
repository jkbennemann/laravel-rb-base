<?php

declare(strict_types=1);

namespace Raidboxes\Domain\Raidboxes\Factory;

use Prwnr\Streamer\Contracts\Event;

class BaseEventFactory implements Event
{
    public function __construct(public \Raidboxes\Domain\Raidboxes\DTO\Event $event)
    {
    }

    /**
     * Require name method, must return a string.
     * Event name can be anything, but remember that it will be used for listening.
     */
    public function name(): string
    {
        return $this->event->name ?? 'base_project.example.event';
    }

    /**
     * Required type method, must return a string.
     * Type can be any string or one of predefined types from Event.
     */
    public function type(): string
    {
        return $this->event->type ?? Event::TYPE_EVENT;
    }

    /**
     * Required payload method, must return array
     * This array will be your message data content.
     */
    public function payload(): array
    {
        return $this->event->payload ?? ['message' => ''];
    }
}
