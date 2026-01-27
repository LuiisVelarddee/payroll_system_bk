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
        Schema::create('cat_users', function (Blueprint $table) {
            $table->id();
            $table->string('employeeNumber', 50)->unique();
            $table->string('password');
            $table->integer('attempts')->default(0);
            $table->boolean('isBlock')->default(false);
            $table->boolean('changePass')->default(false);
            $table->boolean('is_admin')->default(false);
            $table->string('userCreation', 100)->nullable();
            $table->timestamp('dateCreation')->useCurrent();
            $table->string('userUpdate', 100)->nullable();
            $table->timestamp('dateUpdate')->nullable()->useCurrentOnUpdate();
            $table->boolean('statusUser')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cat_users');
    }
};
