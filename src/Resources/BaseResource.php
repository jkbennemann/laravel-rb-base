<?php

declare(strict_types=1);

namespace Raidboxes\RbBase\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BaseResource extends JsonResource
{
    protected function addMessageContext($message): array
    {
        if (is_array($message)) {
            return array_map(function ($key, $item) {
                $string = is_array($item) ? $item[0] : $item;
                if (preg_match("/^(.*?)\|default:(.*?)$/", $string, $matches)) {
                    $error[$key]['i18n'] = $matches[1];
                    $error[$key]['message'] = $matches[2];
                } else {
                    $error[$key]['message'] = $string;
                }

                return $error;
            }, array_keys($message), $message);
        }

        if (preg_match("/^(.*?)\|default:(.*?)$/", $message, $matches)) {
            $error['i18n'] = $matches[1];
            $error['message'] = $matches[2];

            return $error;
        }

        return ['message' => $message];
    }
}
