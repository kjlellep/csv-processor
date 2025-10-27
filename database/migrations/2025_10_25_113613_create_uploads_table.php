<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('uploads', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('vendor_id');
            $t->string('original_filename');
            $t->integer('rows_total')->nullable();
            $t->timestamp('processed_at')->nullable();
            $t->string('status')->default('PENDING');
            $t->text('error_message')->nullable();
            $t->string('source_hash')->nullable();
            $t->timestamps();

            $t->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $t->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
