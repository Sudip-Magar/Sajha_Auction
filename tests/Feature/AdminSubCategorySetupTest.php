<?php

use App\Livewire\Admin\SubCategorySetup;
use App\Models\Admin;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('requires an admin to manage sub categories', function () {
    $this->get(route('admin.sub-category-setup'))
        ->assertRedirect(route('admin.login'));
});

it('allows admins to create update toggle and delete sub categories', function () {
    $admin = Admin::query()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'phone' => '9800000000',
        'password' => 'password',
        'status' => 'active',
    ]);

    $category = Category::query()->create([
        'name' => 'Electronics',
        'slug' => 'electronics',
        'description' => 'Electronic products',
        'status' => 'active',
        'sort_order' => 1,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.sub-category-setup'))
        ->assertOk();

    Livewire::test(SubCategorySetup::class)
        ->set('category_id', $category->id)
        ->set('name', 'Mobile Phones')
        ->set('slug', 'mobile-phones')
        ->set('description', 'Smartphones and accessories')
        ->set('icon', 'o-device-phone-mobile')
        ->set('color', '#0C8FE8')
        ->set('status', 'active')
        ->set('sort_order', 2)
        ->call('saveSubCategory')
        ->assertHasNoErrors();

    $subCategory = SubCategory::query()->firstOrFail();

    expect($subCategory->category_id)->toBe($category->id)
        ->and($subCategory->name)->toBe('Mobile Phones')
        ->and($subCategory->slug)->toBe('mobile-phones');

    Livewire::test(SubCategorySetup::class)
        ->call('editSubCategory', $subCategory)
        ->set('name', 'Phones')
        ->set('slug', 'phones')
        ->call('saveSubCategory')
        ->assertHasNoErrors();

    expect($subCategory->refresh()->name)->toBe('Phones')
        ->and($subCategory->slug)->toBe('phones');

    Livewire::test(SubCategorySetup::class)
        ->call('toggleStatus', $subCategory);

    expect($subCategory->refresh()->status)->toBe('inactive');

    Livewire::test(SubCategorySetup::class)
        ->call('deleteSubCategory', $subCategory);

    $this->assertDatabaseMissing('sub_categories', [
        'id' => $subCategory->id,
    ]);
});
