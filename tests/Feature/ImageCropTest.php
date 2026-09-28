<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageCropTest extends TestCase
{
    use RefreshDatabase;

    private string $source;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        @mkdir(public_path('images/library'), 0775, true);
        $this->source = public_path('images/library/phpunit-crop-source.jpg');
        $canvas = imagecreatetruecolor(400, 300);
        imagejpeg($canvas, $this->source);
        imagedestroy($canvas);
    }

    protected function tearDown(): void
    {
        @unlink($this->source);
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret123', 'role' => 'admin']);
    }

    public function test_admin_crops_an_image_into_a_new_file(): void
    {
        $response = $this->actingAs($this->admin())->postJson(route('admin.images.crop'), [
            'url' => '/images/library/phpunit-crop-source.jpg', 'x' => 50, 'y' => 20, 'width' => 200, 'height' => 150,
        ]);

        $response->assertOk()->assertJsonPath('width', 200)->assertJsonPath('height', 150)->assertJsonPath('is_panorama', false);
        $path = substr($response->json('url'), strlen('/storage/'));
        Storage::disk('public')->assertExists($path);
        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame([200, 150], [$w, $h]);
        $this->assertFileExists($this->source, 'the original is never overwritten');
    }

    public function test_admin_stores_an_image_cropped_in_the_browser(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.images.crop'), [
            'image' => UploadedFile::fake()->image('crop.jpg', 4096, 2048),
        ], ['Accept' => 'application/json']);

        $response->assertOk()->assertJsonPath('width', 4096)->assertJsonPath('height', 2048)->assertJsonPath('is_panorama', true);
        Storage::disk('public')->assertExists(substr($response->json('url'), strlen('/storage/')));
    }

    public function test_a_box_larger_than_the_image_is_clamped(): void
    {
        $this->actingAs($this->admin())->postJson(route('admin.images.crop'), [
            'url' => 'http://localhost/images/library/phpunit-crop-source.jpg', 'x' => 300, 'y' => 100, 'width' => 5000, 'height' => 5000,
        ])->assertOk()->assertJsonPath('width', 100)->assertJsonPath('height', 200);
    }

    public function test_missing_images_and_customers_are_rejected(): void
    {
        $this->actingAs($this->admin())->postJson(route('admin.images.crop'), [
            'url' => '/images/library/does-not-exist.jpg', 'x' => 0, 'y' => 0, 'width' => 10, 'height' => 10,
        ])->assertStatus(422);

        $customer = User::create(['name' => 'C', 'email' => 'c@example.com', 'password' => 'secret123']);
        $this->actingAs($customer)->postJson(route('admin.images.crop'), [
            'url' => '/images/library/phpunit-crop-source.jpg', 'x' => 0, 'y' => 0, 'width' => 10, 'height' => 10,
        ])->assertForbidden();
    }
}
