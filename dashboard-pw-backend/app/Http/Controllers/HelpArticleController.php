<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HelpArticleController extends Controller
{
    /**
     * Display a listing of the help articles.
     */
    public function index(Request $request)
    {
        $this->authorizeSuperAdmin();

        $query = \App\Models\HelpArticle::latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('keywords', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    /**
     * Display the specified help article.
     */
    public function show($slug)
    {
        $this->authorizeSuperAdmin();

        $article = \App\Models\HelpArticle::where('slug', $slug)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $article,
        ]);
    }

    /**
     * Store a newly created help article.
     */
    public function store(Request $request)
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string',
            'keywords' => 'nullable|string',
        ]);

        $validated['slug'] = \Illuminate\Support\Str::slug($request->title) . '-' . time();

        $article = \App\Models\HelpArticle::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Artikel bantuan berhasil dibuat',
            'data' => $article,
        ]);
    }

    /**
     * Update the specified help article.
     */
    public function update(Request $request, $id)
    {
        $this->authorizeSuperAdmin();

        $article = \App\Models\HelpArticle::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string',
            'keywords' => 'nullable|string',
        ]);

        if ($request->title !== $article->title) {
            $validated['slug'] = \Illuminate\Support\Str::slug($request->title) . '-' . time();
        }

        $article->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Artikel bantuan berhasil diperbarui',
            'data' => $article,
        ]);
    }

    /**
     * Remove the specified help article.
     */
    public function destroy($id)
    {
        $this->authorizeSuperAdmin();

        $article = \App\Models\HelpArticle::findOrFail($id);
        $article->delete();

        return response()->json([
            'success' => true,
            'message' => 'Artikel bantuan berhasil dihapus',
        ]);
    }

    /**
     * Helper to authorize only superadmin
     */
    private function authorizeSuperAdmin()
    {
        if (auth()->user()->role !== 'superadmin') {
            abort(403, 'Hanya Superadmin yang diperbolehkan mengakses fitur ini.');
        }
    }
}
