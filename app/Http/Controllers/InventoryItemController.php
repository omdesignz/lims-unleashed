<?php

namespace App\Http\Controllers;

use App\Actions\SetInventoryDocumentArchived;
use App\Actions\SetInventoryItemsArchived;
use App\Enums\InventoryCategoryType;
use App\Http\Requests\InventoryCatalogueLookupRequest;
use App\Http\Requests\SetInventoryItemsArchivedRequest;
use App\Http\Resources\InventoryCatalogueOptionResource;
use App\Http\Resources\MaintenanceTaskResource;
use App\Models\InventoryItem;
use App\Models\MaintenanceTask;
use App\Services\InventoryCatalogueAccess;
use App\Services\InventoryCatalogueLookup;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\MediaStream;

class InventoryItemController extends Controller
{
    public function __construct(
        private readonly SampleLaboratoryAccess $laboratoryAccess,
        private readonly InventoryCatalogueAccess $catalogueAccess,
    ) {}

    private function ownedItem(int $id, string $ability): InventoryItem
    {
        $item = InventoryItem::forLaboratory($this->laboratoryAccess->activeLabId())
            ->whereHas('category', fn ($category) => $category->withTrashed()->where('inventory_type', 'material'))->findOrFail($id);
        $this->catalogueAccess->authorize(request()->user(), $item, $ability);

        return $item;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): RedirectResponse
    {
        abort_unless(request()->user()->can('view_iitems'), 403);

        return redirect()->route('vap-inventory.items.index', ['inventory_type' => 'material', 'search' => request()->input('search')]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): RedirectResponse
    {
        abort_unless(! request()->session()->has('impersonate') && request()->user()->can('add_iitems'), 403);

        return redirect()->route('vap-inventory.items.create', ['inventory_type' => 'material']);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): RedirectResponse
    {
        $item = $this->ownedItem($id, 'view');

        return redirect()->route('vap-inventory.items.show', $item);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): RedirectResponse
    {
        abort_if(request()->session()->has('impersonate'), 403);
        $item = $this->ownedItem($id, 'edit');

        return redirect()->route('vap-inventory.items.edit', $item);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SetInventoryItemsArchivedRequest $request, SetInventoryItemsArchived $archive): RedirectResponse
    {
        $archive->execute($request->user()->id, $this->laboratoryAccess->activeLabId(), $request->validated('recordIds'), true, InventoryCategoryType::MATERIAL);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ],
        ]);
    }

    /**
     * restore the specified resource from storage.
     */
    public function restore(SetInventoryItemsArchivedRequest $request, SetInventoryItemsArchived $archive): RedirectResponse
    {
        $archive->execute($request->user()->id, $this->laboratoryAccess->activeLabId(), $request->validated('recordIds'), false, InventoryCategoryType::MATERIAL);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    public function getInventoryItem(InventoryCatalogueLookupRequest $request, InventoryCatalogueLookup $lookup): JsonResponse
    {
        $items = $lookup->search($this->laboratoryAccess->activeLabId(), $request->user(), $request->validated('q') ?? '', InventoryCategoryType::MATERIAL);

        return response()->json(InventoryCatalogueOptionResource::collection($items)->resolve($request));
    }

    public function getReagentInventoryItem(InventoryCatalogueLookupRequest $request, InventoryCatalogueLookup $lookup): JsonResponse
    {
        $items = $lookup->search($this->laboratoryAccess->activeLabId(), $request->user(), $request->validated('q') ?? '', InventoryCategoryType::MATERIAL, true);

        return response()->json(InventoryCatalogueOptionResource::collection($items)->resolve($request));
    }

    public function downloadallattachments(): MediaStream
    {
        // Get all Docs
        $documents = $this->ownedItem(request()->integer('model_id'), 'view')->getMedia('documents');

        return MediaStream::create('documents.zip')->addMedia($documents);
    }

    public function downloadsingleattachment(): Media
    {
        $media = Media::findOrFail(request()->integer('model_id'));
        abort_unless($media->model_type === (new InventoryItem)->getMorphClass() && $media->collection_name === 'documents', 404);
        $this->ownedItem((int) $media->model_id, 'view');

        return $media;
    }

    public function deleteattachment(SetInventoryDocumentArchived $archiveDocument): RedirectResponse
    {
        $item = $this->ownedItem(request()->integer('model_id'), 'edit');
        $archiveDocument->execute($this->laboratoryAccess->activeLabId(), request()->user()->id, $item->id, request()->integer('id'), true);

        return redirect()->back();
    }

    public function getMaintenanceTasks(int $id): AnonymousResourceCollection
    {
        $item = InventoryItem::forLaboratory($this->laboratoryAccess->activeLabId())->findOrFail($id);
        $this->catalogueAccess->authorize(request()->user(), $item, 'view');
        abort_unless(request()->user()->can('view_maintenance_tasks'), 403);

        return MaintenanceTaskResource::collection(
            MaintenanceTask::query()
                ->with(['equipment', 'category', 'supplier'])
                ->where('equipment_id', $id)
                ->latest()
                ->paginate(10)
                ->withQueryString()
        );
    }

    /**
     * Export all inventory items to an Excel file.
     *
     * @return Response
     */
    public function exportInventory(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('export_iitems'), 403);

        return redirect()->route('vap-inventory.items.export.inventory',
            $request->only(['start', 'end', 'category_id']) + ['inventory_type' => 'material']);
    }
}
