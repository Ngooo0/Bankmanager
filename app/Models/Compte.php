<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compte extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * Le nom de la table
     */
    protected $table = 'comptes';

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
        'numero_compte',
        'titulaire',
        'type',
        'solde',
        'devise',
        'date_creation',
        'statut',
        'metadata',
        'client_id',
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
        'solde' => 'decimal:2',
        'date_creation' => 'date',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Attributs à ajouter lors de la sérialisation
     */
    protected $appends = [
        'solde_formate',
        'solde_calcule',
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

        // Générer automatiquement le numéro de compte
        static::creating(function ($model) {
            if (empty($model->numero_compte)) {
                $model->numero_compte = $model->genererNumeroCompte();
            }
        });

        // Mettre à jour les métadonnées lors de la sauvegarde
        static::saving(function ($model) {
            $model->updateMetadata();
        });
    }

    /**
     * Générer un numéro de compte unique
     */
    public function genererNumeroCompte(): string
    {
        do {
            $numero = 'SN' . date('Y') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('numero_compte', $numero)->exists());

        return $numero;
    }

    /**
     * Mettre à jour les métadonnées
     */
    public function updateMetadata(): void
    {
        $metadata = $this->metadata ?? [];

        $metadata['derniereModification'] = now()->toISOString();
        $metadata['version'] = ($metadata['version'] ?? 0) + 1;

        $this->metadata = $metadata;
    }

    /**
     * Accesseur pour le solde formaté
     */
    public function getSoldeFormateAttribute(): string
    {
        return number_format($this->solde, 2, ',', ' ') . ' ' . $this->devise;
    }

    /**
     * Accesseur pour le solde calculé (somme des dépôts - somme des retraits)
     */
    public function getSoldeCalculeAttribute(): float
    {
        // Calculer le solde basé sur les transactions
        $debits = $this->transactions()
            ->where('type', 'retrait')
            ->sum('montant');

        $credits = $this->transactions()
            ->where('type', 'depot')
            ->sum('montant');

        return $credits - $debits;
    }

    /**
     * Relation : Un compte appartient à un client
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relation : Un compte a plusieurs transactions
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'compte_id');
    }

    /**
     * Scopes pour les requêtes
     */
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeParType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeParClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeSoldePositif($query)
    {
        return $query->where('solde', '>', 0);
    }

    /**
     * Scope global pour récupérer les comptes non supprimés
     */
    public function scopeNonSupprimes($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope local pour récupérer un compte par son numéro
     */
    public function scopeNumero($query, $numero)
    {
        return $query->where('numero_compte', $numero);
    }

    /**
     * Scope local pour récupérer les comptes d'un client basé sur le téléphone
     */
    public function scopeClient($query, $telephone)
    {
        return $query->whereHas('client', function ($q) use ($telephone) {
            $q->where('telephone', $telephone);
        });
    }
}
