<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Design;
use App\Models\DesignImage;
use App\Models\Order;
use App\Models\RoomType;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Initial catalogue. Taxonomy follows the reference sites (home-designing.com
 * inspirations / home tours, homestyler.com template styles); photos are
 * royalty-free Unsplash images referenced by id.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@home.studio')],
            ['name' => 'Studio Admin', 'password' => env('ADMIN_PASSWORD', 'Admin123!'), 'role' => 'admin']
        );
        $customers = collect([
            ['Mina Park', 'mina@example.com'], ['Kenji Watanabe', 'kenji@example.com'], ['Li Wei', 'liwei@example.com'],
            ['Aisha Rahman', 'aisha@example.com'], ['Demo Customer', 'demo@example.com'],
        ])->map(fn ($c) => User::updateOrCreate(['email' => $c[1]], ['name' => $c[0], 'password' => 'Demo123!', 'role' => 'customer']));

        if (Design::exists()) {
            $this->command?->info('Catalogue already seeded; users ensured.');

            return;
        }

        Setting::put([]);
        Cache::forget('site.settings');

        $cats = collect(SeedData::CATEGORIES)->mapWithKeys(fn ($c, $i) => [$c['name'] => Category::create(['image' => SeedData::u($c['image'], 900)] + $c + ['sort_order' => $i + 1])]);
        $rooms = collect(SeedData::ROOM_TYPES)->mapWithKeys(fn ($r, $i) => [$r['name'] => RoomType::create(['image' => SeedData::u($r['image'], 900)] + $r + ['sort_order' => $i + 1])]);

        $designs = [];
        foreach (SeedData::DESIGNS as $i => $d) {
            $images = SeedData::images($i, $d['imageCount'] ?? 4, $d);
            $design = new Design([
                'title' => $d['title'],
                'summary' => $d['summary'],
                'description' => $d['description'],
                'category_id' => $cats[$d['cat']]->id,
                'room_type_id' => $rooms[$d['room']]->id,
                'tags' => array_values(array_unique(array_merge($d['tags'] ?? [], [strtolower($d['cat']), strtolower($d['room'])]))),
                'colors' => $d['colors'] ?? [],
                'price' => $d['price'] ?? 0,
                'cover_image' => $images[0]['url'],
                'designer' => ['Home Studio', 'Atelier Mu', 'Studio Sora', 'Ondol Lab'][$i % 4],
                'area_sqm' => $d['areaSqm'] ?? 0,
                'featured' => (bool) ($d['featured'] ?? false),
                'published' => true,
                'views' => rand(120, 4200),
                'likes' => rand(5, 320),
            ]);
            $design->created_at = now()->subDays(rand(0, 120));
            $design->save();
            foreach ($images as $k => $img) {
                DesignImage::create($img + ['design_id' => $design->id, 'sort_order' => $k]);
            }
            $designs[] = $design;
        }

        // Demo orders so the dashboard and trending have signal.
        $paid = array_values(array_filter($designs, fn ($d) => $d->price > 0));
        for ($i = 0; $i < 40; $i++) {
            $design = $paid[array_rand($paid)];
            $user = $customers->random();
            $when = now()->subDays(rand(0, 29));
            $status = $i % 9 === 0 ? 'pending' : 'paid';
            $order = Order::create([
                'user_id' => $user->id, 'design_id' => $design->id, 'amount' => $design->price, 'currency' => 'USD',
                'status' => $status, 'provider' => 'demo', 'provider_ref' => "demo_seed_$i", 'paid_at' => $status === 'paid' ? $when : null,
            ]);
            $order->created_at = $when;
            $order->save();
        }
        Order::create(['user_id' => $customers->last()->id, 'design_id' => $paid[0]->id, 'amount' => $paid[0]->price, 'currency' => 'USD', 'status' => 'paid', 'provider' => 'demo', 'provider_ref' => 'demo_seed_fixed', 'paid_at' => now()]);

        foreach ($designs as $design) {
            $design->purchases = $design->orders()->where('status', 'paid')->count();
            $design->save(); // recomputes trending_score
        }

        $this->command?->info(sprintf('[seed] %d styles, %d room types, %d designs, %d orders. Admin: %s / %s · Customer: demo@example.com / Demo123!',
            $cats->count(), $rooms->count(), count($designs), Order::count(), $admin->email, env('ADMIN_PASSWORD', 'Admin123!')));
    }
}
