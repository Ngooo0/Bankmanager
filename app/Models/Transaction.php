<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    /**
     * Le nom de la table
     */
    protected $table = 'transactions';

    /**
     * La clé primaire
     */
    protected $primaryKey = 'id';

    /**
     * Type de la clé primaire
     */
    protected $keyType = 'string';

    /**
     * Indique si l'ID est auto-incrémenté
     */
    public $incrementing = false;

    /**
     * Les attributs qui peuvent être assignés en masse
     */
    protected $fillable = [
        'type',
        'montant',
        'devise',
        'description',
        'date_transaction',
        'statut',
        'compte_destinataire',
        'nom_destinataire',
        'frais',
        'solde_apres',
        'metadata',
        'compte_id',
    ];

    /**
     * Les attributs qui doivent être cachés
     */
    protected $hidden = [
        'metadata',
    ];

    /**
     * Les attributs qui doivent être castés
     */
    protected $casts = [
        'montant' => 'decimal:2',
        'frais' => 'decimal:2',
        'solde_apres' => 'decimal:2',
        'date_transaction' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Attributs à ajouter lors de la sérialisation
     */
    protected $appends = [
        'montant_formate',
        'type_libelle',
    ];

    /**
     * Boot du modèle
     */
    protected static function boot()
    {
        parent::boot();

        // Générer automatiquement l'UUID lors de la création
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Accesseur pour le montant formaté
     */
    public function getMontantFormateAttribute(): string
    {
        return number_format($this->montant, 2, ',', ' ') . ' ' . $this->devise;
    }

    /**
     * Accesseur pour le libellé du type
     */
    public function getTypeLibelleAttribute(): string
    {
        return match($this->type) {
            'depot' => 'Dépôt',
            'retrait' => 'Retrait',
            'virement_entrant' => 'Virement entrant',
            'virement_sortant' => 'Virement sortant',
            'frais' => 'Frais',
            'interet' => 'Intérêt',
            default => ucfirst($this->type)
        };
    }

    /**
     * Relation : Une transaction appartient à un compte
     */
    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }

    /**
     * Scopes pour les requêtes
     */
    public function scopeValidees($query)
    {
        return $query->where('statut', 'validee');
    }

    public function scopeParType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeParCompte($query, $compteId)
    {
        return $query->where('compte_id', $compteId);
    }

    public function scopeParPeriode($query, $dateDebut, $dateFin)
    {
        return $query->whereBetween('date_transaction', [$dateDebut, $dateFin]);
    }

    public function scopeDepots($query)
    {
        return $query->where('type', 'depot');
    }

    public function scopeRetraits($query)
    {
        return $query->where('type', 'retrait');
    }

    public function scopeVirements($query)
    {
        return $query->whereIn('type', ['virement_entrant', 'virement_sortant']);
    }
}
