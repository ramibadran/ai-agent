<?php

namespace App\Http\Requests\V1\Admin;

use App\Models\KnowledgeContent;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class UpdateKnowledgeContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $knowledgeContent = $this->route('knowledgeContent');

        return $knowledgeContent instanceof KnowledgeContent
            && ($this->user()?->can('update', $knowledgeContent) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'body' => ['sometimes', 'required', 'string', 'max:100000'],
            'source_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'metadata.category' => ['nullable', 'string', 'max:100'],
            'metadata.tags' => ['nullable', 'array', 'max:20'],
            'metadata.tags.*' => ['string', 'max:50'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(responseJson(Response::HTTP_PRECONDITION_FAILED, $validator->errors()->all()));
    }
}
