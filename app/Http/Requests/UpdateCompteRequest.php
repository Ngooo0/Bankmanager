<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompteRequest extends FormRequest
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
            'titulaire' => ['sometimes', 'string', 'min:2', 'max:255'],
            'informationsClient' => ['sometimes', 'array'],
            'informationsClient.telephone' => ['sometimes', 'string', new \App\Rules\SenegalPhoneRule, 'unique:clients,telephone,' . $this->route('compteId')],
            'informationsClient.email' => ['sometimes', 'email', 'unique:clients,email,' . $this->route('compteId')],
            'informationsClient.password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'informationsClient.nci' => ['sometimes', 'string', new \App\Rules\SenegalNciRule],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Vérifier qu'au moins un champ est fourni
            $hasTitulaire = $this->has('titulaire') && !empty($this->titulaire);
            $hasClientInfo = $this->has('informationsClient') &&
                           collect($this->informationsClient)->filter()->isNotEmpty();

            if (!$hasTitulaire && !$hasClientInfo) {
                $validator->errors()->add('general', 'Au moins un champ de modification doit être fourni.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'titulaire.sometimes' => 'Le titulaire peut être fourni pour modification.',
            'titulaire.string' => 'Le nom du titulaire doit être une chaîne de caractères.',
            'titulaire.min' => 'Le nom du titulaire doit contenir au moins 2 caractères.',
            'titulaire.max' => 'Le nom du titulaire ne peut pas dépasser 255 caractères.',
            'informationsClient.array' => 'Les informations client doivent être un objet.',
            'informationsClient.telephone.sometimes' => 'Le téléphone peut être fourni pour modification.',
            'informationsClient.telephone.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'informationsClient.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé par un autre client.',
            'informationsClient.email.sometimes' => 'L\'email peut être fourni pour modification.',
            'informationsClient.email.email' => 'L\'email doit être une adresse email valide.',
            'informationsClient.email.unique' => 'Cet email est déjà utilisé par un autre client.',
            'informationsClient.password.sometimes' => 'Le mot de passe peut être fourni pour modification.',
            'informationsClient.password.string' => 'Le mot de passe doit être une chaîne de caractères.',
            'informationsClient.password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'informationsClient.password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'informationsClient.nci.sometimes' => 'Le numéro NCI peut être fourni pour modification.',
            'informationsClient.nci.string' => 'Le numéro NCI doit être une chaîne de caractères.',
            'general' => 'Au moins un champ de modification doit être fourni.',
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
            'titulaire' => 'nom du titulaire',
            'informationsClient.telephone' => 'téléphone',
            'informationsClient.email' => 'email',
            'informationsClient.password' => 'mot de passe',
            'informationsClient.nci' => 'numéro NCI',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Nettoyer les champs vides
        if ($this->has('informationsClient')) {
            $clientInfo = collect($this->informationsClient)->filter(function ($value) {
                return $value !== null && $value !== '';
            })->toArray();

            $this->merge([
                'informationsClient' => $clientInfo
            ]);
        }
    }
}