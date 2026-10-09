<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret123', 'role' => 'admin']);
    }

    public function test_pages_render_a_working_active_toggle_and_slug_based_edit_links(): void
    {
        Category::create(['name' => 'Modern Hanok', 'is_active' => true]);
        RoomType::create(['name' => 'Living Room', 'is_active' => false]);

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('onchange="confirmToggle(this,', false)
            ->assertDontSee('onchange=&quot;', false)
            ->assertSee(route('admin.categories.update', 'modern-hanok'), false);
        $this->actingAs($admin)->get(route('admin.room-types.index'))
            ->assertOk()
            ->assertSee(route('admin.room-types.update', 'living-room'), false);
    }

    public function test_admin_creates_edits_and_toggles_styles_and_rooms(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.categories.store'), ['name' => 'Wabi Sabi', 'description' => 'Imperfect beauty', 'is_active' => 1])
            ->assertRedirect()->assertSessionHas('success');
        $style = Category::where('slug', 'wabi-sabi')->firstOrFail();
        $this->assertTrue($style->is_active);

        $this->actingAs($admin)->put(route('admin.categories.update', $style), ['name' => 'Wabi-Sabi Modern', 'description' => 'Updated', 'sort_order' => 4, 'is_active' => 1])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame(['Wabi-Sabi Modern', 'wabi-sabi-modern', 4], [$style->fresh()->name, $style->fresh()->slug, $style->fresh()->sort_order]);

        // The table toggle re-posts every field without is_active to switch the style off.
        $this->actingAs($admin)->put(route('admin.categories.update', $style->fresh()), ['name' => 'Wabi-Sabi Modern', 'description' => 'Updated', 'sort_order' => 4])
            ->assertRedirect();
        $this->assertFalse($style->fresh()->is_active);

        $this->actingAs($admin)->post(route('admin.room-types.store'), ['name' => 'Sun Room', 'icon' => 'sun', 'is_active' => 1])->assertRedirect();
        $room = RoomType::where('slug', 'sun-room')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.room-types.update', $room), ['name' => 'Sun Room', 'icon' => 'sun-medium'])->assertRedirect();
        $this->assertSame(['sun-medium', false], [$room->fresh()->icon, $room->fresh()->is_active]);
    }

    public function test_updating_by_numeric_id_is_not_a_valid_route(): void
    {
        $style = Category::create(['name' => 'Japandi']);

        $this->actingAs($this->admin())->put(url('admin/categories/'.$style->id), ['name' => 'Japandi'])->assertNotFound();
    }
}
