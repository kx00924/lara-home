<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Design;
use App\Models\DesignImage;
use App\Models\Order;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class DesignDownloadTest extends TestCase
{
    use RefreshDatabase;

    private string $imagePath;

    protected function setUp(): void
    {
        parent::setUp();
        // A real JPEG under public/ so the zip has something to pack.
        @mkdir(public_path('images/library'), 0775, true);
        $this->imagePath = public_path('images/library/phpunit-sample.jpg');
        $canvas = imagecreatetruecolor(4, 4);
        imagejpeg($canvas, $this->imagePath);
        imagedestroy($canvas);
    }

    protected function tearDown(): void
    {
        @unlink($this->imagePath);
        parent::tearDown();
    }

    private function makeDesign(float $price): Design
    {
        $category = Category::create(['name' => 'Japandi']);
        $room = RoomType::create(['name' => 'Bedroom']);
        $design = Design::create([
            'title' => 'Test Suite', 'category_id' => $category->id, 'room_type_id' => $room->id, 'price' => $price, 'published' => true,
        ]);
        foreach (['Overview', 'Detail'] as $i => $angle) {
            DesignImage::create(['design_id' => $design->id, 'url' => '/images/library/phpunit-sample.jpg', 'angle' => $angle, 'sort_order' => $i]);
        }

        return $design;
    }

    public function test_guest_cannot_download_a_paid_design(): void
    {
        $design = $this->makeDesign(29);

        $this->get(route('designs.download', $design))->assertForbidden();
    }

    public function test_guest_can_download_a_free_design_as_a_zip(): void
    {
        $design = $this->makeDesign(0);

        $response = $this->get(route('designs.download', $design));

        $response->assertOk()->assertHeader('content-type', 'application/zip');
        $this->assertStringContainsString('test-suite-images.zip', $response->headers->get('content-disposition'));
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($response->getFile()->getPathname()));
        $this->assertSame(3, $zip->numFiles); // two images + README
        $this->assertNotFalse($zip->locateName('01-overview.jpg'));
        $zip->close();
    }

    public function test_buyer_can_download_a_paid_design(): void
    {
        $design = $this->makeDesign(29);
        $buyer = User::create(['name' => 'Buyer', 'email' => 'buyer@example.com', 'password' => 'secret123']);
        Order::create(['user_id' => $buyer->id, 'design_id' => $design->id, 'amount' => 29, 'status' => 'paid']);

        $this->actingAs($buyer)->get(route('designs.download', $design))->assertOk();
    }

    public function test_customer_without_purchase_cannot_download_a_paid_design(): void
    {
        $design = $this->makeDesign(29);
        $visitor = User::create(['name' => 'Visitor', 'email' => 'visitor@example.com', 'password' => 'secret123']);

        $this->actingAs($visitor)->get(route('designs.download', $design))->assertForbidden();
    }
}
