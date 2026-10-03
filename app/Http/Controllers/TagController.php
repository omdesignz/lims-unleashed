<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\VAPFile;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $laboratory) {}

    public function index(): JsonResponse
    {
        return response()->json(Tag::query()
            ->where('lab_id', $this->laboratory->activeLabId())
            ->orderBy('name')
            ->get(['id', 'name']));
    }

    public function store(Request $request): JsonResponse
    {
        $labId = $this->laboratory->activeLabId();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('tags', 'name')->where('lab_id', $labId)],
        ]);

        $tag = Tag::query()->create(['lab_id' => $labId, 'name' => $validated['name']]);

        return response()->json($tag->only(['id', 'name']), 201);
    }

    public function updateFileTags(Request $request, VAPFile $file): JsonResponse
    {
        $labId = $this->laboratory->activeLabId();
        abort_unless((int) $file->lab_id === $labId, 404);
        abort_unless($file->canBeWrittenBy($request->user()), 403);

        $validated = $request->validate([
            'tags' => 'present|array|max:30',
            'tags.*' => 'required|string|max:100',
        ]);

        $tagNames = collect($validated['tags'])->unique()->values();
        DB::transaction(function () use ($file, $labId, $tagNames): void {
            $tags = $tagNames->map(fn (string $tagName) => Tag::query()->firstOrCreate([
                'lab_id' => $labId,
                'name' => $tagName,
            ]));

            $file->tags()->sync($tags->pluck('id'));
        });

        return response()->json(['tags' => $tagNames]);
    }
}
