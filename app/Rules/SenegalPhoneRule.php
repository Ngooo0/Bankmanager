<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SenegalPhoneRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Format attendu: +221XXXXXXXXX où X est un chiffre
        // Les numéros sénégalais commencent par +221 suivi de 9 chiffres
        // Les opérateurs valides commencent par 70, 76, 77, 78
        $pattern = '/^\+221[70|76|77|78][0-9]{7}$/';

        if (!preg_match($pattern, $value)) {
            $fail('Le numéro de téléphone doit être un numéro sénégalais valide au format +221XXXXXXXXX (où XXXXXXXXX représente 9 chiffres commençant par 70, 76, 77 ou 78).');
        }
    }
}