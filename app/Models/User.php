<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Helpers\StorageHelper;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'nik', 'no_hp', 'skck', 'alamat', 'status_pegawai', 'foto_identitas', 'google_id', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Atribut tambahan yang akan otomatis dimuat ke dalam respon JSON.
     *
     * @var array<int, string>
     */
    protected $appends = ['role', 'has_password'];

    /**
     * Mengecek apakah user memiliki password
     */
    protected function getHasPasswordAttribute()
    {
        return ! empty($this->password);
    }

    /**
     * Mendapatkan role pertama yang dimiliki oleh User.
     */
    protected function getRoleAttribute()
    {
        return $this->roles->first()->name ?? null;
    }

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
        ];
    }

    /**
     * Relasi ke Donasi (Seorang User bisa memiliki banyak Donasi)
     */
    public function donasis()
    {
        return $this->hasMany(Donasi::class);
    }

    public function toArray()
    {
        $array = parent::toArray();
        if (! empty($array['foto_identitas'])) {
            $array['foto_identitas'] = StorageHelper::getUrl($array['foto_identitas']);
        }

        return $array;
    }
}
