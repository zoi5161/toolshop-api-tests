<?php

namespace tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Tests\TestCase;

class CategoryTest extends TestCase {
    use DatabaseMigrations;

    public function testRetrieveCategories() {
        Category::factory()->create();

        $response = $this->getJson('/categories');

        $response
            ->assertStatus(ResponseAlias::HTTP_OK)
            ->assertJsonStructure([
                '*' => [
                    'name',
                    'slug'
                ]
            ]);
    }

    public function testRetrieveTreeOfCategories() {
        Category::factory()->create();

        $response = $this->getJson('/categories/tree');

        $response
            ->assertStatus(ResponseAlias::HTTP_OK)
            ->assertJsonStructure([
                '*' => [
                    'name',
                    'slug'
                ]
            ]);
    }

    public function testRetrieveTreeOfCategoriesBySlug() {
        Category::factory()->create([
            'slug' => 'test'
        ]);

        $response = $this->getJson('/categories/tree?by_category_slug=test');

        $response
            ->assertStatus(ResponseAlias::HTTP_OK)
            ->assertJsonStructure([
                '*' => [
                    'name',
                    'slug'
                ]
            ]);
    }

    public function testRetrieveCategory() {
        $category = Category::factory()->create();

        $response = $this->getJson("/categories/{$category->id}");

        $response
            ->assertStatus(ResponseAlias::HTTP_OK)
            ->assertJsonStructure([
                'name',
                'slug'
            ]);
    }

    public function testAddCategory() {
        $payload = ['name' => 'new',
            'slug' => 'some description'];

        $response = $this->postJson('/categories', $payload);

        $response
            ->assertStatus(ResponseAlias::HTTP_CREATED)
            ->assertJsonStructure([
                'id',
                'name',
                'slug'
            ]);
    }

    public function testAddCategoryRequiredFields() {
        $response = $this->postJson('/categories');

        $response
            ->assertStatus(ResponseAlias::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson([
                'name' => ['The name field is required.'],
                'slug' => ['The slug field is required.']
            ]);
    }

    public function testDeleteCategoryUnauthorized() {
        $brand = Category::factory()->create();

        $this->json('DELETE', "/categories/{$brand->id}")
            ->assertStatus(ResponseAlias::HTTP_UNAUTHORIZED);
    }

    public function testDeleteCategory() {
        $admin = User::factory()->create(['role' => 'admin']);

        $category = Category::factory()->create();

        $this->deleteJson("/categories/{$category->id}", [], $this->headers($admin))
            ->assertStatus(ResponseAlias::HTTP_NO_CONTENT);
    }

    public function testDeleteNonExistingCategory() {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->deleteJson('/categories/99', [], $this->headers($admin))
            ->assertStatus(ResponseAlias::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson([
                'id' => ['The selected id is invalid.']
            ]);
    }

    public function testDeleteCategoryThatIsInUse() {
        $admin = User::factory()->create(['role' => 'admin']);

        $brand = Brand::factory()->create();
        $category = Category::factory()->create();
        $productImage = ProductImage::factory()->create();

        Product::factory()->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'product_image_id' => $productImage->id]);


        $this->json('DELETE', "/categories/{$category->id}", [], $this->headers($admin))
            ->assertStatus(ResponseAlias::HTTP_CONFLICT);
    }

    public function testUpdateCategory() {
        $category = Category::factory()->create();

        $payload = ['name' => 'new name'];

        $response = $this->putJson("/categories/{$category->id}", $payload);

        $response
            ->assertStatus(ResponseAlias::HTTP_OK)
            ->assertJson([
                'success' => true
            ]);
    }

    public function testSearchCategory() {
        Category::factory()->create(['name' => 'categoryname']);

        $this->getJson('/categories/search?q=categoryname')
            ->assertStatus(ResponseAlias::HTTP_OK)
            ->assertJsonStructure([
                '*' => [
                    'name',
                    'slug'
                ]
            ]);
    }
}
