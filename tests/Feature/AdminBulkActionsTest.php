<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Design;
use App\Models\Order;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret123', 'role' => 'admin']);
    }

    private function design(string $title, bool $published = true): Design
    {
        return Design::create([
            'title' => $title, 'category_id' => Category::firstOrCreate(['name' => 'Japandi'])->id,
            'room_type_id' => RoomType::firstOrCreate(['name' => 'Living Room'])->id, 'price' => 20, 'published' => $published,
        ]);
    }

    public function test_designs_can_be_published_and_deleted_in_bulk_but_sold_ones_are_kept(): void
    {
        $admin = $this->admin();
        [$a, $b, $sold] = [$this->design('A', false), $this->design('B', false), $this->design('Sold', false)];
        $customer = User::create(['name' => 'C', 'email' => 'c@example.com', 'password' => 'secret123']);
        Order::create(['user_id' => $customer->id, 'design_id' => $sold->id, 'amount' => 20, 'currency' => 'USD', 'status' => 'paid', 'provider' => 'demo', 'paid_at' => now()]);

        $this->actingAs($admin)->post(route('admin.designs.bulk'), ['action' => 'publish', 'ids' => [$a->id, $b->id]])
            ->assertRedirect()->assertSessionHas('success', '2 design(s) published.');
        $this->assertTrue($a->fresh()->published);
        $this->assertTrue($b->fresh()->published);

        $this->actingAs($admin)->post(route('admin.designs.bulk'), ['action' => 'delete', 'ids' => [$a->id, $sold->id]])
            ->assertRedirect()->assertSessionHas('warning', fn (string $m) => str_starts_with($m, '1 design(s) deleted.') && str_contains($m, 'paid orders'));
        $this->assertNull(Design::find($a->id));
        $this->assertNotNull(Design::find($sold->id), 'designs with paid orders are kept');

        $this->actingAs($admin)->post(route('admin.designs.bulk'), ['action' => 'explode', 'ids' => [$b->id]])->assertSessionHasErrors('action');
    }

    public function test_styles_rooms_orders_and_customers_support_bulk_actions(): void
    {
        $admin = $this->admin();
        $style = Category::create(['name' => 'Wabi Sabi', 'is_active' => true]);
        $used = Category::create(['name' => 'Used', 'is_active' => true]);
        $room = RoomType::create(['name' => 'Sun Room', 'is_active' => true]);
        $design = $this->design('D');
        $design->update(['category_id' => $used->id]);

        $this->actingAs($admin)->post(route('admin.categories.bulk'), ['action' => 'deactivate', 'ids' => [$style->id]])->assertRedirect();
        $this->assertFalse($style->fresh()->is_active);
        $this->actingAs($admin)->post(route('admin.categories.bulk'), ['action' => 'delete', 'ids' => [$style->id, $used->id]])->assertSessionHas('warning');
        $this->assertNull(Category::find($style->id));
        $this->assertNotNull(Category::find($used->id), 'styles that still have designs are kept');

        $this->actingAs($admin)->post(route('admin.room-types.bulk'), ['action' => 'deactivate', 'ids' => [$room->id]])->assertRedirect();
        $this->assertFalse($room->fresh()->is_active);

        $customer = User::create(['name' => 'C', 'email' => 'c@example.com', 'password' => 'secret123']);
        $order = Order::create(['user_id' => $customer->id, 'design_id' => $design->id, 'amount' => 20, 'currency' => 'USD', 'status' => 'pending', 'provider' => 'demo']);
        $this->actingAs($admin)->post(route('admin.orders.bulk'), ['action' => 'paid', 'ids' => [$order->id]])->assertSessionHas('success', '1 order(s) marked paid.');
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(1, $design->fresh()->purchases);
        $this->actingAs($admin)->post(route('admin.orders.bulk'), ['action' => 'refunded', 'ids' => [$order->id]])->assertRedirect();
        $this->assertSame(0, $design->fresh()->purchases, 'leaving paid decrements the purchase count');

        $this->actingAs($admin)->post(route('admin.users.bulk'), ['action' => 'deactivate', 'ids' => [$customer->id, $admin->id]])
            ->assertSessionHas('warning', fn (string $m) => str_contains($m, 'Your own account was skipped'));
        $this->assertFalse($customer->fresh()->is_active);
        $this->assertTrue($admin->fresh()->is_active, 'the signed-in admin is never changed in bulk');
    }

    public function test_admin_tables_render_selection_controls_search_and_pagination(): void
    {
        $admin = $this->admin();
        foreach (range(1, 21) as $i) {
            Category::create(['name' => "Style $i"]);
        }

        $this->actingAs($admin)->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('data-row-id', false)
            ->assertSee('Select all')
            ->assertSee(route('admin.categories.bulk'), false)
            ->assertSee('?page=2', false);
        $this->actingAs($admin)->get(route('admin.categories.index', ['q' => 'Style 7']))->assertOk()->assertSee('Style 7')->assertDontSee('Style 8');
        $this->actingAs($admin)->get(route('admin.orders.index', ['q' => 'nobody']))->assertOk()->assertSee('No orders match.');
        $this->design('Listed design');
        $this->actingAs($admin)->get(route('admin.designs.index'))->assertOk()->assertSee('confirmToggle(', false)->assertSee("\$dispatch('open-image'", false);
    }
}
