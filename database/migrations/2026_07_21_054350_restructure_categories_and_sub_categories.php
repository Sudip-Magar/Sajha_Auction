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
        Schema::table('products', function (Blueprint $table): void {
            $table->dropForeign(['category_id']);
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
        });

        Schema::rename('categories', 'sub_categories');

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('status');
            $table->integer('sort_order');
            $table->timestamps();
        });

        $rootCategories = DB::table('sub_categories')
            ->whereNull('parent_id')
            ->orderBy('id')
            ->get();

        foreach ($rootCategories as $category) {
            DB::table('categories')->insert([
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'image' => $category->image,
                'icon' => $category->icon,
                'color' => $category->color,
                'status' => $category->status,
                'sort_order' => $category->sort_order,
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
            ]);
        }

        Schema::table('sub_categories', function (Blueprint $table): void {
            $table->renameColumn('parent_id', 'category_id');
        });

        DB::table('sub_categories')
            ->whereNull('category_id')
            ->update(['category_id' => DB::raw('id')]);

        Schema::table('sub_categories', function (Blueprint $table): void {
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->renameColumn('category_id', 'sub_category_id');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->foreign('sub_category_id')->references('id')->on('sub_categories')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropForeign(['sub_category_id']);
        });

        Schema::table('sub_categories', function (Blueprint $table): void {
            $table->dropForeign(['category_id']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->renameColumn('sub_category_id', 'category_id');
        });

        DB::table('sub_categories')
            ->whereColumn('category_id', 'id')
            ->update(['category_id' => null]);

        Schema::dropIfExists('categories');

        Schema::table('sub_categories', function (Blueprint $table): void {
            $table->renameColumn('category_id', 'parent_id');
        });

        Schema::rename('sub_categories', 'categories');

        Schema::table('categories', function (Blueprint $table): void {
            $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
        });
    }
};
