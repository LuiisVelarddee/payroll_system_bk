<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatRole extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cat_roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nameRole',
        'salaryBase',
        'bonusRole',
        'bonusHours',
        'bonusDeliveries',
        'userCreation',
        'dateCreation',
        'userUpdate',
        'dateUpdate',
        'statusRole',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'salaryBase' => 'decimal:2',
        'bonusRole' => 'decimal:2',
        'bonusHours' => 'decimal:2',
        'bonusDeliveries' => 'decimal:2',
        'dateCreation' => 'datetime',
        'dateUpdate' => 'datetime',
        'statusRole' => 'boolean',
    ];

    /**
     * Scope a query to only include active roles.
     */
    public function scopeActive($query)
    {
        return $query->where('statusRole', true);
    }

    /**
     * Scope a query to only include inactive roles.
     */
    public function scopeInactive($query)
    {
        return $query->where('statusRole', false);
    }
}
