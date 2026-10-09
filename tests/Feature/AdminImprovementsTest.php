<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Design;
use App\Models\DesignImage;
use App\Models\Order;
use App\Models\RoomType;
use App\Models\Setting;
use App\Models\User;
use App\Support\LocalImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminImprovementsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret123', 'role' => 'admin']);
    }

    private function design(string $title): Design
    {
        return Design::create([
            'title' => $title, 'category_id' => Category::firstOrCreate(['name' => 'Japandi'])->id,
            'room_type_id' => RoomType::firstOrCreate(['name' => 'Living Room'])->id, 'price' => 0, 'published' => true,
        ]);
    }

    public function test_titles_without_latin_letters_still_get_a_usable_slug(): void
    {
        $design = $this->design('现代客厅设计');
        $this->assertMatchesRegularExpression('/^design-[a-z0-9]{6}$/', $design->slug);

        $design->update(['title' => '日式卧室']);
        $this->assertNotSame('', $design->fresh()->slug);
        $this->assertSame('japandi', Category::firstOrCreate(['name' => 'Japandi'])->slug);
        $this->assertMatchesRegularExpression('/^style-[a-z0-9]{6}$/', Category::create(['name' => '北欧风'])->slug);
        $this->assertMatchesRegularExpression('/^room-[a-z0-9]{6}$/', RoomType::create(['name' => '厨房'])->slug);

        $this->actingAs($this->admin())->put(route('admin.designs.update', $design->fresh()), [
            'title' => '极简浴室', 'category_id' => $design->category_id, 'room_type_id' => $design->room_type_id, 'price' => 0, 'published' => 1,
        ])->assertRedirect();
        $this->assertNotSame('', $design->fresh()->slug, 'saving from the admin never leaves the slug empty');
        $this->get(route('designs.show', $design->fresh()))->assertOk();
    }

    public function test_similar_designs_query_uses_only_portable_sql(): void
    {
        $design = $this->design('Tatami Rest');
        $design->update(['tags' => ['tatami', 'shoji']]);
        $other = $this->design('Other');
        $other->update(['tags' => ['tatami'], 'trending_score' => 9000]);

        DB::enableQueryLog();
        $similar = $design->similar(6);
        $sql = collect(DB::getQueryLog())->pluck('query')->first(fn (string $q) => str_contains($q, 'sim_score'));

        $this->assertSame([$other->id], $similar->pluck('id')->all());
        // MySQL/MariaDB only know MIN() as an aggregate; the cap must be a CASE expression.
        $this->assertStringNotContainsString('MIN(', $sql);
        $this->assertStringContainsString('CASE WHEN trending_score / 200.0 > 20 THEN 20', $sql);
    }

    public function test_removing_the_cover_image_from_the_gallery_falls_back_to_the_first_photo(): void
    {
        $admin = $this->admin();
        $design = $this->design('Cover test');
        DesignImage::create(['design_id' => $design->id, 'url' => '/storage/designs/old-cover.jpg', 'angle' => 'Overview', 'sort_order' => 0]);
        DesignImage::create(['design_id' => $design->id, 'url' => '/storage/designs/second.jpg', 'angle' => 'Detail', 'sort_order' => 1]);
        $design->update(['cover_image' => '/storage/designs/old-cover.jpg']);
        $base = ['title' => 'Cover test', 'category_id' => $design->category_id, 'room_type_id' => $design->room_type_id, 'price' => 0, 'published' => 1];

        // The old cover is removed from the gallery and a new image replaces it.
        $this->actingAs($admin)->put(route('admin.designs.update', $design), $base + [
            'cover_image' => '/storage/designs/old-cover.jpg',
            'images' => [['url' => '/storage/designs/new-first.jpg', 'kind' => 'photo'], ['url' => '/storage/designs/second.jpg', 'kind' => 'photo']],
        ])->assertRedirect();
        $this->assertSame('/storage/designs/new-first.jpg', $design->fresh()->cover_image);

        // A cover that never was a gallery image (a dedicated upload) is kept.
        $this->actingAs($admin)->put(route('admin.designs.update', $design), $base + [
            'cover_image' => '/storage/designs/dedicated-cover.jpg',
            'images' => [['url' => '/storage/designs/second.jpg', 'kind' => 'photo']],
        ])->assertRedirect();
        $this->assertSame('/storage/designs/dedicated-cover.jpg', $design->fresh()->cover_image);
    }

    public function test_uploaded_images_are_found_without_the_public_storage_link(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('designs/unlinked.jpg', 'not-really-a-jpeg');

        $this->assertFileDoesNotExist(public_path('storage/designs/unlinked.jpg'));
        $this->assertSame(Storage::disk('public')->path('designs/unlinked.jpg'), LocalImage::path('/storage/designs/unlinked.jpg'));
        $this->assertNull(LocalImage::path('/storage/designs/missing.jpg'));
    }

    public function test_dashboard_can_be_filtered_by_period(): void
    {
        $admin = $this->admin();
        $design = $this->design('Sold');
        $customer = User::create(['name' => 'C', 'email' => 'c@example.com', 'password' => 'secret123']);
        $make = fn (string $when, float $amount) => Order::create(['user_id' => $customer->id, 'design_id' => $design->id, 'amount' => $amount, 'currency' => 'USD', 'status' => 'paid', 'provider' => 'demo', 'paid_at' => now()->parse($when), 'created_at' => now()->parse($when)]);
        $make('-2 days', 10);
        $make('-20 days', 25);
        $make('-200 days', 100);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Last 30 days')->assertSee('2 paid orders in period');
        $this->actingAs($admin)->get(route('admin.dashboard', ['range' => '7d']))->assertOk()->assertSee('1 paid orders in period');
        $this->actingAs($admin)->get(route('admin.dashboard', ['range' => 'year']))->assertOk()->assertSee('Paid orders per month');
        $this->actingAs($admin)->get(route('admin.dashboard', ['range' => 'custom', 'from' => now()->subDays(250)->toDateString(), 'to' => now()->subDays(100)->toDateString()]))
            ->assertOk()->assertSee('1 paid orders in period')->assertSee('$100');
        $this->actingAs($admin)->get(route('admin.dashboard', ['range' => 'custom', 'from' => 'nonsense']))->assertOk();
        $this->actingAs($admin)->get(route('admin.dashboard', ['range' => 'bogus']))->assertOk()->assertSee('Last 30 days');

        // The filter form must not carry a hidden range field: the clicked chip alone decides the period.
        $page = $this->actingAs($admin)->get(route('admin.dashboard', ['range' => 'week']))->assertOk();
        $page->assertDontSee('type="hidden" name="range"', false)->assertSee('name="range" value="week"', false);
        $this->assertSame('week', $page->viewData('range'));
    }

    public function test_notification_colours_are_editable_and_reach_every_layout(): void
    {
        $admin = $this->admin();
        $values = ['toastSuccessColor' => '#00aa55', 'toastErrorColor' => '#cc0011', 'toastWarningColor' => '#ffaa00', 'toastInfoColor' => '#1133ff'];

        $this->actingAs($admin)->put(route('admin.settings.update'), ['siteName' => 'Home Studio'] + $values)->assertRedirect();
        $this->assertSame('#00aa55', Setting::toastColors()['success']);
        $this->actingAs($admin)->put(route('admin.settings.update'), ['siteName' => 'Home Studio', 'toastErrorColor' => 'red'])->assertSessionHasErrors('toastErrorColor');

        $this->actingAs($admin)->get(route('admin.settings.edit', ['tab' => 'theme']))->assertOk()->assertSee('toastWarningColor', false);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('toastColors', false)->assertSee('#cc0011', false);
        $this->get(route('home'))->assertOk()->assertSee('#ffaa00', false);
        $this->get(route('home', ['theme' => 'neon']))->assertOk()->assertSee('#1133ff', false);
    }

    public function test_notification_texts_live_in_the_language_files_for_both_languages(): void
    {
        $flatten = function (array $a, string $prefix = '') use (&$flatten): array {
            $out = [];
            foreach ($a as $k => $v) {
                $out = array_merge($out, is_array($v) ? $flatten($v, "$prefix$k.") : ["$prefix$k" => $v]);
            }

            return $out;
        };
        $en = $flatten(require lang_path('en/messages.php'));
        $zh = $flatten(require lang_path('zh/messages.php'));
        $this->assertSame([], array_diff_key($en, $zh), 'every message needs a Chinese translation');
        $this->assertSame([], array_diff_key($zh, $en), 'zh/messages.php has keys that en/messages.php lacks');
        foreach ($en as $key => $text) {
            preg_match_all('/:\w+/', $text, $m);
            foreach ($m[0] as $placeholder) {
                $this->assertStringContainsString($placeholder, $zh[$key], "placeholder $placeholder missing in zh $key");
            }
        }

        // The controllers read from the files: a changed text shows up without touching code.
        $admin = $this->admin();
        app('translator')->addLines(['messages.admin.settings_saved' => 'Custom wording works.'], 'en');
        $this->actingAs($admin)->put(route('admin.settings.update'), ['siteName' => 'Home Studio'])->assertSessionHas('success', 'Custom wording works.');
        $this->actingAs($admin)->withSession(['locale' => 'zh'])->put(route('admin.settings.update'), ['siteName' => 'Home Studio'])->assertSessionHas('success', '设置已保存，网站即时生效。');
    }

    public function test_every_layout_includes_the_page_loader_and_confirm_dialog(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('page-progress', false)->assertSee('$store.loader.visible', false)->assertSee('$store.confirm.open', false);
        $this->get(route('home', ['theme' => 'neon']))->assertOk()->assertSee('page-progress', false)->assertSee('$store.confirm.open', false);
        $this->actingAs($this->admin())->get(route('admin.designs.index'))->assertOk()->assertSee('page-progress', false)->assertSee('@open-image.window', false);
    }
}
