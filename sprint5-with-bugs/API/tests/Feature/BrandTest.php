<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Tests\TestCase;

class BrandTest extends TestCase {
    use DatabaseMigrations;

    public function testRetrieveBrands(): void {
        $response = $this->getJson('/brands');

        $response->assertStatus(ResponseAlias::HTTP_OK)
            ->assertJsonStructure([
                '*' => [
                    'name',
                    'slug'
                ]
            ]);
    }

    public function testRetrieveBrand(): void {
        $brand = Brand::factory()->create();

        $response = $this->getJson("/brands/{$brand->id}");

        $response->assertStatus(ResponseAlias::HTTP_OK)
            ->assertJsonStructure([
                'name',
                'slug'
            ]);
    }

    public function testAddBrand(): void {
        $payload = [
            'name' => $this->faker->name,
            'slug' => $this->faker->slug
        ];

        $response = $this->postJson('/brands', $payload);

        $response->assertStatus(ResponseAlias::HTTP_CREATED)
            ->assertJsonStructure([
                'id',
                'name',
                'slug'
            ]);
    }

    public function testAddBrandRequiredFields(): void {
        $response = $this->postJson('/brands');

        $response
            ->assertStatus(ResponseAlias::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson([
                'name' => ['The name field is required.'],
                'slug' => ['The slug field is required.']
            ]);
    }

    public function testDeleteBrandUnauthorized() {
        $brand = Brand::factory()->create();

        $this->json('DELETE', "/brands/{$brand->id}")
            ->assertStatus(ResponseAlias::HTTP_NO_CONTENT);
    }

    public function testDeleteBrand() {
        $admin = User::factory()->create(['role' => 'admin']);

        $brand = Brand::factory()->create();

        $this->json('DELETE', "/brands/{$brand->id}", [], $this->headers($admin))
            ->assertStatus(ResponseAlias::HTTP_NO_CONTENT);
    }

    public function testDeleteNonExistingBrand() {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->deleteJson('/brands/99', [], $this->headers($admin))
            ->assertStatus(ResponseAlias::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson([
                'id' => ['The selected id is invalid.']
            ]);
    }

    public function testDeleteBrandThatIsInUse() {
        $admin = User::factory()->create(['role' => 'admin']);

        $brand = Brand::factory()->create();
        $category = Category::factory()->create();
        $productImage = ProductImage::factory()->create();

        Product::factory()->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'product_image_id' => $productImage->id]);


        $this->json('DELETE', "/brands/{$brand->id}", [], $this->headers($admin))
            ->assertStatus(ResponseAlias::HTTP_CONFLICT);
    }

    public function testUpdateBrand() {
        $brand = Brand::factory()->create();

        $payload = ['name' => 'new name'];

        $this->putJson("/brands/{$brand->id}", $payload)
            ->assertStatus(ResponseAlias::HTTP_OK)
            ->assertJson([
                'success' => true
            ]);
    }

    public function testSearchBrand() {
        Brand::factory()->create(['name' => 'brandname']);

        $this->getJson('/brands/search?q=brandname')
            ->assertStatus(ResponseAlias::HTTP_OK)
            ->assertJsonStructure([
                '*' => [
                    'name',
                    'slug'
                ]
            ]);
    }
}
