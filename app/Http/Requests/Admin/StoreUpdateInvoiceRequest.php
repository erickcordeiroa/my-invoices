<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class StoreUpdateInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:income,expense'],
            'amount' => ['required', 'integer', 'min:1'],
            'due_at' => ['required', 'date'],
            'repeat_when' => ['nullable', 'string', 'in:monthly'],
            'period' => ['nullable', 'string', 'in:monthly'],
            'enrollments' => ['nullable', 'integer', 'min:1', 'max:120'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Dados inválidos',
            ], Response::HTTP_BAD_REQUEST)
        );
    }
}

