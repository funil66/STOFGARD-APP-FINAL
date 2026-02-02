<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WeatherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Permitir acesso público ao widget
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'city' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-ZÀ-ÿ\s\-]+$/u'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'city.required' => 'O nome da cidade é obrigatório.',
            'city.regex' => 'O nome da cidade contém caracteres inválidos.',
            'city.min' => 'O nome da cidade deve ter pelo menos 2 caracteres.',
            'city.max' => 'O nome da cidade não pode exceder 100 caracteres.',
        ];
    }

    /**
     * Prepare the data for validation.
     * Sanitiza o input antes da validação.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('city')) {
            $this->merge([
                'city' => trim($this->input('city')),
            ]);
        }
    }

    /**
     * Get the sanitized city name.
     */
    public function getSanitizedCity(): string
    {
        return \Illuminate\Support\Str::limit(
            preg_replace('/[^a-zA-ZÀ-ÿ\s\-]/u', '', $this->input('city')),
            100
        );
    }
}
