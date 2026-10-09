<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Design;
use App\Models\DesignImage;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PanoramaTourTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret123', 'role' => 'admin']);
    }

    /** A design with one photo and panoramas on two floors. */
    private function designWithTour(float $price): Design
    {
        $design = Design::create([
            'title' => 'Two Floor House', 'category_id' => Category::create(['name' => 'Japandi'])->id,
            'room_type_id' => RoomType::create(['name' => 'Living Room'])->id, 'price' => $price, 'published' => true,
            'floors' => ['Ground floor', 'Upper floor'],
        ]);
        DesignImage::create(['design_id' => $design->id, 'url' => '/storage/designs/photo.jpg', 'angle' => 'Overview', 'sort_order' => 0]);
        DesignImage::create(['design_id' => $design->id, 'url' => '/storage/designs/pano-lounge.jpg', 'kind' => 'panorama', 'floor' => 1, 'title' => 'Lounge', 'pano_yaw' => 30, 'sort_order' => 1]);
        DesignImage::create(['design_id' => $design->id, 'url' => '/storage/designs/pano-bedroom.jpg', 'kind' => 'panorama', 'floor' => 2, 'title' => 'Bedroom', 'sort_order' => 2]);

        return $design->fresh('images');
    }

    public function test_admin_saves_panoramas_floors_and_starting_views(): void
    {
        $design = $this->designWithTour(0);

        $this->actingAs($this->admin())->put(route('admin.designs.update', $design), [
            'title' => $design->title, 'category_id' => $design->category_id, 'room_type_id' => $design->room_type_id, 'price' => 0,
            'published' => 1, 'floors' => ['Ground floor', 'Upper floor', ' '],
            'images' => [
                ['url' => '/storage/designs/pano-new.jpg', 'kind' => 'panorama', 'floor' => 2, 'pano_yaw' => -45, 'pano_pitch' => 10, 'pano_fov' => 60, 'title' => 'Study'],
                ['url' => '/storage/designs/photo.jpg', 'kind' => 'photo', 'angle' => 'Overview'],
            ],
        ])->assertRedirect();

        $design->refresh();
        $this->assertSame(['Ground floor', 'Upper floor'], $design->floors);
        $this->assertSame('/storage/designs/photo.jpg', $design->cover_image, 'a panorama must never become the cover');
        $pano = $design->images->firstWhere('url', '/storage/designs/pano-new.jpg');
        $this->assertTrue($pano->isPanorama());
        $this->assertSame(2, $pano->floor);
        $this->assertSame([-45.0, 10.0, 60.0], [$pano->pano_yaw, $pano->pano_pitch, $pano->pano_fov]);
        $this->assertNull($design->images->firstWhere('url', '/storage/designs/photo.jpg')->floor);
    }

    public function test_unlocked_design_shows_one_viewer_per_floor(): void
    {
        $design = $this->designWithTour(0);

        $tour = $design->tour(true);
        $this->assertCount(2, $tour);
        $this->assertSame(['Ground floor', 'Upper floor'], array_column($tour, 'name'));
        $this->assertSame(30.0, $tour[0]['scenes'][0]['yaw']);

        $this->get(route('designs.show', $design))
            ->assertOk()
            ->assertSee('360° tour')
            ->assertSee('Ground floor')
            ->assertSee('Upper floor')
            ->assertSee('/storage/designs/pano-lounge.jpg', false)
            ->assertSee('panoTour(', false);
    }

    public function test_gallery_lists_photos_only_in_both_themes(): void
    {
        $design = $this->designWithTour(0);

        foreach (['default', 'neon'] as $theme) {
            $this->get(route('designs.show', ['design' => $design, 'theme' => $theme]))
                ->assertOk()
                ->assertSee('downloadButton(', false) // "Download all images" shows progress instead of the page loader
                ->assertSee('Preparing your zip', false)
                ->assertSee('1/1', false)
                ->assertDontSee('1/3', false)
                ->assertDontSee('download="two-floor-house-2.jpg"', false);
        }
    }

    public function test_locked_design_never_renders_panorama_urls(): void
    {
        $design = $this->designWithTour(29);

        $this->get(route('designs.show', $design))
            ->assertOk()
            ->assertSee('360° tour')
            ->assertSee('Unlock this design to walk through every room in 360°.')
            ->assertSee('Lounge')
            ->assertSee('/storage/designs/photo.jpg', false)
            ->assertDontSee('/storage/designs/pano-lounge.jpg', false)
            ->assertDontSee('/storage/designs/pano-bedroom.jpg', false);
        $this->assertNull($design->tour(false)[0]['scenes'][0]['url']);
    }

    public function test_locked_design_reveals_the_first_photo_even_after_a_panorama(): void
    {
        $design = $this->designWithTour(29);
        $design->images()->where('kind', 'panorama')->update(['sort_order' => -1]);

        $this->get(route('designs.show', $design))
            ->assertOk()
            ->assertSee('/storage/designs/photo.jpg', false)
            ->assertDontSee('/storage/designs/pano-lounge.jpg', false);
    }

    public function test_only_floors_with_panoramas_get_a_viewer_and_numbered_rooms_are_labelled(): void
    {
        $design = $this->designWithTour(0);
        $design->images()->where('floor', 2)->update(['floor' => 1]);
        $design->images()->where('title', 'Lounge')->update(['title' => '1']);
        $design->images()->where('title', 'Bedroom')->update(['title' => '2']);

        $tour = $design->fresh('images')->tour(true);
        $this->assertCount(1, $tour, 'an empty floor must not get a viewer');
        $this->assertSame(['Room 1', 'Room 2'], array_column($tour[0]['scenes'], 'title'));

        $this->get(route('designs.show', $design))->assertOk()->assertSee('Room 2')->assertDontSee('Upper floor');
        $this->actingAs($this->admin())->get(route('designs.show', $design))
            ->assertOk()->assertSee('Upper floor')->assertSee('No 360° panoramas on this floor yet');
    }

    public function test_saving_warns_about_panoramas_that_are_not_two_to_one(): void
    {
        @mkdir(public_path('images/library'), 0775, true);
        $path = public_path('images/library/phpunit-portrait.jpg');
        $canvas = imagecreatetruecolor(40, 80);
        imagejpeg($canvas, $path);
        imagedestroy($canvas);
        $design = $this->designWithTour(0);

        try {
            $this->actingAs($this->admin())->put(route('admin.designs.update', $design), [
                'title' => $design->title, 'category_id' => $design->category_id, 'room_type_id' => $design->room_type_id, 'price' => 0, 'published' => 1,
                'images' => [['url' => 'http://localhost/images/library/phpunit-portrait.jpg', 'kind' => 'panorama', 'floor' => 1, 'title' => 'Tall shot']],
            ])->assertRedirect()->assertSessionHas('warning', fn (string $m) => str_contains($m, 'Tall shot') && str_contains($m, '2:1'));

            $this->assertSame('/images/library/phpunit-portrait.jpg', $design->fresh('images')->images->first()->url, 'own-host URLs are stored site-relative');
        } finally {
            @unlink($path);
        }
    }

    public function test_images_marked_360_that_are_not_two_to_one_get_no_viewer(): void
    {
        $design = $this->designWithTour(0);
        // Room 2 on the ground floor is a portrait phone screenshot; the upper-floor one is a real 2:1 panorama.
        $design->images()->where('title', 'Lounge')->update(['width' => 388, 'height' => 837]);
        $design->images()->where('title', 'Bedroom')->update(['width' => 4096, 'height' => 2048]);
        $design = $design->fresh('images');

        $tour = $design->tour(true);
        $this->assertSame(['Upper floor'], array_column($tour, 'name'), 'a floor whose only panorama is misshapen gets no viewer');
        $this->assertSame(['Lounge'], $design->misshapenPanoramas()->pluck('title')->all());

        $this->get(route('designs.show', $design))
            ->assertOk()
            ->assertDontSee('Ground floor')
            ->assertDontSee('are not shown in the tour')
            ->assertSee('/storage/designs/pano-lounge.jpg', false); // still in the gallery, as a photo
        $this->actingAs($this->admin())->get(route('designs.show', $design))
            ->assertOk()
            ->assertSee('are not shown in the tour')
            ->assertSee('Lounge 388×837');
    }

    public function test_upload_flags_two_to_one_images_as_panoramas(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin())->post(route('admin.upload'), [
            'images' => [UploadedFile::fake()->image('living-360.jpg', 4000, 2000), UploadedFile::fake()->image('detail.jpg', 1200, 800)],
        ]);

        $response->assertOk()
            ->assertJsonPath('files.0.is_panorama', true)
            ->assertJsonPath('files.0.width', 4000)
            ->assertJsonPath('files.1.is_panorama', false);
        $this->assertStringStartsWith('/storage/designs/', $response->json('files.0.url'), 'uploads are returned site-relative');
    }
}
