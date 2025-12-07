<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Relation avec réservation
            $table->foreignId('reservation_id')
                ->constrained()
                ->onDelete('cascade');

            // Montant payé
            $table->double('montant');

            // Mode de paiement
            $table->enum('mode', ['Paiement sur Place', 'PayPal'])->default('PayPal');

            // Statut
            $table->enum('statut', ['Payé', 'Échoué', 'En Attente'])->default('En Attente');

            // Date du paiement
            $table->date('date_paiement')->nullable();

            // Champs PayPal
            $table->string('order_id')->nullable(); // ID de commande PayPal
            $table->string('currency')->default('EUR'); // Devise

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};