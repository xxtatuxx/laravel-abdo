<?php

namespace App\Http\Controllers\Home;

use App\Models\Episode;
use App\Models\Anime;
use App\Models\EpisodeVideo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        // Query أساسي مع Lazy Loading للعلاقات
        $query = Episode::with(['series', 'videos'])
            ->orderByDesc('id'); // ترتيب من الأحدث إلى الأقدم

        // البحث
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('episode_number', $search)
                  ->orWhereHas('series', function ($seriesQuery) use ($search) {
                      $seriesQuery->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Pagination
        $episodes = $query->paginate(15);

        // Lazy load بعد pagination
        $episodes->getCollection()->load('series', 'videos');

        // إرجاع البيانات لـ Inertia + Vue
        return Inertia::render('home/ar-home', [
            'episodes' => $episodes,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}
