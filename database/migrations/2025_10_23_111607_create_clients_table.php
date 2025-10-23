<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            // Clé primaire UUID
            $table->uuid('id')->primary();
            
            // Informations personnelles
            $table->string('prenom', 100);
            $table->string('nom', 100);
            $table->string('email')->unique();
            $table->string('telephone', 20)->nullable();
            $table->string('adresse')->nullable();
            $table->string('ville', 100)->nullable();
            $table->string('pays', 100)->default('Sénégal');
            $table->string('code_postal', 10)->nullable();
            
            // Informations d'identification
            $table->string('numero_identification')->unique()->comment('CIN, Passeport, etc.');
            $table->enum('type_identification', ['CIN', 'Passeport', 'Permis de conduire'])->default('CIN');
            
            // Date de naissance et sexe
            $table->date('date_naissance');
            $table->enum('sexe', ['M', 'F', 'Autre'])->default('M');
            
            // Informations professionnelles
            $table->string('profession', 150)->nullable();
            $table->string('employeur', 150)->nullable();
            $table->decimal('revenu_mensuel', 15, 2)->nullable();
            
            // Statut du client
            $table->enum('statut', ['Actif', 'Inactif', 'Suspendu', 'Bloqué'])->default('Actif');
            $table->text('notes')->nullable();
            
            // Relation avec User (facultatif - si le client a un compte utilisateur)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes(); // Pour la suppression douce
            
            // Index pour optimiser les recherches
            $table->index('email');
            $table->index('telephone');
            $table->index('numero_identification');
            $table->index('statut');
            $table->index(['nom', 'prenom']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};