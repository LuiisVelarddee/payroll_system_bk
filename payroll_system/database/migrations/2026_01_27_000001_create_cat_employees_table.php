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
        Schema::create('cat_employees', function (Blueprint $table) {
            $table->id();
            $table->string('employeeNumber', 50)->unique();
            $table->string('nameEmployee', 150);
            $table->unsignedBigInteger('roleID');
            $table->unsignedBigInteger('userID');
            $table->string('userCreation', 100)->nullable();
            $table->timestamp('dateCreation')->useCurrent();
            $table->string('userUpdate', 100)->nullable();
            $table->timestamp('dateUpdate')->nullable()->useCurrentOnUpdate();
            $table->boolean('statusEmployee')->default(true);
            $table->timestamps();

            // Foreign keys
            $table->foreign('roleID')->references('id')->on('cat_roles')->onDelete('restrict');
            $table->foreign('userID')->references('id')->on('cat_users')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cat_employees');
    }
};
