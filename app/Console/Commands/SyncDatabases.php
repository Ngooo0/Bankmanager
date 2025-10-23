<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncDatabases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:databases {--direction=local-to-online : Direction de synchronisation (local-to-online, online-to-local, bidirectional)} {--tables= : Tables spécifiques à synchroniser}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchroniser les données entre les bases de données locale et en ligne';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $direction = $this->option('direction');
        $specificTables = $this->option('tables') ? explode(',', $this->option('tables')) : null;

        $this->info("🔄 Démarrage de la synchronisation des bases de données");
        $this->info("Direction: {$direction}");

        // Tester les connexions
        $this->testConnections();

        // Tables à synchroniser
        $tables = $specificTables ?? ['clients', 'comptes', 'transactions'];

        foreach ($tables as $table) {
            $this->syncTable($table, $direction);
        }

        $this->info("✅ Synchronisation terminée!");
    }

    /**
     * Tester les connexions aux bases de données
     */
    private function testConnections()
    {
        $this->info("🔍 Test des connexions...");

        try {
            DB::connection('local')->getPdo();
            $this->info("✅ Connexion locale OK");
        } catch (\Exception $e) {
            $this->error("❌ Erreur connexion locale: " . $e->getMessage());
            return;
        }

        try {
            DB::connection('online')->getPdo();
            $this->info("✅ Connexion en ligne OK");
        } catch (\Exception $e) {
            $this->error("❌ Erreur connexion en ligne: " . $e->getMessage());
            return;
        }
    }

    /**
     * Synchroniser une table spécifique
     */
    private function syncTable($table, $direction)
    {
        $this->info("📊 Synchronisation de la table: {$table}");

        switch ($direction) {
            case 'local-to-online':
                $this->syncLocalToOnline($table);
                break;
            case 'online-to-local':
                $this->syncOnlineToLocal($table);
                break;
            case 'bidirectional':
                $this->syncBidirectional($table);
                break;
            default:
                $this->error("Direction non reconnue: {$direction}");
        }
    }

    /**
     * Synchroniser de local vers en ligne
     */
    private function syncLocalToOnline($table)
    {
        $localData = DB::connection('local')->table($table)->get();

        $this->info("📤 Synchronisation {$table}: local → en ligne (" . $localData->count() . " enregistrements)");

        foreach ($localData as $record) {
            DB::connection('online')->table($table)->updateOrInsert(
                ['id' => $record->id], // Condition de recherche
                (array) $record // Données à insérer/modifier
            );
        }

        $this->info("✅ Table {$table} synchronisée de local vers en ligne");
    }

    /**
     * Synchroniser d'en ligne vers local
     */
    private function syncOnlineToLocal($table)
    {
        $onlineData = DB::connection('online')->table($table)->get();

        $this->info("📥 Synchronisation {$table}: en ligne → local (" . $onlineData->count() . " enregistrements)");

        foreach ($onlineData as $record) {
            DB::connection('local')->table($table)->updateOrInsert(
                ['id' => $record->id],
                (array) $record
            );
        }

        $this->info("✅ Table {$table} synchronisée d'en ligne vers local");
    }

    /**
     * Synchronisation bidirectionnelle (fusion)
     */
    private function syncBidirectional($table)
    {
        $this->info("🔄 Synchronisation bidirectionnelle pour {$table}");

        // D'abord local vers en ligne
        $this->syncLocalToOnline($table);

        // Puis en ligne vers local (écrasera les conflits avec la version en ligne)
        $this->syncOnlineToLocal($table);

        $this->info("✅ Synchronisation bidirectionnelle terminée pour {$table}");
    }
}
