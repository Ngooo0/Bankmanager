<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'client_id',
        'scopes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'scopes' => 'array',
        ];
    }

    /**
     * Relation avec le client (pour les utilisateurs de type client)
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Vérifier si l'utilisateur est un admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Vérifier si l'utilisateur est un client
     */
    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    /**
     * Obtenir les permissions de l'utilisateur
     */
    public function getPermissions(): array
    {
        return $this->scopes ?? [];
    }

    /**
     * Vérifier si l'utilisateur a une permission spécifique
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getPermissions());
    }

    /**
     * Ajouter des permissions à l'utilisateur
     */
    public function addPermissions(array $permissions): void
    {
        $currentScopes = $this->scopes ?? [];
        $this->scopes = array_unique(array_merge($currentScopes, $permissions));
        $this->save();
    }

    /**
     * Supprimer des permissions de l'utilisateur
     */
    public function removePermissions(array $permissions): void
    {
        $currentScopes = $this->scopes ?? [];
        $this->scopes = array_diff($currentScopes, $permissions);
        $this->save();
    }
}
