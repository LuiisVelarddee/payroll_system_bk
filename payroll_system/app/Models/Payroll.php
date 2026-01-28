<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payroll';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employeeID',
        'month',
        'year',
        'deliveries',
        'baseSalary',
        'hourBonus',
        'deliveryBonus',
        'grossSalary',
        'isr',
        'foodVouchers',
        'netSalary',
        'userCreation',
        'dateCreation',
        'userUpdate',
        'dateUpdate',
        'statusPayroll',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'year' => 'integer',
        'deliveries' => 'integer',
        'baseSalary' => 'decimal:2',
        'hourBonus' => 'decimal:2',
        'deliveryBonus' => 'decimal:2',
        'grossSalary' => 'decimal:2',
        'isr' => 'decimal:2',
        'foodVouchers' => 'decimal:2',
        'netSalary' => 'decimal:2',
        'dateCreation' => 'datetime',
        'dateUpdate' => 'datetime',
        'statusPayroll' => 'boolean',
    ];

    /**
     * Get the employee that owns the payroll.
     */
    public function employee()
    {
        return $this->belongsTo(CatEmployee::class, 'employeeID');
    }

    /**
     * Scope a query to only include active payroll records.
     */
    public function scopeActive($query)
    {
        return $query->where('statusPayroll', true);
    }

    /**
     * Scope a query to filter by month and year.
     */
    public function scopeByPeriod($query, $month, $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }
}
