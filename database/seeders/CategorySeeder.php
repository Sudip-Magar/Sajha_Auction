<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Psy\Util\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'description' => 'Devices and gadgets including phones, laptops, and accessories.',
                'subcategories' => [
                    'Mobile Phones',
                    'Laptops & Computers',
                    'Cameras & Accessories',
                ],
            ],
            [
                'name' => 'Fashion',
                'description' => 'Clothing, footwear, and accessories for men, women, and kids.',
                'subcategories' => [
                    "Men's Clothing",
                    "Women's Clothing",
                    'Footwear',
                ],
            ],
            [
                'name' => 'Home & Kitchen',
                'description' => 'Furniture, appliances, and kitchenware for everyday living.',
                'subcategories' => [
                    'Furniture',
                    'Kitchen Appliances',
                    'Home Decor',
                ],
            ],
            [
                'name' => 'Health & Beauty',
                'description' => 'Personal care, cosmetics, and wellness products.',
                'subcategories' => [
                    'Skincare',
                    'Haircare',
                    'Personal Care',
                ],
            ],
            [
                'name' => 'Sports & Outdoors',
                'description' => 'Equipment and gear for sports, fitness, and outdoor activities.',
                'subcategories' => [
                    'Fitness Equipment',
                    'Outdoor Gear',
                    'Sportswear',
                ],
            ],
        ];

        foreach ($categories as $index => $categoryData) {
            $category = Category::create([
                'name' => $categoryData['name'],
                'slug' =>  \Illuminate\Support\Str::slug($categoryData['name']),
                'description' => $categoryData['description'],
                'status' => 'active',
                'sort_order' => $index + 1,
            ]);

            foreach ($categoryData['subcategories'] as $subIndex => $subcategoryName) {
                SubCategory::create([
                    'name' => $subcategoryName,
                    'slug' =>  \Illuminate\Support\Str::slug($subcategoryName),
                    'category_id' => $category->id,
                    'description' => $subcategoryName . ' under ' . $categoryData['name'],
                    'status' => 'active',
                    'sort_order' => $subIndex + 1,
                ]);
            }
        }
    }
}
