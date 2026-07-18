<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens,HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web'; // ⬅️ اختياري لكن مفيد باش يكون واضح

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'active',
        'route_home',
        'mail_integrate',
        'mail_integration_login',
        'mail_integration_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'mail_integration_password',
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
            'active' => 'boolean',
            'password' => 'hashed',
            'mail_integrate' => 'boolean',
            'mail_integration_password' => 'encrypted',
        ];
    }

    public function mailAccounts()
    {
        return $this->hasMany(MailAccount::class);
    }

    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    public function guide()
    {
        return $this->hasOne(Guide::class);
    }

    public function supplierClients()
    {
        return $this->hasMany(SupplierClient::class);
    }

    public function supplierVehicules()
    {
        return $this->hasMany(SupplierVehicule::class);
    }

    public function mobileDeviceTokens()
    {
        return $this->hasMany(MobileDeviceToken::class);
    }

    public function isSuperAdmin(): bool
    {
        return method_exists($this, 'hasAnyRole')
            && $this->hasAnyRole(['super_admin', 'admin']);
    }
}
