<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('processed_rows', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->uuid('upload_id');
            $t->uuid('vendor_id');

            $t->text('product_name')->nullable();
            $t->integer('quantity')->nullable();
            $t->decimal('price', 12, 2)->nullable();
            $t->text('sku')->nullable();

            $t->jsonb('raw_source')->nullable()->default(new Expression('NULL'));

            $t->timestamps();

            $t->foreign('upload_id')->references('id')->on('uploads')->cascadeOnDelete();
            $t->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();

            $t->index('upload_id');
            $t->index('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_rows');
    }
};
