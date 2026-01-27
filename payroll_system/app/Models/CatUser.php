<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class CatUser extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cat_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employeeNumber',
        'password',
        'attempts',
        'isBlock',
        'changePass',
        'is_admin',
        'userCreation',
        'dateCreation',
        'userUpdate',
        'dateUpdate',
        'statusUser',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attempts' => 'integer',
        'isBlock' => 'boolean',
        'changePass' => 'boolean',
        'is_admin' => 'boolean',
        'dateCreation' => 'datetime',
        'dateUpdate' => 'datetime',
        'statusUser' => 'boolean',
    ];

    /**
     * Mutator to hash the password when setting it.
     */
    public function setPasswordAttribute($value)
    {
        // Only hash if the password is not already hashed
        if (!empty($value)) {
            $this->attributes['password'] = Hash::needsRehash($value) ? Hash::make($value) : $value;
        }
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('statusUser', true);
    }

    /**
     * Scope a query to only include inactive users.
     */
    public function scopeInactive($query)
    {
        return $query->where('statusUser', false);
    }

    /**
     * Scope a query to only include blocked users.
     */
    public function scopeBlocked($query)
    {
        return $query->where('isBlock', true);
    }

    /**
     * Scope a query to only include admin users.
     */
    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }
}
