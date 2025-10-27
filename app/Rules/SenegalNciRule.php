<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SenegalNciRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Format attendu: 13 chiffres suivis d'une lettre majuscule
        // Exemple: 1234567890123A
        $pattern = '/^[0-9]{13}[A-Z]{1}$/';

        if (!preg_match($pattern, $value)) {
            $fail('Le numéro NCI doit être composé de 13 chiffres suivis d\'une lettre majuscule (ex: 1234567890123A).');
        }

        // Vérification de la validité du numéro NCI (algorithme de Luhn simplifié pour le Sénégal)
        $digits = substr($value, 0, 13);
        $checkDigit = strtoupper(substr($value, 13, 1));

        // Calcul de la somme des chiffres avec alternance
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $digit = (int)$digits[$i];
            if ($i % 2 === 0) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }

        // Calcul du chiffre de contrôle attendu
        $expectedCheckDigit = (10 - ($sum % 10)) % 10;

        // Pour le Sénégal, le chiffre de contrôle peut être représenté par une lettre
        // A=1, B=2, ..., J=0
        $letterToNumber = [
            'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5,
            'F' => 6, 'G' => 7, 'H' => 8, 'I' => 9, 'J' => 0
        ];

        if (!isset($letterToNumber[$checkDigit]) || $letterToNumber[$checkDigit] !== $expectedCheckDigit) {
            $fail('Le numéro NCI fourni n\'est pas valide selon l\'algorithme de vérification.');
        }
    }
}