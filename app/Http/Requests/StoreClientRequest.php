<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'prenom' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:clients,email'],
            'telephone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\s\-\+\(\)]+$/'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:100'],
            'pays' => ['required', 'string', 'max:100'],
            'code_postal' => ['nullable', 'string', 'max:10'],
            'numero_identification' => ['required', 'string', 'unique:clients,numero_identification'],
            'type_identification' => ['required', 'in:CIN,Passeport,Permis de conduire'],
            'date_naissance' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'sexe' => ['required', 'in:M,F,Autre'],
            'profession' => ['nullable', 'string', 'max:150'],
            'employeur' => ['nullable', 'string', 'max:150'],
            'revenu_mensuel' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'statut' => ['sometimes', 'in:Actif,Inactif,Suspendu,Bloqué'],
            'notes' => ['nullable', 'string'],
            'user_id' => ['nullable', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'prenom.required' => 'Le prénom est obligatoire.',
            'nom.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'numero_identification.required' => 'Le numéro d\'identification est obligatoire.',
            'numero_identification.unique' => 'Ce numéro d\'identification existe déjà.',
            'date_naissance.required' => 'La date de naissance est obligatoire.',
            'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'telephone.regex' => 'Le format du téléphone est invalide.',
            'revenu_mensuel.numeric' => 'Le revenu mensuel doit être un nombre.',
        ];
    }

    protected function prepareForValidation()
    {
        // Nettoyer les données avant validation
        if ($this->has('telephone')) {
            $this->merge([
                'telephone' => preg_replace('/[^0-9\+]/', '', $this->telephone),
            ]);
        }
    }
}