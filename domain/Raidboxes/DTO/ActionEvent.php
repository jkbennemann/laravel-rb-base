<?php

declare(strict_types=1);

namespace Raidboxes\Domain\Raidboxes\DTO;

use Illuminate\Support\Str;
use Prwnr\Streamer\EventDispatcher\ReceivedMessage;
use Raidboxes\Schema\BaseProject\DTO\DTO;
use Raidboxes\Schema\Schema;

class ActionEvent
{
    public function __construct(
        ReceivedMessage $message,
        public ?string $type = null,
        public ?string $action = null
    ) {
        $this->type = $message->getContent()['type'];
        $this->action = $message->get('action');
        $schema = new Schema($message->getEventName(), $this->type);
        $values = array_values($message->get('message'));

        $this->{$this->attributeName()} = (new $schema->className(...$values));
    }

    public function getDTO(): DTO
    {
        return $this->{$this->attributeName()};
    }

    private function attributeName(): string
    {
        return Str::lcfirst(Str::camel($this->type));
    }
}
