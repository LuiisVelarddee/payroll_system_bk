<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cat_roles', function (Blueprint $table) {
            $table->id();
            $table->string('nameRole', 100);
            $table->decimal('salaryBase', 10, 2);
            $table->decimal('bonusRole', 10, 2)->default(0);
            $table->decimal('bonusHours', 10, 2)->default(0);
            $table->decimal('bonusDeliveries', 10, 2)->default(0);
            $table->string('userCreation', 100)->nullable();
            $table->timestamp('dateCreation')->useCurrent();
            $table->string('userUpdate', 100)->nullable();
            $table->timestamp('dateUpdate')->nullable()->useCurrentOnUpdate();
            $table->boolean('statusRole')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('cat_roles');
    }
};
