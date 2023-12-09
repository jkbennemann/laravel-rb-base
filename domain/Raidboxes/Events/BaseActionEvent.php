<?php

declare(strict_types=1);

namespace Raidboxes\Domain\Raidboxes\Events;

use Raidboxes\Domain\Raidboxes\DTO\Event;
use Raidboxes\Schema\BaseProject\DTO\DTO;

class BaseActionEvent extends Event
{
    public function actionPayload(string $action, DTO $dto): array
    {
        return [
            'action' => $action,
            'message' => $dto->toArray(),
        ];
    }
}
