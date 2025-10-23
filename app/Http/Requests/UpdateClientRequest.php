<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clientId = $this->route('client'); // UUID du client

        return [
            'prenom' => ['sometimes', 'string', 'max:100'],
            'nom' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', Rule::unique('clients')->ignore($clientId)],
            'telephone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\s\-\+\(\)]+$/'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:100'],
            'pays' => ['sometimes', 'string', 'max:100'],
            'code_postal' => ['nullable', 'string', 'max:10'],
            'numero_identification' => [
                'sometimes',
                'string',
                Rule::unique('clients')->ignore($clientId)
            ],
            'type_identification' => ['sometimes', 'in:CIN,Passeport,Permis de conduire'],
            'date_naissance' => ['sometimes', 'date', 'before:today', 'after:1900-01-01'],
            'sexe' => ['sometimes', 'in:M,F,Autre'],
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
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'numero_identification.unique' => 'Ce numéro d\'identification existe déjà.',
            'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'telephone.regex' => 'Le format du téléphone est invalide.',
            'revenu_mensuel.numeric' => 'Le revenu mensuel doit être un nombre.',
        ];
    }
}