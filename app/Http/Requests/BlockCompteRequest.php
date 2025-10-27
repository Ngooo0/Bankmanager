<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlockCompteRequest extends FormRequest
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
            'motif' => ['required', 'string', 'min:5', 'max:500'],
            'duree' => ['required', 'integer', 'min:1', 'max:365'],
            'unite' => ['required', 'string', 'in:jours,mois'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motif.required' => 'Le motif de blocage est obligatoire.',
            'motif.string' => 'Le motif doit être une chaîne de caractères.',
            'motif.min' => 'Le motif doit contenir au moins 5 caractères.',
            'motif.max' => 'Le motif ne peut pas dépasser 500 caractères.',
            'duree.required' => 'La durée de blocage est obligatoire.',
            'duree.integer' => 'La durée doit être un nombre entier.',
            'duree.min' => 'La durée doit être d\'au moins 1 jour.',
            'duree.max' => 'La durée ne peut pas dépasser 365 jours.',
            'unite.required' => 'L\'unité de durée est obligatoire.',
            'unite.string' => 'L\'unité doit être une chaîne de caractères.',
            'unite.in' => 'L\'unité doit être "jours" ou "mois".',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'motif' => 'motif de blocage',
            'duree' => 'durée de blocage',
            'unite' => 'unité de durée',
        ];
    }
}