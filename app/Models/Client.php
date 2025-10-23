<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * Le nom de la table
     */
    protected $table = 'clients';

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
        'prenom',
        'nom',
        'email',
        'telephone',
        'adresse',
        'ville',
        'pays',
        'code_postal',
        'numero_identification',
        'type_identification',
        'date_naissance',
        'sexe',
        'profession',
        'employeur',
        'revenu_mensuel',
        'statut',
        'notes',
        'user_id',
    ];

    /**
     * Les attributs qui doivent être cachés
     */
    protected $hidden = [
        'numero_identification', // Sensible
    ];

    /**
     * Les attributs qui doivent être castés
     */
    protected $casts = [
        'date_naissance' => 'date',
        'revenu_mensuel' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Attributs à ajouter lors de la sérialisation
     */
    protected $appends = [
        'nom_complet',
        'age',
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
     * Accesseur pour le nom complet
     */
    public function getNomCompletAttribute(): string
    {
        return "{$this->prenom} {$this->nom}";
    }

    /**
     * Accesseur pour l'âge
     */
    public function getAgeAttribute(): int
    {
        return $this->date_naissance->age ?? 0;
    }

    /**
     * Mutateur pour le nom (mettre en majuscules)
     */
    public function setNomAttribute($value): void
    {
        $this->attributes['nom'] = strtoupper($value);
    }

    /**
     * Mutateur pour le prénom (capitaliser)
     */
    public function setPrenomAttribute($value): void
    {
        $this->attributes['prenom'] = ucwords(strtolower($value));
    }

    /**
     * Mutateur pour l'email (minuscules)
     */
    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = strtolower($value);
    }

    /**
     * Relation : Un client a plusieurs comptes
     */
    public function comptes(): HasMany
    {
        return $this->hasMany(Compte::class, 'client_id');
    }

    /**
     * Relation : Un client peut avoir un utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes pour les requêtes
     */
    public function scopeActif($query)
    {
        return $query->where('statut', 'Actif');
    }

    public function scopeRecherche($query, $terme)
    {
        return $query->where(function ($q) use ($terme) {
            $q->where('nom', 'LIKE', "%{$terme}%")
              ->orWhere('prenom', 'LIKE', "%{$terme}%")
              ->orWhere('email', 'LIKE', "%{$terme}%")
              ->orWhere('telephone', 'LIKE', "%{$terme}%");
        });
    }

    public function scopeParPays($query, $pays)
    {
        return $query->where('pays', $pays);
    }
}