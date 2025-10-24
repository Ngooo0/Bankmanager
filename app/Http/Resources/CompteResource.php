<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_compte' => $this->numero_compte,
            'titulaire' => $this->titulaire,
            'type' => $this->type,
            'solde' => $this->solde,
            'solde_formate' => $this->solde_formate,
            'devise' => $this->devise,
            'date_creation' => $this->date_creation,
            'statut' => $this->statut,
            'metadata' => $this->metadata,
            'client' => $this->whenLoaded('client'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}