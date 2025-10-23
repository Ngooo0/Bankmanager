<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comptes', function (Blueprint $table) {
            // Clé primaire UUID
            $table->uuid('id')->primary();

            // Numéro de compte (généré automatiquement)
            $table->string('numero_compte', 20)->unique();

            // Titulaire du compte (nom complet du client)
            $table->string('titulaire', 200);

            // Type de compte
            $table->enum('type', ['epargne', 'cheque'])->default('cheque');

            // Solde du compte
            $table->decimal('solde', 15, 2)->default(0);

            // Devise
            $table->string('devise', 3)->default('XOF');

            // Date de création du compte
            $table->date('date_creation');

            // Statut du compte
            $table->enum('statut', ['actif', 'bloque', 'ferme'])->default('actif');

            // Métadonnées JSON
            $table->json('metadata')->nullable();

            // Relation avec Client
            $table->foreignUuid('client_id')->constrained('clients')->onDelete('cascade');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Index pour optimiser les recherches
            $table->index('numero_compte');
            $table->index('client_id');
            $table->index('type');
            $table->index('statut');
            $table->index('date_creation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};
