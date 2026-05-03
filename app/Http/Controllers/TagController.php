<?php

namespace App\Http\Controllers;

use App\Models\VAPFile;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index()
    {
        return response()->json(Tag::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:tags,name',
        ]);

        $tag = Tag::create($request->all());

        return response()->json($tag);
    }

    public function updateFileTags(Request $request, VAPFile $file)
    {
        abort_unless($file->canBeWrittenBy($request->user()), 403);

        $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'string',
        ]);

        $tags = collect($request->tags)->map(function ($tagName) {
            return Tag::firstOrCreate(['name' => $tagName]);
        });

        $file->tags()->sync($tags->pluck('id'));

        return response()->json($file->load('tags'));
    }
}
