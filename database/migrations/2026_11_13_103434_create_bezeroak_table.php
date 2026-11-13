<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bezeroak', function (Blueprint $table) {
            $table->id();
            $table->string('izena');
            $table->string('emaila')->unique();
            $table->string('telefonoa')->nullable();
            $table->unsignedBigInteger('odoo_id')->nullable()->unique(); // res.partner-en id-a
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bezeroak');
    }
};
