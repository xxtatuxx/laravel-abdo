<?php

namespace App\Http\Controllers\Home;
use App\Models\Episode;
use App\Models\Anime;
use App\Models\EpisodeVideo;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
   public function index(Request $request)
{
    $search = $request->input('search');

    $query = Episode::with('series')
        ->orderByDesc('id'); // ← ترتيب من الأحدث إلى الأقدم

    if (!empty($search)) {
        $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', "%{$search}%")
              ->orWhere('episode_number', $search)
              ->orWhereHas('series', function ($seriesQuery) use ($search) {
                  $seriesQuery->where('name', 'LIKE', "%{$search}%");
              });
        });
    }

    $episodes = $query->paginate(15);

    return Inertia::render('home/ar-home', [
        'episodes' => $episodes,
        'filters' => [
            'search' => $search,
        ],
    ]);
}

}
