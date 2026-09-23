<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Design;
use App\Models\DesignImage;
use App\Models\Order;
use App\Models\RoomType;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the seeded catalogue against drifting away from what the site expects:
 * every reference resolves, the demo accounts work, and the seeded pages render.
 */
class SeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_seed_creates_the_catalogue_and_accounts(): void
    {
        $this->assertSame(10, Category::count());
        $this->assertSame(10, RoomType::count());
        $this->assertSame(45, Design::count());
        $this->assertGreaterThan(0, Design::where('price', 0)->count(), 'some designs must be free');
        $this->assertGreaterThan(0, Design::where('featured', true)->count());
        $this->assertGreaterThan(0, Order::where('status', 'paid')->count());

        $admin = User::where('email', 'admin@home.studio')->firstOrFail();
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue(User::where('email', 'demo@example.com')->exists());
        $this->assertSame('default', Setting::get('activeTheme'));
    }

    public function test_every_seeded_image_is_a_local_file_that_exists(): void
    {
        $urls = collect([Setting::get('heroImage')])
            ->merge(Category::pluck('image'))
            ->merge(RoomType::pluck('image'))
            ->merge(Design::pluck('cover_image'))
            ->merge(DesignImage::pluck('url'))
            ->unique();

        $this->assertGreaterThan(100, $urls->count());
        foreach ($urls as $url) {
            $this->assertStringStartsWith('/images/library/', $url, "not a library path: $url");
            $this->assertFileExists(public_path($url));
        }
        $this->assertSame(0, Design::doesntHave('images')->count(), 'every design needs at least one image');
    }

    public function test_seeded_pages_render_in_both_themes(): void
    {
        $design = Design::where('price', '>', 0)->firstOrFail();

        $this->get('/')->assertOk()->assertSee(Category::orderBy('sort_order')->firstOrFail()->name)->assertSee(Setting::get('siteName'));
        $this->get('/designs?price=free')->assertOk()->assertSee('Free');
        $this->get(route('designs.show', $design))->assertOk()->assertSee('Unlock to view');
        $this->get('/?theme=neon')->assertOk()->assertSee('data-theme="neon"', false);
        $this->get(route('designs.show', $design))->assertOk()->assertSee('Unlock to view');
    }

    public function test_seeded_accounts_can_sign_in(): void
    {
        $this->post('/login', ['email' => 'admin@home.studio', 'password' => 'Admin123!'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
        $this->post('/logout');

        $this->post('/login', ['email' => 'demo@example.com', 'password' => 'Demo123!'])->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->get('/dashboard')->assertOk()->assertSee('Unlocked designs');
    }
}
