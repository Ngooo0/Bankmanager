<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource pour les comptes archivés
 */
class ArchivedCompteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'numeroCompte' => $this->resource['numeroCompte'],
            'titulaire' => $this->resource['titulaire'],
            'type' => $this->resource['type'],
            'solde' => $this->resource['solde'],
            'solde_formate' => number_format($this->resource['solde'], 0, ',', ' ') . ' ' . $this->resource['devise'],
            'devise' => $this->resource['devise'],
            'dateCreation' => $this->resource['dateCreation'],
            'statut' => $this->resource['statut'],
            'metadata' => [
                'derniereModification' => $this->resource['metadata']['derniereModification'] ?? null,
                'version' => $this->resource['metadata']['version'] ?? 1,
                'dateArchivage' => $this->resource['metadata']['dateArchivage'] ?? null,
            ],
            'created_at' => $this->resource['dateCreation'],
            'updated_at' => $this->resource['metadata']['derniereModification'] ?? $this->resource['dateCreation'],
        ];
    }
}