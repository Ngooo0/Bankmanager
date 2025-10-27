<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompteRequest extends FormRequest
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
            'type' => ['required', 'string', Rule::in(['epargne', 'cheque'])],
            'soldeInitial' => ['required', 'numeric', 'min:10000', 'max:999999999999.99'],
            'devise' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'client' => ['required', 'array'],
            'client.id' => ['nullable', 'uuid', 'exists:clients,id'],
            'client.titulaire' => ['required_if:client.id,null', 'string', 'min:2', 'max:255'],
            'client.nci' => ['required_if:client.id,null', 'string', new \App\Rules\SenegalNciRule],
            'client.email' => ['required_if:client.id,null', 'email', 'unique:clients,email'],
            'client.telephone' => ['required_if:client.id,null', 'string', new \App\Rules\SenegalPhoneRule, 'unique:clients,telephone'],
            'client.adresse' => ['required_if:client.id,null', 'string', 'min:5', 'max:500'],
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
            'type.required' => 'Le type de compte est obligatoire.',
            'type.in' => 'Le type de compte doit être épargne ou chèque.',
            'soldeInitial.required' => 'Le solde initial est obligatoire.',
            'soldeInitial.numeric' => 'Le solde initial doit être un nombre.',
            'soldeInitial.min' => 'Le solde initial doit être d\'au moins 10 000 FCFA.',
            'soldeInitial.max' => 'Le solde initial ne peut pas dépasser 999 999 999 999.99.',
            'devise.required' => 'La devise est obligatoire.',
            'devise.size' => 'La devise doit contenir exactement 3 caractères.',
            'devise.regex' => 'La devise doit être en majuscules (ex: FCFA, USD).',
            'client.required' => 'Les informations du client sont obligatoires.',
            'client.array' => 'Les informations du client doivent être un objet.',
            'client.id.uuid' => 'L\'ID du client doit être un UUID valide.',
            'client.id.exists' => 'Le client spécifié n\'existe pas.',
            'client.titulaire.required_if' => 'Le nom du titulaire est obligatoire pour un nouveau client.',
            'client.titulaire.min' => 'Le nom du titulaire doit contenir au moins 2 caractères.',
            'client.titulaire.max' => 'Le nom du titulaire ne peut pas dépasser 255 caractères.',
            'client.nci.required_if' => 'Le numéro NCI est obligatoire pour un nouveau client.',
            'client.nci.regex' => 'Le numéro NCI doit être au format 13 chiffres suivis d\'une lettre majuscule.',
            'client.email.required_if' => 'L\'email est obligatoire pour un nouveau client.',
            'client.email.email' => 'L\'email doit être une adresse email valide.',
            'client.email.unique' => 'Cet email est déjà utilisé par un autre client.',
            'client.telephone.required_if' => 'Le numéro de téléphone est obligatoire pour un nouveau client.',
            'client.telephone.regex' => 'Le numéro de téléphone doit être au format sénégalais (+221XXXXXXXXX).',
            'client.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé par un autre client.',
            'client.adresse.required_if' => 'L\'adresse est obligatoire pour un nouveau client.',
            'client.adresse.min' => 'L\'adresse doit contenir au moins 5 caractères.',
            'client.adresse.max' => 'L\'adresse ne peut pas dépasser 500 caractères.',
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
            'type' => 'type de compte',
            'soldeInitial' => 'solde initial',
            'devise' => 'devise',
            'client.id' => 'ID du client',
            'client.titulaire' => 'nom du titulaire',
            'client.nci' => 'numéro NCI',
            'client.email' => 'email',
            'client.telephone' => 'téléphone',
            'client.adresse' => 'adresse',
        ];
    }
}