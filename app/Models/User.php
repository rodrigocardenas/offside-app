<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $with = ['roles'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'theme',
        'theme_mode',
        'unique_id',
        'is_admin',
        'avatar',
        'favorite_competition_id',
        'favorite_club_id',
        'favorite_national_team_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
    ];

    /**
     * Get the user's avatar URL.
     *
     * @return string
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            // Verificar si el archivo existe en el storage público
            if (Storage::disk('public')->exists('avatars/' . $this->avatar)) {
                return url('/avatars/' . $this->avatar);
            }

            // Si no existe en storage público, intentar con la ruta directa
            $directPath = storage_path('app/public/avatars/' . $this->avatar);
            if (file_exists($directPath)) {
                return url('/avatars/' . $this->avatar);
            }

            // Si el archivo no existe, limpiar el campo avatar
            $this->update(['avatar' => null]);
        }

        // Retornar un avatar por defecto basado en el nombre del usuario
        $name = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$name}&background=random";
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles->contains('name', $role);
        }

        return (bool) $role->intersect($this->roles)->count();
    }

    public function assignRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->syncWithoutDetaching([$role->id]);
    }

    public function removeRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->detach($role->id);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Generar un ID único de 4 caracteres alfanuméricos
            do {
                $uniqueId = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
                $fullUniqueId = $user->name . '_' . $uniqueId;
            } while (static::where('unique_id', $fullUniqueId)->exists());

            $user->unique_id = $fullUniqueId;
        });
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function createdGroups()
    {
        return $this->hasMany(Group::class, 'created_by');
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function userReactions()
    {
        return $this->hasMany(UserReaction::class);
    }

    public function groupRoles()
    {
        return $this->hasMany(GroupRole::class);
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function favoriteCompetition()
    {
        return $this->belongsTo(Competition::class, 'favorite_competition_id');
    }

    public function favoriteClub()
    {
        return $this->belongsTo(Team::class, 'favorite_club_id');
    }

    public function favoriteNationalTeam()
    {
        return $this->belongsTo(Team::class, 'favorite_national_team_id');
    }

    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class, 'user_id');
    }
}
