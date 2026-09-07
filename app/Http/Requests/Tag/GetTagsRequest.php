<?php

declare(strict_types=1);

namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;
use Src\Tag\Application\Usecase\Query\GetTags\GetTagsInput;

final class GetTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'tag_name' => ['nullable', 'string'],
        ];
    }

    public function toInput(): GetTagsInput
    {
        return new GetTagsInput(
            tagName: $this->validated('tag_name'),
        );
    }
}
