<?php

use App\Models\Announcement;
use App\Models\Product;
use App\Models\Ride;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/',               fn() => view('pages.index'));
Route::get('/shop',           fn() => view('pages.shop'));
Route::get('/rides',          fn() => view('pages.rides'));
Route::get('/announcements',  fn() => view('pages.announcements'));
Route::get('/gallery',        fn() => view('pages.gallery'));
Route::get('/gallery/{slug}', fn() => view('pages.album'));
Route::get('/articles',       fn() => view('pages.articles'));
Route::get('/articles/{slug}',fn() => view('pages.article'));
Route::get('/auth',           fn() => view('pages.auth'));
Route::get('/admin',          fn() => view('pages.admin'));
Route::get('/profile',        fn() => view('pages.profile'));
Route::get('/requests',       fn() => view('pages.requests'));

Route::get('/products/{id}', function (int $id) {
    $product = Product::with([
        'shop:id,user_id,name',
        'shop.user:id,nickname,first_name,last_name',
    ])->find($id);
    abort_if(!$product, 404);
    return view('pages.product', compact('product'));
})->where('id', '[0-9]+');

Route::get('/announcements/{id}', function (int $id) {
    $announcement = Announcement::find($id);
    abort_if(!$announcement, 404);
    return view('pages.announcement', compact('announcement'));
})->where('id', '[0-9]+');

Route::get('/rides/{id}', function (int $id) {
    $ride = Ride::find($id);
    abort_if(!$ride, 404);
    return view('pages.ride', compact('ride'));
})->where('id', '[0-9]+');

Route::get('/sitemap.xml', function () {
    $urls = [];
    $base = rtrim(config('app.url'), '/');

    foreach (['/', '/announcements', '/rides', '/gallery', '/shop', '/articles'] as $path) {
        $urls[] = ['loc' => $base . $path, 'priority' => '0.8'];
    }

    Product::select('id', 'updated_at')->latest()->limit(2000)->get()
        ->each(fn ($p) => $urls[] = [
            'loc'      => $base . '/products/' . $p->id,
            'lastmod'  => optional($p->updated_at)->toAtomString(),
            'priority' => '0.6',
        ]);

    Announcement::select('id', 'updated_at')->latest()->limit(2000)->get()
        ->each(fn ($a) => $urls[] = [
            'loc'      => $base . '/announcements/' . $a->id,
            'lastmod'  => optional($a->updated_at)->toAtomString(),
            'priority' => '0.6',
        ]);

    Ride::select('id', 'updated_at')->latest()->limit(2000)->get()
        ->each(fn ($r) => $urls[] = [
            'loc'      => $base . '/rides/' . $r->id,
            'lastmod'  => optional($r->updated_at)->toAtomString(),
            'priority' => '0.5',
        ]);

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
         . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        $xml .= "  <url>\n    <loc>" . htmlspecialchars($u['loc']) . "</loc>\n";
        if (!empty($u['lastmod'])) $xml .= "    <lastmod>{$u['lastmod']}</lastmod>\n";
        if (!empty($u['priority'])) $xml .= "    <priority>{$u['priority']}</priority>\n";
        $xml .= "  </url>\n";
    }
    $xml .= '</urlset>';

    return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
});

Route::get('/media/{path}', function (string $path) {
    abort_if(str_contains($path, '..'), 404);
    abort_unless(Storage::disk('public')->exists($path), 404);

    return response()->file(Storage::disk('public')->path($path), [
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '.*');
