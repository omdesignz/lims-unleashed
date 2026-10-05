<?php

namespace App\Http\Controllers;

use App\Http\Resources\VAPFileResource;
use App\Http\Resources\VAPFileVersionResource;
use App\Models\User;
use App\Models\VAPFile;
use App\Models\VAPFilePermission;
use App\Models\VAPFileShare;
use App\Models\VAPFileVersion;
use App\Models\WorkflowTask;
use App\Services\SampleLaboratoryAccess;
use App\Support\NotificationTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VAPFileController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $laboratory) {}

    public function index(Request $request)
    {
        $files = VAPFile::query()
            ->with([
                'versions.creator',
                'permissions.user',
                'shares.sharedWithUser',
                'tags',
                'creator',
                'owner',
                'approver',
                'supersededBy',
            ])
            ->when($request->has('parent_id'), function ($query) use ($request) {
                $query->where('parent_id', $request->parent_id);
            })
            ->when($request->has('archived'), function ($query) use ($request) {
                $query->where('archived', $request->boolean('archived'));
            })
            ->tap(fn ($query) => $this->scopeAccessibleFiles($query, $request->user()))
            ->orderByRaw("case when type = 'folder' then 0 else 1 end")
            ->orderBy('name')
            ->get();

        return VAPFileResource::collection($files);
    }

    public function show(Request $request, VAPFile $file)
    {
        $this->authorizeRead($request, $file);

        return new VAPFileResource($file->load([
            'versions.creator',
            'permissions.user',
            'shares.sharedWithUser',
            'tags',
            'creator',
            'owner',
            'approver',
            'supersededBy',
        ]));
    }

    public function upload(Request $request)
    {
        $labId = $this->laboratory->activeLabId();
        $validated = $request->validate([
            'file' => 'required|file',
            'parent_id' => 'nullable|exists:v_files,id',
            'override' => 'nullable|boolean',
            ...$this->documentMetadataRules(),
        ]);

        $parentId = $validated['parent_id'] ?? null;
        $parent = $parentId ? VAPFile::query()->findOrFail($parentId) : null;
        if ($parent) {
            $this->authorizeWrite($request, $parent);
            if ($parent->type !== 'folder') {
                throw ValidationException::withMessages(['parent_id' => 'O destino deve ser uma pasta.']);
            }
        }

        $uploadedFile = $request->file('file');
        $fileName = $uploadedFile->getClientOriginalName();
        $existingFile = VAPFile::query()
            ->where('lab_id', $labId)
            ->where('parent_id', $parentId)
            ->where('name', $fileName)
            ->first();

        if ($existingFile) {
            $this->authorizeWrite($request, $existingFile);
        }

        if ($existingFile && ! $request->boolean('override')) {
            return response()->json([
                'message' => trans('gestlab.general.labels.vap_filemanager.notifications.error_file_already_exists'),
                'existing_file' => new VAPFileResource($existingFile->load(['versions.creator', 'permissions.user'])),
            ], 409);
        }

        $storagePath = $this->storeUploadedFile($uploadedFile, $fileName);
        $checksum = hash_file('sha256', $uploadedFile->getRealPath());
        $changeReason = $validated['change_reason'] ?? ($existingFile ? 'Conteúdo actualizado' : 'Primeira emissão');

        if ($existingFile) {
            DB::transaction(function () use ($existingFile, $uploadedFile, $storagePath, $checksum, $changeReason, $validated, $request) {
                $revisionCode = $this->nextRevisionCode($existingFile);

                VAPFileVersion::create([
                    'file_id' => $existingFile->id,
                    'revision_code' => $revisionCode,
                    'content' => $storagePath,
                    'mime_type' => $uploadedFile->getMimeType(),
                    'size' => $uploadedFile->getSize(),
                    'checksum' => $checksum,
                    'created_by' => $request->user()->id,
                    'comment' => 'Ficheiro actualizado',
                    'change_reason' => $changeReason,
                ]);

                $existingFile->update(array_merge(
                    $this->revisionMetadataPayload($validated),
                    [
                        'size' => $uploadedFile->getSize(),
                        'mime_type' => $uploadedFile->getMimeType(),
                        'content' => $storagePath,
                        'modified_at' => now(),
                        'revision_code' => $revisionCode,
                    ]
                ));

                $this->logDocumentEvent($existingFile, 'updated', 'Documento actualizado com nova revisão.', [
                    'revision_code' => $revisionCode,
                    'checksum' => $checksum,
                    'change_reason' => $changeReason,
                ]);
            });

            return new VAPFileResource($existingFile->fresh([
                'versions.creator',
                'permissions.user',
                'shares.sharedWithUser',
                'tags',
                'creator',
                'owner',
                'approver',
            ]));
        }

        $file = DB::transaction(function () use ($fileName, $uploadedFile, $parentId, $storagePath, $checksum, $validated, $request, $changeReason, $labId) {
            $file = VAPFile::create(array_merge(
                $this->buildMetadataPayload($validated, $request),
                [
                    'lab_id' => $labId,
                    'name' => $fileName,
                    'type' => 'file',
                    'size' => $uploadedFile->getSize(),
                    'parent_id' => $parentId,
                    'mime_type' => $uploadedFile->getMimeType(),
                    'content' => $storagePath,
                    'modified_at' => now(),
                    'created_by' => $request->user()->id,
                    'revision_code' => 'R01',
                ]
            ));

            VAPFileVersion::create([
                'file_id' => $file->id,
                'revision_code' => 'R01',
                'content' => $storagePath,
                'mime_type' => $uploadedFile->getMimeType(),
                'size' => $uploadedFile->getSize(),
                'checksum' => $checksum,
                'created_by' => $request->user()->id,
                'comment' => 'Versão inicial',
                'change_reason' => $changeReason,
            ]);

            VAPFilePermission::create([
                'file_id' => $file->id,
                'user_id' => $request->user()->id,
                'access_level' => 'admin',
            ]);

            $this->logDocumentEvent($file, 'created', 'Documento controlado criado.', [
                'revision_code' => 'R01',
                'checksum' => $checksum,
                'change_reason' => $changeReason,
            ]);

            return $file;
        });

        return new VAPFileResource($file->load([
            'versions.creator',
            'permissions.user',
            'shares.sharedWithUser',
            'tags',
            'creator',
            'owner',
            'approver',
        ]));
    }

    public function uploadFolder(Request $request)
    {
        $labId = $this->laboratory->activeLabId();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:v_files,id',
            ...$this->documentMetadataRules(false),
        ]);

        $parentId = $validated['parent_id'] ?? null;
        $parent = $parentId ? VAPFile::query()->findOrFail($parentId) : null;
        if ($parent) {
            $this->authorizeWrite($request, $parent);
            if ($parent->type !== 'folder') {
                throw ValidationException::withMessages(['parent_id' => 'O destino deve ser uma pasta.']);
            }
        }

        $existingFolder = VAPFile::query()
            ->where('lab_id', $labId)
            ->where('parent_id', $parentId)
            ->where('name', $validated['name'])
            ->where('type', 'folder')
            ->first();

        if ($existingFolder) {
            return response()->json([
                'message' => trans('gestlab.general.labels.vap_filemanager.notifications.error_folder_already_exists'),
                'existing_folder' => new VAPFileResource($existingFolder),
            ], 409);
        }

        $folder = DB::transaction(function () use ($validated, $request, $labId, $parentId) {
            $folder = VAPFile::create(array_merge(
                $this->buildMetadataPayload($validated, $request, false),
                [
                    'lab_id' => $labId,
                    'name' => $validated['name'],
                    'type' => 'folder',
                    'parent_id' => $parentId,
                    'modified_at' => now(),
                    'created_by' => $request->user()->id,
                    'status' => $validated['status'] ?? 'effective',
                ]
            ));

            VAPFilePermission::create([
                'file_id' => $folder->id,
                'user_id' => $request->user()->id,
                'access_level' => 'admin',
            ]);

            $this->logDocumentEvent($folder, 'created', 'Pasta documental criada.', [
                'type' => 'folder',
            ]);

            return $folder;
        });

        return new VAPFileResource($folder->load(['permissions.user', 'creator', 'owner']));
    }

    public function rename(Request $request, VAPFile $file)
    {
        $this->authorizeWrite($request, $file);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'change_reason' => 'nullable|string|max:1000',
        ]);

        $exists = VAPFile::query()
            ->where('lab_id', $file->lab_id)
            ->where('parent_id', $file->parent_id)
            ->where('name', $validated['name'])
            ->where('id', '!=', $file->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => trans('gestlab.general.labels.vap_filemanager.notifications.error_file_or_folder_already_exists_in_this_location'),
            ], 409);
        }

        $file->update([
            'name' => $validated['name'],
            'modified_at' => now(),
            'change_reason' => $validated['change_reason'] ?? 'Document renamed',
        ]);

        $this->logDocumentEvent($file, 'renamed', 'Documento renomeado.', [
            'new_name' => $validated['name'],
            'change_reason' => $validated['change_reason'] ?? null,
        ]);

        return new VAPFileResource($file->fresh(['permissions.user', 'creator', 'owner']));
    }

    public function move(Request $request, VAPFile $file)
    {
        $this->authorizeWrite($request, $file);

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:v_files,id',
            'change_reason' => 'nullable|string|max:1000',
        ]);

        $newParentId = $validated['parent_id'] ?? null;

        if ($newParentId) {
            $targetFolder = VAPFile::query()->findOrFail($newParentId);
            $this->authorizeWrite($request, $targetFolder);

            if ($targetFolder->type !== 'folder') {
                return response()->json([
                    'message' => trans('gestlab.general.labels.vap_filemanager.notifications.error_target_location_not_folder'),
                ], 400);
            }

            if ($file->type === 'folder') {
                $ancestor = $targetFolder;
                $visited = [];

                while ($ancestor) {
                    if ($ancestor->is($file) || isset($visited[$ancestor->id])) {
                        throw ValidationException::withMessages(['parent_id' => 'Uma pasta não pode ser movida para dentro de si própria.']);
                    }

                    $visited[$ancestor->id] = true;
                    $ancestor = $ancestor->parent_id
                        ? VAPFile::query()->where('lab_id', $file->lab_id)->findOrFail($ancestor->parent_id)
                        : null;
                }
            }
        }

        $exists = VAPFile::query()
            ->where('lab_id', $file->lab_id)
            ->where('parent_id', $newParentId)
            ->where('name', $file->name)
            ->where('id', '!=', $file->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => trans('gestlab.general.labels.vap_filemanager.notifications.error_file_or_folder_already_exists_in_this_location'),
            ], 409);
        }

        $file->update([
            'parent_id' => $newParentId,
            'modified_at' => now(),
            'change_reason' => $validated['change_reason'] ?? 'Document moved',
        ]);

        $this->logDocumentEvent($file, 'moved', 'Documento movido.', [
            'new_parent_id' => $newParentId,
            'change_reason' => $validated['change_reason'] ?? null,
        ]);

        return new VAPFileResource($file->fresh(['permissions.user', 'creator', 'owner']));
    }

    public function archive(Request $request, VAPFile $file)
    {
        $this->authorizeWrite($request, $file);

        DB::transaction(function () use ($file) {
            $file->update([
                'archived' => true,
                'archived_at' => now(),
                'status' => 'archived',
            ]);

            if ($file->type === 'folder') {
                VAPFile::query()
                    ->where('lab_id', $file->lab_id)
                    ->where('parent_id', $file->id)
                    ->update([
                        'archived' => true,
                        'archived_at' => now(),
                        'status' => 'archived',
                    ]);
            }
        });

        $this->logDocumentEvent($file, 'archived', 'Documento arquivado.');

        return new VAPFileResource($file->fresh(['permissions.user', 'creator', 'owner', 'approver']));
    }

    public function restore(Request $request, VAPFile $file)
    {
        $this->authorizeWrite($request, $file);

        DB::transaction(function () use ($file) {
            $restoredStatus = $file->obsolete_at ? 'obsolete' : ($file->approved_at ? 'effective' : 'draft');

            $file->update([
                'archived' => false,
                'archived_at' => null,
                'status' => $restoredStatus,
            ]);

            if ($file->type === 'folder') {
                VAPFile::query()
                    ->where('lab_id', $file->lab_id)
                    ->where('parent_id', $file->id)
                    ->update([
                        'archived' => false,
                        'archived_at' => null,
                        'status' => 'draft',
                    ]);
            }
        });

        $this->logDocumentEvent($file, 'restored', 'Documento restaurado do arquivo.');

        return new VAPFileResource($file->fresh(['permissions.user', 'creator', 'owner', 'approver']));
    }

    public function destroy(Request $request, VAPFile $file)
    {
        $this->authorizeApprove($request, $file);

        // Deleting a folder never deletes what is inside it: each document is
        // deleted, or moved, on its own, so each leaves its own record.
        if ($file->type === 'folder' && VAPFile::withoutGlobalScopes()->where('parent_id', $file->id)->exists()) {
            return response()->json([
                'message' => 'A pasta ainda tem documentos ou pastas, incluindo os arquivados. Mova-os ou elimine-os primeiro.',
            ], 422);
        }

        if ($file->content) {
            Storage::delete($file->content);
        }

        foreach ($file->versions as $version) {
            Storage::delete($version->content);
        }

        $this->logDocumentEvent($file, 'deleted', 'Documento eliminado permanentemente.');

        $file->delete();

        return response()->json([
            'message' => trans('gestlab.general.labels.vap_filemanager.notifications.file_deleted'),
        ]);
    }

    public function share(Request $request, VAPFile $file, NotificationTemplateService $templates)
    {
        $this->authorizeApprove($request, $file);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'access_level' => 'required|in:read,write,admin',
        ]);

        $recipient = User::query()->findOrFail($validated['user_id']);
        if (! $recipient->is_active || ! $recipient->hasVerifiedEmail()
            || ! DB::table('lab_user')->where('lab_id', $file->lab_id)->where('user_id', $recipient->id)->exists()) {
            throw ValidationException::withMessages(['user_id' => 'O destinatário deve pertencer directamente ao laboratório deste documento.']);
        }

        DB::transaction(function () use ($file, $validated) {
            VAPFilePermission::query()->updateOrCreate(
                ['file_id' => $file->id, 'user_id' => $validated['user_id']],
                ['access_level' => $validated['access_level']]
            );

            VAPFileShare::query()->firstOrCreate([
                'file_id' => $file->id,
                'shared_with' => $validated['user_id'],
            ]);
        });

        $this->logDocumentEvent($file, 'shared', 'Permissão documental actualizada.', [
            'shared_with' => $validated['user_id'],
            'access_level' => $validated['access_level'],
        ]);

        if (! $recipient->is($request->user())) {
            $templates->notify([$recipient], 'documents.controlled_file.shared', [
                'lab_id' => $file->lab_id,
                'actor_name' => $request->user()->name,
                'access_level' => $validated['access_level'],
                'document_label' => $file->name,
                'document_url' => route('file-manager'),
            ]);
        }

        return new VAPFileResource($file->fresh(['permissions.user', 'shares.sharedWithUser', 'creator', 'owner']));
    }

    public function versions(Request $request, VAPFile $file)
    {
        $this->authorizeRead($request, $file);

        return VAPFileVersionResource::collection($file->versions()->with('creator')->latest()->get());
    }

    public function compareVersions(Request $request, VAPFile $file, string $older, string $newer)
    {
        $this->authorizeRead($request, $file);
        abort_unless($file->type === 'file', 404);

        $versions = $file->versions()->whereKey([$older, $newer])->get()->keyBy('id');
        abort_unless($older !== $newer && $versions->has($older) && $versions->has($newer), 404);

        $readText = function (VAPFileVersion $version): string {
            $mimeType = (string) $version->mime_type;
            abort_unless(str_starts_with($mimeType, 'text/') || in_array($mimeType, ['application/json', 'application/xml', 'application/javascript'], true), 415);
            abort_unless($version->content && Storage::exists($version->content), 404);
            abort_if((int) $version->size > 1048576 || Storage::size($version->content) > 1048576, 413);

            $content = Storage::get($version->content);
            abort_unless(mb_check_encoding($content, 'UTF-8'), 422);

            return $content;
        };

        return response()->json([
            'older' => [
                'id' => $older,
                'revision_code' => $versions[$older]->revision_code,
                'text' => $readText($versions[$older]),
            ],
            'newer' => [
                'id' => $newer,
                'revision_code' => $versions[$newer]->revision_code,
                'text' => $readText($versions[$newer]),
            ],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function restoreVersion(Request $request, VAPFile $file, string $version)
    {
        $this->authorizeWrite($request, $file);

        $restoredVersion = $file->versions()->with('creator')->findOrFail($version);

        DB::transaction(function () use ($file, $restoredVersion, $request) {
            $revisionCode = $this->nextRevisionCode($file);

            VAPFileVersion::create([
                'file_id' => $file->id,
                'revision_code' => $revisionCode,
                'content' => $restoredVersion->content,
                'mime_type' => $restoredVersion->mime_type,
                'size' => $restoredVersion->size,
                'checksum' => $restoredVersion->checksum,
                'created_by' => $request->user()->id,
                'comment' => 'Versão restaurada a partir da revisão '.$restoredVersion->revision_code,
                'change_reason' => $request->input('change_reason', 'Versão restaurada para uso controlado'),
            ]);

            $file->update([
                'content' => $restoredVersion->content,
                'mime_type' => $restoredVersion->mime_type,
                'size' => $restoredVersion->size,
                'modified_at' => now(),
                'revision_code' => $revisionCode,
                'change_reason' => $request->input('change_reason', 'Versão restaurada para uso controlado'),
                // Like any new revision, a restored one is a draft until it is approved again.
                'status' => 'draft',
                'effective_at' => null,
                'approved_at' => null,
                'approved_by' => null,
            ]);
        });

        $this->logDocumentEvent($file, 'version_restored', 'Versão documental restaurada.', [
            'restored_from' => $restoredVersion->revision_code,
        ]);

        return new VAPFileResource($file->fresh(['versions.creator', 'permissions.user', 'creator', 'owner']));
    }

    public function download(Request $request, VAPFile $file)
    {
        $this->authorizeRead($request, $file);

        if ($file->type !== 'file' || ! $file->content) {
            return response()->json([
                'message' => trans('gestlab.general.labels.vap_filemanager.notifications.error_file_not_found'),
            ], 404);
        }

        $this->logDocumentEvent($file, 'downloaded', 'Documento descarregado.');

        return Storage::download($file->content, $file->name);
    }

    public function updateMetadata(Request $request, VAPFile $file)
    {
        $this->authorizeWrite($request, $file);

        $validated = $request->validate($this->documentMetadataRules());

        $file->update(array_merge(
            $this->buildMetadataPayload($validated, $request),
            ['modified_at' => now()]
        ));

        $this->logDocumentEvent($file, 'metadata_updated', 'Metadados documentais actualizados.', [
            'change_reason' => $validated['change_reason'] ?? null,
        ]);

        return new VAPFileResource($file->fresh([
            'versions.creator',
            'permissions.user',
            'shares.sharedWithUser',
            'tags',
            'creator',
            'owner',
            'approver',
        ]));
    }

    public function submitReview(Request $request, VAPFile $file)
    {
        $this->authorizeWrite($request, $file);

        $validated = $request->validate([
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id'),
                Rule::exists('lab_user', 'user_id')->where('lab_id', $file->lab_id),
            ],
            'due_date' => 'nullable|date',
            'change_reason' => 'required|string|max:1000',
        ]);

        DB::transaction(function () use ($file, $validated) {
            $file->update([
                'status' => 'in_review',
                'change_reason' => $validated['change_reason'],
                'modified_at' => now(),
            ]);

            if (! empty($validated['assigned_to'])) {
                WorkflowTask::query()->create([
                    'file_id' => $file->id,
                    'type' => 'review',
                    'assigned_to' => $validated['assigned_to'],
                    'status' => 'pending',
                    'due_date' => $validated['due_date'] ?? null,
                ]);
            }

            $this->logDocumentEvent($file, 'submitted_for_review', 'Documento submetido para revisão.', [
                'assigned_to' => $validated['assigned_to'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'change_reason' => $validated['change_reason'],
            ]);
        });

        return new VAPFileResource($file->fresh(['permissions.user', 'creator', 'owner', 'approver']));
    }

    public function approve(Request $request, VAPFile $file)
    {
        $this->authorizeApprove($request, $file);

        $validated = $request->validate([
            'effective_at' => 'nullable|date',
            'review_due_at' => 'nullable|date|after_or_equal:effective_at',
            'change_reason' => 'required|string|max:1000',
        ]);

        $effectiveAt = isset($validated['effective_at']) ? Carbon::parse($validated['effective_at']) : now();

        DB::transaction(function () use ($file, $validated, $request, $effectiveAt) {
            $status = $effectiveAt->isFuture() ? 'approved' : 'effective';

            $file->update([
                'status' => $status,
                'approved_at' => now(),
                'approved_by' => $request->user()->id,
                'effective_at' => $effectiveAt,
                'review_due_at' => $validated['review_due_at'] ?? $file->review_due_at,
                'change_reason' => $validated['change_reason'],
                'modified_at' => now(),
            ]);

            WorkflowTask::query()
                ->where('file_id', $file->id)
                ->whereIn('type', ['review', 'approve', 'publish'])
                ->whereIn('status', ['pending', 'in_progress'])
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

            $this->logDocumentEvent($file, 'approved', 'Documento aprovado para uso controlado.', [
                'effective_at' => $effectiveAt?->toDateTimeString(),
                'review_due_at' => $validated['review_due_at'] ?? null,
                'change_reason' => $validated['change_reason'],
            ]);
        });

        return new VAPFileResource($file->fresh(['permissions.user', 'creator', 'owner', 'approver']));
    }

    public function markObsolete(Request $request, VAPFile $file)
    {
        $this->authorizeApprove($request, $file);

        $validated = $request->validate([
            'superseded_by' => ['nullable', Rule::exists('v_files', 'id')->where('lab_id', $file->lab_id)],
            'change_reason' => 'required|string|max:1000',
        ]);

        if (! empty($validated['superseded_by']) && $validated['superseded_by'] === $file->id) {
            return response()->json([
                'message' => 'Um documento não pode substituir a si mesmo.',
            ], 422);
        }

        if (! empty($validated['superseded_by'])) {
            $this->authorizeRead($request, VAPFile::query()->findOrFail($validated['superseded_by']));
        }

        $file->update([
            'status' => 'obsolete',
            'obsolete_at' => now(),
            'superseded_by' => $validated['superseded_by'] ?? null,
            'change_reason' => $validated['change_reason'],
            'modified_at' => now(),
        ]);

        $this->logDocumentEvent($file, 'obsolete', 'Documento marcado como obsoleto.', [
            'superseded_by' => $validated['superseded_by'] ?? null,
            'change_reason' => $validated['change_reason'],
        ]);

        return new VAPFileResource($file->fresh(['permissions.user', 'creator', 'owner', 'approver', 'supersededBy']));
    }

    public function search(Request $request)
    {
        $this->laboratory->activeLabId();
        $validated = $request->validate([
            'query' => 'nullable|string|max:255',
        ]);

        $query = trim((string) ($validated['query'] ?? ''));

        if ($query === '') {
            return response()->json([]);
        }

        $files = VAPFile::query()
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', '%'.$query.'%')
                    ->orWhere('document_number', 'like', '%'.$query.'%')
                    ->orWhere('category', 'like', '%'.$query.'%')
                    ->orWhere('document_type', 'like', '%'.$query.'%')
                    ->orWhereHas('tags', function ($tagQuery) use ($query) {
                        $tagQuery->where('name', 'like', '%'.$query.'%');
                    });
            })
            ->tap(fn ($builder) => $this->scopeAccessibleFiles($builder, $request->user()))
            ->where('archived', false)
            ->with(['permissions.user', 'tags', 'creator', 'owner', 'approver'])
            ->orderByRaw('CASE 
                WHEN name = ? THEN 1
                WHEN document_number = ? THEN 2
                WHEN name LIKE ? THEN 3
                ELSE 4
            END', [$query, $query, $query.'%'])
            ->orderBy('modified_at', 'desc')
            ->get();

        return response()->json(VAPFileResource::collection($files)->resolve());
    }

    public function getBreadcrumbs(Request $request, $folderId = null)
    {
        $this->laboratory->activeLabId();
        $breadcrumbs = [];
        $currentId = $folderId;

        while ($currentId) {
            $folder = VAPFile::query()->find($currentId);
            if (! $folder) {
                break;
            }

            $this->authorizeRead($request, $folder);

            array_unshift($breadcrumbs, [
                'id' => $folder->id,
                'name' => $folder->name,
            ]);

            $currentId = $folder->parent_id;
        }

        return response()->json($breadcrumbs);
    }

    public function getFolders(Request $request)
    {
        $query = VAPFile::query()->where('type', 'folder');
        $this->scopeAccessibleFiles($query, $request->user());

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->q.'%');
        }

        return response()->json($query->orderBy('name')->get(['id', 'name', 'parent_id', 'type']));
    }

    private function authorizeRead(Request $request, VAPFile $file): void
    {
        $this->authorizeOwnedLaboratory($file);
        abort_unless($file->canBeReadBy($request->user()), 403);
    }

    private function authorizeWrite(Request $request, VAPFile $file): void
    {
        $this->authorizeOwnedLaboratory($file);
        abort_unless($file->canBeWrittenBy($request->user()), 403);
    }

    private function authorizeApprove(Request $request, VAPFile $file): void
    {
        $this->authorizeOwnedLaboratory($file);
        abort_unless($file->canBeApprovedBy($request->user()), 403);
    }

    private function authorizeOwnedLaboratory(VAPFile $file): void
    {
        abort_unless((int) $file->lab_id === $this->laboratory->activeLabId(), 404);
    }

    private function scopeAccessibleFiles($query, $user): void
    {
        $query->where('lab_id', $this->laboratory->activeLabId());

        if (! $user) {
            $query->whereRaw('1 = 0');

            return;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return;
        }

        $query->where(function ($scopedQuery) use ($user) {
            $scopedQuery->where('created_by', $user->id)
                ->orWhereHas('permissions', function ($permissionQuery) use ($user) {
                    $permissionQuery->where('user_id', $user->id);
                });
        });
    }

    /**
     * What a new revision changes on its document. The document keeps its
     * number, type and the rest of its control data unless the upload states
     * them again. The revision itself is a draft: not yet effective, and
     * without the approval given to the revision before it.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function revisionMetadataPayload(array $validated): array
    {
        $stated = array_intersect_key($validated, array_flip([
            'document_number', 'document_type', 'category', 'confidentiality_level', 'is_controlled',
            'requires_periodic_review', 'retention_period_days', 'review_due_at', 'owner_id', 'meta',
        ]));

        return array_merge($stated, [
            'status' => $validated['status'] ?? 'draft',
            'effective_at' => $validated['effective_at'] ?? null,
            'approved_at' => null,
            'approved_by' => null,
            'change_reason' => $validated['change_reason'] ?? null,
        ]);
    }

    private function buildMetadataPayload(array $validated, Request $request, bool $defaultDraft = true): array
    {
        return [
            'document_number' => $validated['document_number'] ?? null,
            'document_type' => $validated['document_type'] ?? null,
            'category' => $validated['category'] ?? null,
            'status' => $validated['status'] ?? ($defaultDraft ? 'draft' : 'effective'),
            'confidentiality_level' => $validated['confidentiality_level'] ?? 'internal',
            'is_controlled' => $validated['is_controlled'] ?? true,
            'requires_periodic_review' => $validated['requires_periodic_review'] ?? true,
            'retention_period_days' => $validated['retention_period_days'] ?? null,
            'effective_at' => $validated['effective_at'] ?? null,
            'review_due_at' => $validated['review_due_at'] ?? null,
            'owner_id' => $validated['owner_id'] ?? $request->user()?->id,
            'change_reason' => $validated['change_reason'] ?? null,
            'meta' => $validated['meta'] ?? [],
        ];
    }

    private function documentMetadataRules(bool $includeStatus = true): array
    {
        $rules = [
            'document_number' => 'nullable|string|max:255',
            'document_type' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'confidentiality_level' => 'nullable|in:public,internal,confidential,restricted',
            'is_controlled' => 'nullable|boolean',
            'requires_periodic_review' => 'nullable|boolean',
            'retention_period_days' => 'nullable|integer|min:1|max:3650',
            'effective_at' => 'nullable|date',
            'review_due_at' => 'nullable|date|after_or_equal:effective_at',
            'owner_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
                Rule::exists('lab_user', 'user_id')->where('lab_id', $this->laboratory->activeLabId()),
            ],
            'change_reason' => 'nullable|string|max:1000',
            'meta' => 'nullable|array',
        ];

        if ($includeStatus) {
            $rules['status'] = 'nullable|in:draft,in_review,approved,effective,obsolete,archived';
        }

        return $rules;
    }

    private function nextRevisionCode(VAPFile $file): string
    {
        $nextNumber = $file->versions()->count() + 1;

        return 'R'.str_pad((string) $nextNumber, 2, '0', STR_PAD_LEFT);
    }

    private function storeUploadedFile($uploadedFile, string $fileName): string
    {
        $storagePath = 'files/'.Str::uuid().'/'.$fileName;
        Storage::put($storagePath, file_get_contents($uploadedFile->getRealPath()));

        return $storagePath;
    }

    private function logDocumentEvent(VAPFile $file, string $event, string $description, array $properties = []): void
    {
        activity('document_control')
            ->event($event)
            ->withProperties(array_merge([
                'file_id' => $file->id,
                'file_name' => $file->name,
                'document_number' => $file->document_number,
                'revision_code' => $file->revision_code,
                'status' => $file->status,
            ], $properties))
            ->log($description);
    }
}
