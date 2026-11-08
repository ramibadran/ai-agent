<?php

namespace App\Http\Requests\V1\Commerce;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class SalesComparisonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'current_start_date' => ['required', 'date_format:Y-m-d'],
            'current_end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:current_start_date'],
            'previous_start_date' => ['required', 'date_format:Y-m-d'],
            'previous_end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:previous_start_date'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(responseJson(Response::HTTP_PRECONDITION_FAILED, $validator->errors()->all()));
    }
}
