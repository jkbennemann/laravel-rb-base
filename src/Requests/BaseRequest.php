<?php

declare(strict_types=1);

namespace Raidboxes\RbBase\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class BaseRequest extends FormRequest
{
    protected array $defaults = [];

    public function authorize(): bool
    {
        return false;
    }

    public function rules(): array
    {
        return [];
    }

    public function validationData(): array
    {
        $data = parent::validationData();

        foreach ($this->defaults as $field => $value) {
            if (!isset($data[$field])) {
                $data[$field] = $value;
            }
        }

        return $data;
    }

    public function camelizeValidatedData(): array
    {
        $validated = parent::validated();

        return $this->camelizeArrayKeys($validated);
    }

    private function camelizeArrayKeys(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = $this->camelizeArrayKeys($value);
            }

            $result[Str::camel($key)] = $value;
        }

        return $result;
    }
}
