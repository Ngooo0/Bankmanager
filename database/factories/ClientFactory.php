<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        $sexe = $this->faker->randomElement(['M', 'F']);
        
        return [
            'id' => (string) Str::uuid(),
            'prenom' => $sexe === 'M' ? $this->faker->firstNameMale() : $this->faker->firstNameFemale(),
            'nom' => strtoupper($this->faker->lastName()),
            'email' => $this->faker->unique()->safeEmail(),
            'telephone' => $this->faker->numerify('+221 ## ### ## ##'),
            'adresse' => $this->faker->streetAddress(),
            'ville' => $this->faker->randomElement(['Dakar', 'Thiès', 'Saint-Louis', 'Kaolack', 'Ziguinchor', 'Diourbel']),
            'pays' => 'Sénégal',
            'code_postal' => $this->faker->numerify('#####'),
            'numero_identification' => $this->faker->unique()->numerify('SN###########'),
            'type_identification' => $this->faker->randomElement(['CIN', 'Passeport', 'Permis de conduire']),
            'date_naissance' => $this->faker->dateTimeBetween('-65 years', '-18 years'),
            'sexe' => $sexe,
            'profession' => $this->faker->jobTitle(),
            'employeur' => $this->faker->company(),
            'revenu_mensuel' => $this->faker->numberBetween(100000, 5000000),
            'statut' => $this->faker->randomElement(['Actif', 'Actif', 'Actif', 'Inactif']), // Plus de chances d'être actif
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indiquer que le client est actif
     */
    public function actif(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'Actif',
        ]);
    }

    /**
     * Indiquer que le client est inactif
     */
    public function inactif(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'Inactif',
        ]);
    }

    /**
     * Indiquer que le client est suspendu
     */
    public function suspendu(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'Suspendu',
        ]);
    }

    /**
     * Client avec un revenu élevé
     */
    public function revenuEleve(): static
    {
        return $this->state(fn (array $attributes) => [
            'revenu_mensuel' => $this->faker->numberBetween(5000000, 20000000),
            'profession' => $this->faker->randomElement(['Directeur', 'Médecin', 'Avocat', 'Entrepreneur']),
        ]);
    }
}