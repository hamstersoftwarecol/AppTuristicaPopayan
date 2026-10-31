<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'string', 'max:80']]);

        return Inertia::render('blog/Index', [
            'posts' => Post::where('published_at', '<=', now())
                ->when($filters['q'] ?? null, fn ($query, $q) => $query->where('title', 'like', '%'.$q.'%'))
                ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
                ->latest('published_at')->paginate(9)->withQueryString(),
            'categories' => Post::where('published_at', '<=', now())->distinct()->orderBy('category')->pluck('category'),
            'filters' => $filters,
        ]);
    }

    public function show(string $slug): Response
    {
        return Inertia::render('blog/Show', ['post' => Post::where('slug', $slug)->where('published_at', '<=', now())->firstOrFail()]);
    }
}
