<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendor_rules', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('vendor_id');
            $t->string('type');
            $t->smallInteger('position')->default(0);
            $t->string('target')->nullable();
            $t->jsonb('config')->default(new Expression("'{}'::jsonb"));
            $t->boolean('enabled')->default(true);
            $t->timestamps();

            $t->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $t->index(['vendor_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_rules');
    }
};
