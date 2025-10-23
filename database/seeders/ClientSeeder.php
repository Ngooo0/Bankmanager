<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        // Créer des clients de test spécifiques
        Client::create([
            'prenom' => 'Amadou',
            'nom' => 'DIALLO',
            'email' => 'amadou.diallo@example.com',
            'telephone' => '+221 77 123 45 67',
            'adresse' => 'Rue 10, Liberté 6',
            'ville' => 'Dakar',
            'pays' => 'Sénégal',
            'code_postal' => '12500',
            'numero_identification' => 'SN1234567890123',
            'type_identification' => 'CIN',
            'date_naissance' => '1985-03-15',
            'sexe' => 'M',
            'profession' => 'Ingénieur Informatique',
            'employeur' => 'Sonatel',
            'revenu_mensuel' => 1250000,
            'statut' => 'Actif',
        ]);

        Client::create([
            'prenom' => 'Fatou',
            'nom' => 'NDIAYE',
            'email' => 'fatou.ndiaye@example.com',
            'telephone' => '+221 76 234 56 78',
            'adresse' => 'Sacré-Coeur 3',
            'ville' => 'Dakar',
            'pays' => 'Sénégal',
            'code_postal' => '12000',
            'numero_identification' => 'SN9876543210987',
            'type_identification' => 'CIN',
            'date_naissance' => '1990-07-22',
            'sexe' => 'F',
            'profession' => 'Comptable',
            'employeur' => 'Cabinet Seck & Associés',
            'revenu_mensuel' => 850000,
            'statut' => 'Actif',
        ]);

        Client::create([
            'prenom' => 'Moussa',
            'nom' => 'SOW',
            'email' => 'moussa.sow@example.com',
            'telephone' => '+221 70 345 67 89',
            'adresse' => 'Cité Keur Gorgui',
            'ville' => 'Dakar',
            'pays' => 'Sénégal',
            'numero_identification' => 'SN5555555555555',
            'type_identification' => 'Passeport',
            'date_naissance' => '1978-11-05',
            'sexe' => 'M',
            'profession' => 'Commerçant',
            'revenu_mensuel' => 2500000,
            'statut' => 'Actif',
        ]);

        // Créer 47 clients aléatoires avec le factory
        Client::factory()->count(47)->create();

        // Créer 10 clients avec un revenu élevé
        Client::factory()->revenuEleve()->count(10)->create();

        // Créer 5 clients suspendus
        Client::factory()->suspendu()->count(5)->create();

        // Créer 3 clients inactifs
        Client::factory()->inactif()->count(3)->create();

        $this->command->info('✓ Clients créés avec succès!');
    }
}