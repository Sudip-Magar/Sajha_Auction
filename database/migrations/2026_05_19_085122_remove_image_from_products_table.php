<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $products = DB::table('products')
            ->select('id', 'image', 'created_at', 'updated_at')
            ->whereNotNull('image')
            ->get();

        if ($products->isNotEmpty()) {
            DB::table('product_images')->insert(
                $products
                    ->map(fn ($product): array => [
                        'product_id' => $product->id,
                        'path' => $product->image,
                        'sort_order' => 0,
                        'created_at' => $product->created_at,
                        'updated_at' => $product->updated_at,
                    ])
                    ->all()
            );
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('image')->nullable()->after('type');
        });

        $firstImages = DB::table('product_images')
            ->select('product_id', 'path')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->unique('product_id');

        foreach ($firstImages as $image) {
            DB::table('products')
                ->where('id', $image->product_id)
                ->update(['image' => $image->path]);
        }
    }
};
