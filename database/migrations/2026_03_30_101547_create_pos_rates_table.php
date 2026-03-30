<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_rates', function (Blueprint $table) {
            $table->id();
            $table->string('pos_name', 50);
            $table->string('card_type', 30);
            $table->string('card_brand', 30);
            $table->unsignedSmallInteger('installment');
            $table->string('currency', 3);
            $table->decimal('commission_rate', 8, 4);
            $table->decimal('min_fee', 8, 2)->default(0);
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestamps();

            $table->index(['card_type', 'installment', 'currency'], 'pos_rates_selection_index');
            $table->index(['card_type', 'card_brand', 'installment', 'currency'], 'pos_rates_full_selection_index');
            $table->unique(
                ['pos_name', 'card_type', 'card_brand', 'installment', 'currency'],
                'pos_rates_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_rates');
    }
};
