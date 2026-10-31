<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('management/Posts', ['posts' => Post::latest()->paginate(15)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($request, $data): void {
            $post = Post::create([...$data, 'author_id' => $request->user()->id]);
            AuditLog::record('post.created', $post);
        });

        return back()->with('success', 'Artículo creado.');
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $data = $this->validated($request, $post);
        DB::transaction(function () use ($data, $post): void {
            $post->update($data);
            AuditLog::record('post.updated', $post);
        });

        return back()->with('success', 'Artículo actualizado.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Post $post = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('posts')->ignore($post)],
            'category' => ['required', 'string', 'max:80'],
            'excerpt' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string', 'min:20', 'max:50000'],
            'published_at' => ['nullable', 'date'],
        ]);
    }
}
