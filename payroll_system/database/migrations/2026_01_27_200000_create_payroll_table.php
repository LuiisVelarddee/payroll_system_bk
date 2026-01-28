<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payroll', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employeeID');
            $table->string('month', 20); // Ej: "Enero 2026"
            $table->integer('year');
            $table->integer('deliveries')->default(0); // Cantidad de entregas
            $table->decimal('baseSalary', 10, 2); // Sueldo base calculado
            $table->decimal('hourBonus', 10, 2)->default(0); // Bono por hora
            $table->decimal('deliveryBonus', 10, 2)->default(0); // Bono por entregas
            $table->decimal('grossSalary', 10, 2); // Sueldo bruto
            $table->decimal('isr', 10, 2)->default(0); // Impuesto ISR
            $table->decimal('foodVouchers', 10, 2)->default(0); // Vales de despensa
            $table->decimal('netSalary', 10, 2); // Sueldo neto
            $table->string('userCreation', 100)->nullable();
            $table->timestamp('dateCreation')->useCurrent();
            $table->string('userUpdate', 100)->nullable();
            $table->timestamp('dateUpdate')->nullable()->useCurrentOnUpdate();
            $table->boolean('statusPayroll')->default(true);
            $table->timestamps();

            // Foreign key
            $table->foreign('employeeID')->references('id')->on('cat_employees')->onDelete('restrict');
            
            // Unique constraint: one payroll per employee per month/year
            $table->unique(['employeeID', 'month', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll');
    }
};
