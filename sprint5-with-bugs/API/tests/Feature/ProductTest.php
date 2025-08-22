<?php

namespace tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Tests\TestCase;

class ProductTest extends TestCase {
    use DatabaseMigrations;

    public function testRetrieveProducts() {
        $product = $this->addProduct();

        $response = $this->getJson('/products');

        $response
            ->assertStatus(ResponseAlias::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'description',
                        'price',
                        'name',
                    ]
                ]
            ]);
    }

    public function testRetrieveProductsByCategory() {
        $this->addProduct();

        $response = $this->getJson('/products?by_category=category-name');

        $response
            ->assertStatus(ResponseAlias::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'description',
                        'price',
                        'name',
                    ]
                ]
            ]);
    }

    public function testRetrieveProductsByCategorySlug() {
        $this->addProduct();

        $response = $this->getJson('/products?by_category_slug=category-slug');

        $response
            ->assertStatus(ResponseAlias::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'description',
                        'price',
                        'name',
                    ]
                ]
            ]);
    }

    public function testRetrieveProductsByBrand() {
        $this->addProduct();

        $response = $this->getJson('/products?by_brand=brand-name');

        $response
            ->assertStatus(ResponseAlias::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'description',
                        'price',
                        'name',
                    ]
                ]
            ]);
    }

    public function testRetrieveProductsByQuery() {
        $this->addProduct();

        $response = $this->getJson('/products?q=test-product');

        $response
            ->assertStatus(ResponseAlias::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'description',
                        'price',
                        'name',
                    ]
                ]
            ]);
    }

    public function testRetrieveRentals() {
        $this->addProduct();

        $response = $this->getJson('/products?by_category_slug=category-slug&is_rental=true');

        $response
            ->assertStatus(ResponseAlias::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'description',
                        'price',
                        'name',
                    ]
                ]
            ]);
    }

    public function testRetrieveProduct() {
        $product = $this->addProduct();

        $response = $this->getJson("/products/{$product->id}");

        $response
            ->assertStatus(ResponseAlias::HTTP_OK)
            ->assertJsonStructure([
                'name',
                'description',
                'price',
                'name',
            ]);
    }

    public function testAddProduct() {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();
        $productImage = ProductImage::factory()->create();

        $payload = ['name' => 'new',
            'description' => 'some description',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'price' => 4.99,
            'is_location_offer' => false,
            'is_rental' => false,
            'product_image_id' => $productImage->id];

        $response = $this->postJson('/products', $payload);

//        $response->dump();

        $response
            ->assertStatus(ResponseAlias::HTTP_CREATED)
            ->assertJsonStructure([
                'id',
                'name',
                'description',
                'price',
                'name',
            ]);
    }

    public function testAddProductRequiredFields() {
        $response = $this->postJson('/products');

        $response
            ->assertStatus(ResponseAlias::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson([
                'name' => ['The name field is required.'],
                'price' => ['The price field is required.'],
                'category_id' => ['The category id field is required.'],
                'brand_id' => ['The brand id field is required.']
            ]);
    }

    public function testDeleteProductUnauthorized() {
        $product = $this->addProduct();

        $this->json('DELETE', "/products/{$product->id}")
            ->assertStatus(ResponseAlias::HTTP_NO_CONTENT);
    }

    public function testDeleteProduct() {
        $admin = User::factory()->create(['role' => 'admin']);

        $product = $this->addProduct();

        $this->deleteJson("/products/{$product->id}", [], $this->headers($admin))
            ->assertStatus(ResponseAlias::HTTP_NO_CONTENT);
    }

    public function testDeleteNonExistingProduct() {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->deleteJson('/products/99', [], $this->headers($admin))
            ->assertStatus(ResponseAlias::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson([
                'id' => ['The selected id is invalid.']
            ]);
    }

    public function testDeleteProductInUse() {
        $invoice = Invoice::factory()->create([
            'total' => 150.00,
            'billing_country' => 'The Netherlands'
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->deleteJson("/products/{$invoice->invoicelines[0]['product_id']}", [], $this->headers($admin))
            ->assertStatus(ResponseAlias::HTTP_CONFLICT);
    }

    public function testUpdateProduct() {
        $product = $this->addProduct();

        $payload = ['name' => 'new name'];

        $this->putJson("/products/{$product->id}", $payload)
            ->assertStatus(ResponseAlias::HTTP_OK)
            ->assertJson([
                'success' => true
            ]);
    }

    public function testRetrieveRelatedProducts() {
        $product = $this->addProduct();

        $response = $this->getJson("/products/{$product->id}/related");

        $response
            ->assertStatus(ResponseAlias::HTTP_OK)
            ->assertJsonStructure([
                '*' => [
                    'name',
                    'description',
                    'price',
                    'name'
                ]
            ]);
    }

    public function testSearchProduct() {
        $this->addProduct();

        $response = $this->getJson('/products/search?q=test-product');

        $response
            ->assertStatus(ResponseAlias::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'description',
                        'price',
                        'name'
                    ]
                ]
            ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function addProduct(): \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model {
        $brand = Brand::factory()->create([
            'name' => 'brand-name',
            'slug' => 'brand-slug'
        ]);
        $category = Category::factory()->create([
            'name' => 'category-name',
            'slug' => 'category-slug'
        ]);
        $productImage = ProductImage::factory()->create();

        $product = Product::factory()->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'product_image_id' => $productImage->id,
            'name' => 'test-product']);
        return $product;
    }

}
