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
        Schema::create('chambres', function (Blueprint $table) {
        $table->id();
        $table->string('numero')->unique();      // numéro de la chambre
        $table->string('type');                  // type de chambre (simple, double...)
        $table->integer('capacite');             // capacité
        $table->decimal('prix', 8, 2);           // prix par nuit
        $table->string('photo')->nullable();     // photo de la chambre
        $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chambres');
    }
};
