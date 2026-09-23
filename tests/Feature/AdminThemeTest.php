<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminThemeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret123', 'role' => 'admin']);
    }

    public function test_admin_sees_every_available_theme(): void
    {
        $this->actingAs($this->admin())->get(route('admin.themes.index'))
            ->assertOk()
            ->assertSee('Calm')
            ->assertSee('Neon')
            ->assertSee('Activate for visitors');
    }

    public function test_admin_can_activate_a_theme_for_visitors(): void
    {
        $this->actingAs($this->admin())->post(route('admin.themes.activate', 'neon'))
            ->assertRedirect(route('admin.themes.index'));

        $this->assertSame('neon', Setting::get('activeTheme'));
        $this->get('/')->assertOk()->assertSee('data-theme="neon"', false);
    }

    public function test_unknown_theme_cannot_be_activated(): void
    {
        $this->actingAs($this->admin())->post(route('admin.themes.activate', 'missing'))->assertNotFound();
        $this->assertSame('default', Setting::get('activeTheme'));
    }

    public function test_customers_cannot_open_the_themes_page(): void
    {
        $customer = User::create(['name' => 'Customer', 'email' => 'c@example.com', 'password' => 'secret123']);

        $this->actingAs($customer)->get(route('admin.themes.index'))->assertForbidden();
    }
}
