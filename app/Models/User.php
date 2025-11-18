<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'points',
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
            'is_active' => 'boolean',
        ];
    }

    /**
     * Los roles del usuario
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Verifica si el usuario tiene un rol específico
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Verifica si el usuario tiene alguno de los roles especificados
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }
    
    /**
     * Obtener el nombre del primer rol del usuario
     */
    public function getRoleAttribute(): ?string
    {
        return $this->roles()->first()?->name;
    }

    /**
     * Asignar un rol al usuario
     */
    public function assignRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $this->roles()->syncWithoutDetaching([$role->id]);
    }

    /**
     * Las transacciones de puntos del usuario
     */
    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }

    /**
     * Las órdenes del usuario
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Los pagos del usuario
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * La billetera del usuario
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Cuentas bancarias del usuario
     */
    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
    }

    /**
     * Paquetes de sesiones como cliente
     */
    public function purchasedPackages()
    {
        return $this->hasMany(SessionPackage::class, 'client_id');
    }

    /**
     * Paquetes de sesiones como profesional
     */
    public function offeredPackages()
    {
        return $this->hasMany(SessionPackage::class, 'professional_id');
    }

    /**
     * Sesiones como cliente
     */
    public function clientSessions()
    {
        return $this->hasMany(TrainingSession::class, 'client_id');
    }

    /**
     * Sesiones como profesional
     */
    public function professionalSessions()
    {
        return $this->hasMany(TrainingSession::class, 'professional_id');
    }

    /**
     * Crear billetera para el usuario si no existe
     */
    public function getOrCreateWallet()
    {
        return $this->wallet ?? $this->wallet()->create([
            'balance_available' => 0,
            'balance_frozen' => 0,
        ]);
    }

    /**
     * Perfil de gimnasio
     */
    public function gymProfile()
    {
        return $this->hasOne(GymProfile::class);
    }

    /**
     * Perfil de entrenador
     */
    public function trainerProfile()
    {
        return $this->hasOne(TrainerProfile::class);
    }

    /**
     * Perfil de nutricionista
     */
    public function nutritionistProfile()
    {
        return $this->hasOne(NutritionistProfile::class);
    }

    /**
     * Perfil de cliente
     */
    public function clientProfile()
    {
        return $this->hasOne(ClientProfile::class);
    }

    /**
     * Metas del cliente
     */
    public function goals()
    {
        return $this->hasMany(ClientGoal::class, 'client_id');
    }

    /**
     * Medidas del cliente
     */
    public function measurements()
    {
        return $this->hasMany(ClientMeasurement::class, 'client_id');
    }

    /**
     * Reviews recibidas (como profesional)
     */
    public function receivedReviews()
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    /**
     * Reviews dadas (como cliente)
     */
    public function givenReviews()
    {
        return $this->hasMany(Review::class, 'client_id');
    }

    /**
     * Agregar puntos al usuario
     */
    public function addPoints(int $points, string $source, string $description = null, $reference = null): void
    {
        $this->increment('points', $points);
        
        $this->pointTransactions()->create([
            'points' => $points,
            'type' => 'earned',
            'source' => $source,
            'description' => $description,
            'reference_id' => $reference ? $reference->id : null,
            'reference_type' => $reference ? get_class($reference) : null,
        ]);
    }

    /**
     * Restar puntos al usuario
     */
    public function subtractPoints(int $points, string $source, string $description = null, $reference = null): bool
    {
        if ($this->points < $points) {
            return false; // No tiene suficientes puntos
        }

        $this->decrement('points', $points);
        
        $this->pointTransactions()->create([
            'points' => -$points,
            'type' => 'spent',
            'source' => $source,
            'description' => $description,
            'reference_id' => $reference ? $reference->id : null,
            'reference_type' => $reference ? get_class($reference) : null,
        ]);

        return true;
    }
}
