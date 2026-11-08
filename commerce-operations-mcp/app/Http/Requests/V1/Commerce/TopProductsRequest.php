<?php

namespace App\Http\Requests\V1\Commerce;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class TopProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'metric' => ['sometimes', 'in:revenue,units_sold'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:25'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(responseJson(Response::HTTP_PRECONDITION_FAILED, $validator->errors()->all()));
    }
}
