<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class PaginatedRequest extends FormRequest
{
    protected const int DEFAULT_PAGE = 1;

    protected const int DEFAULT_NUMBER_OF_ITEMS_PER_PAGE = 40;

    protected const int MAX_NUMBER_OF_ITEMS_PER_PAGE = 120;

    /**
     * @return array<string, list<string|int>>
     */
    protected function paginationRules(): array
    {
        return [
            'page' => ['sometimes', 'required', 'integer', 'min:1'],
            'number_of_items_per_page' => ['sometimes', 'required', 'integer', 'min:1', 'max:'.self::MAX_NUMBER_OF_ITEMS_PER_PAGE],
        ];
    }

    protected function page(): int
    {
        return $this->integer('page', self::DEFAULT_PAGE);
    }

    protected function numberOfItemsPerPage(): int
    {
        return $this->integer('number_of_items_per_page', self::DEFAULT_NUMBER_OF_ITEMS_PER_PAGE);
    }
}
