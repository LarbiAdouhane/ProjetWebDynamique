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
    Schema::create('reservations', function (Blueprint $table) {
        $table->unsignedBigInteger('id')->primary();
        $table->unsignedBigInteger('client_id')->index('reservations_client_id_foreign');
        $table->unsignedBigInteger('room_id')->index('reservations_room_id_foreign');
        $table->date('date_debut');
        $table->date('date_fin');
        $table->enum('statut', ['En Attente', 'Confirmée', 'Annulée'])->default('En Attente');
        $table->integer('nbr_personnes');
        $table->double('total_prix');
        $table->date('date_reservation')->default('2025-11-09');
        $table->timestamps();

        // Ajouter les contraintes de clés étrangères avec cascade
        $table->foreign('client_id')
              ->references('id')
              ->on('users') // ou 'clients' selon votre table
              ->onDelete('cascade'); // Suppression en cascade

        $table->foreign('room_id')
              ->references('id')
              ->on('rooms')
              ->onDelete('cascade'); // Suppression en cascade
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
