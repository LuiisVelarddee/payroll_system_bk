<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatEmployee extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cat_employees';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employeeNumber',
        'nameEmployee',
        'roleID',
        'userID',
        'userCreation',
        'dateCreation',
        'userUpdate',
        'dateUpdate',
        'statusEmployee',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'dateCreation' => 'datetime',
        'dateUpdate' => 'datetime',
        'statusEmployee' => 'boolean',
    ];

    /**
     * Get the role that the employee belongs to.
     */
    public function role()
    {
        return $this->belongsTo(CatRole::class, 'roleID');
    }

    /**
     * Get the user associated with the employee.
     */
    public function user()
    {
        return $this->belongsTo(CatUser::class, 'userID');
    }

    /**
     * Scope a query to only include active employees.
     */
    public function scopeActive($query)
    {
        return $query->where('statusEmployee', true);
    }

    /**
     * Scope a query to only include inactive employees.
     */
    public function scopeInactive($query)
    {
        return $query->where('statusEmployee', false);
    }
}
