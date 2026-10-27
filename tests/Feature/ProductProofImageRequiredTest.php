<?php

use App\Livewire\User\ManageProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function proofTestSubCategory(): SubCategory
{
    $category = Category::create(['name' => 'Cat', 'slug' => 'cat-'.Str::random(6), 'status' => 'active', 'sort_order' => 1]);

    return SubCategory::create(['category_id' => $category->id, 'name' => 'Sub', 'slug' => 'sub-'.Str::random(6), 'status' => 'active', 'sort_order' => 1]);
}

function fillDirectSellForm($component, SubCategory $subCategory)
{
    return $component
        ->set('name', 'Second Hand Phone')
        ->set('description', 'Good condition phone.')
        ->set('sub_category_id', $subCategory->id)
        ->set('condition', 'like-new')
        ->set('quantity', 1)
        ->set('sale_price', 15000)
        ->set('negotiable', 'fixed')
        ->set('meetup_location', 'Kathmandu');
}

test('a new listing is rejected without a proof image even when a general image is uploaded', function () {
    Storage::fake('public');
    $seller = User::factory()->create(['is_seller' => true]);

    fillDirectSellForm(Livewire::actingAs($seller)->test(ManageProduct::class), proofTestSubCategory())
        ->set('newImages', [UploadedFile::fake()->image('phone.jpg')])
        ->call('save')
        ->assertHasErrors(['newProofImages']);

    expect(Product::count())->toBe(0);
});

test('a new listing succeeds once both a general image and a proof image are uploaded', function () {
    Storage::fake('public');
    $seller = User::factory()->create(['is_seller' => true]);

    fillDirectSellForm(Livewire::actingAs($seller)->test(ManageProduct::class), proofTestSubCategory())
        ->set('newImages', [UploadedFile::fake()->image('phone.jpg')])
        ->set('newProofImages', [UploadedFile::fake()->image('receipt.jpg')])
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::firstOrFail();
    expect($product->proofImages)->toHaveCount(1);
});

test('removing the only proof image while editing blocks saving until a new one is added', function () {
    Storage::fake('public');
    $seller = User::factory()->create(['is_seller' => true]);
    $subCategory = proofTestSubCategory();

    $product = Product::create([
        'seller_id' => $seller->id,
        'sub_category_id' => $subCategory->id,
        'name' => 'Editable Phone',
        'description' => 'x',
        'condition' => 'like-new',
        'quantity' => 1,
        'sale_price' => 15000,
        'listing_type' => 'direct_seller',
        'status' => 'pending',
        'approval_status' => 'correction',
        'remarks' => 'Please clarify condition.',
    ]);
    ProductImage::create(['product_id' => $product->id, 'path' => 'products/existing.jpg', 'image_type' => 'general', 'sort_order' => 1]);
    $proofImage = ProductImage::create(['product_id' => $product->id, 'path' => 'products/proofs/existing.jpg', 'image_type' => 'proof', 'sort_order' => 1]);

    $component = fillDirectSellForm(Livewire::actingAs($seller)->test(ManageProduct::class, ['product' => $product]), $subCategory)
        ->call('removeExistingProofImage', $proofImage->id)
        ->call('save')
        ->assertHasErrors(['newProofImages']);

    expect($product->fresh()->proofImages)->toHaveCount(1);

    $component
        ->set('newProofImages', [UploadedFile::fake()->image('new-receipt.jpg')])
        ->call('save')
        ->assertHasNoErrors();

    expect($product->fresh()->proofImages)->toHaveCount(1);
});
