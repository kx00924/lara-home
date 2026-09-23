<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ThemeSwitchTest extends TestCase
{
    use RefreshDatabase;

    private string $themeDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->themeDir = resource_path('views/themes/phpunit-theme');
        File::ensureDirectoryExists($this->themeDir);
        File::put($this->themeDir.'/home.blade.php', 'PHPUNIT THEME HOME');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->themeDir);
        parent::tearDown();
    }

    public function test_default_theme_renders_the_standard_views(): void
    {
        $this->get('/')->assertOk()->assertDontSee('PHPUNIT THEME HOME');
    }

    public function test_query_parameter_previews_a_theme_for_the_session(): void
    {
        $this->get('/?theme=phpunit-theme')->assertOk()->assertSee('PHPUNIT THEME HOME');
        $this->get('/')->assertOk()->assertSee('PHPUNIT THEME HOME');
        $this->get('/?theme=site')->assertOk()->assertDontSee('PHPUNIT THEME HOME');
    }

    public function test_neon_theme_renders_the_landing_page(): void
    {
        $this->get('/?theme=neon')->assertOk()->assertSee('data-theme="neon"', false);
        $this->get('/designs')->assertOk()->assertSee('data-theme="neon"', false);
        $this->get('/login')->assertOk()->assertSee('data-theme="neon"', false);
    }

    public function test_unknown_theme_is_ignored(): void
    {
        $this->get('/?theme=does-not-exist')->assertOk()->assertDontSee('PHPUNIT THEME HOME');
        $this->get('/theme/does-not-exist')->assertNotFound();
    }
}
