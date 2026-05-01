<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Announcement;
use App\Models\Article;
use App\Models\Photo;
use App\Models\Product;
use App\Models\Ride;
use App\Models\User;

class UserRatingService
{
    public const THRESHOLD_ANNOUNCEMENT_RIDE = 15;
    public const THRESHOLD_PRODUCT           = 30;
    public const THRESHOLD_ARTICLE           = 50;

    /**
     * @return array{
     *   rides:int, announcements:int, photos:int,
     *   articles:int, products:int, total:float
     * }
     */
    public function breakdown(User $user): array
    {
        $rides         = Ride::where('user_id', $user->id)->count();
        $announcements = Announcement::where('user_id', $user->id)->count();

        $albumIds      = Album::where('user_id', $user->id)->pluck('id');
        $photos        = $albumIds->isEmpty() ? 0 : Photo::whereIn('album_id', $albumIds)->count();

        $articles      = Article::where('user_id', $user->id)->count();

        $shopId        = optional($user->shop)->id;
        $products      = $shopId ? Product::where('shop_id', $shopId)->count() : 0;

        $total = $rides * 1
               + $announcements * 1
               + $photos * 0.3
               + $articles * 3
               + $products * 1;

        return [
            'rides'         => $rides,
            'announcements' => $announcements,
            'photos'        => $photos,
            'articles'      => $articles,
            'products'      => $products,
            'total'         => round($total, 2),
        ];
    }

    public function score(User $user): float
    {
        return $this->breakdown($user)['total'];
    }

    public function statusForAnnouncement(User $user): string
    {
        if ($user->is_admin) return 'published';
        return $this->score($user) >= self::THRESHOLD_ANNOUNCEMENT_RIDE ? 'published' : 'pending';
    }

    public function statusForRide(User $user): string
    {
        if ($user->is_admin) return 'published';
        return $this->score($user) >= self::THRESHOLD_ANNOUNCEMENT_RIDE ? 'published' : 'pending';
    }

    public function statusForProduct(User $user): string
    {
        if ($user->is_admin) return 'published';
        return $this->score($user) >= self::THRESHOLD_PRODUCT ? 'published' : 'pending';
    }

    public function statusForArticle(User $user): string
    {
        if ($user->is_admin) return 'published';
        return $this->score($user) >= self::THRESHOLD_ARTICLE ? 'published' : 'pending';
    }
}
