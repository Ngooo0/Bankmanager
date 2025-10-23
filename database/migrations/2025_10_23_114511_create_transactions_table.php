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
        Schema::create('transactions', function (Blueprint $table) {
            // Clé primaire UUID
            $table->uuid('id')->primary();

            // Type de transaction
            $table->enum('type', ['depot', 'retrait', 'virement_entrant', 'virement_sortant', 'frais', 'interet'])->default('depot');

            // Montant de la transaction
            $table->decimal('montant', 15, 2);

            // Devise
            $table->string('devise', 3)->default('XOF');

            // Description de la transaction
            $table->text('description')->nullable();

            // Date et heure de la transaction
            $table->timestamp('date_transaction');

            // Statut de la transaction
            $table->enum('statut', ['en_attente', 'validee', 'annulee', 'echouee'])->default('validee');

            // Informations sur le compte destinataire (pour les virements)
            $table->string('compte_destinataire')->nullable();
            $table->string('nom_destinataire')->nullable();

            // Frais de transaction
            $table->decimal('frais', 10, 2)->default(0);

            // Solde après transaction
            $table->decimal('solde_apres', 15, 2);

            // Métadonnées JSON
            $table->json('metadata')->nullable();

            // Relation avec Compte
            $table->foreignUuid('compte_id')->constrained('comptes')->onDelete('cascade');

            // Timestamps
            $table->timestamps();

            // Index pour optimiser les recherches
            $table->index('compte_id');
            $table->index('type');
            $table->index('statut');
            $table->index('date_transaction');
            $table->index(['compte_id', 'date_transaction']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
