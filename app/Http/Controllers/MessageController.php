<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\MessageRequest;
use App\Http\Resources\MessageResource;
use Illuminate\Support\Facades\DB;
use App\Models\Attachment;
use App\Models\Message;
use App\Models\User;
use App\Notifications\MessageReceivedNotification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MessageController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if(!auth()->user()->can('view_' . Message::MENU_NAME), 403, '');

        return Inertia::render('Messages/Index', [
            'record' => MessageResource::collection(
                Message::query()
                    ->with('attachments', 'sender', 'receiver')
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('message', 'like', "%{$search}%");
                    })
                    ->when(request()->input('filter'), function ($query, $filter) {
                        if ($filter === 'trashed') {
                            $query->withTrashed();
                        }
                    })
                    ->latest()
                    ->paginate(10)
                    ->withQueryString()
            ),
            'slideOverEdit' => false,
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.messages.sender_id'),
                    'value' => 'sender'
                ],
                [
                    'name' => trans('gestlab.general.labels.messages.receiver_id'),
                    'value' => 'receiver'
                ],
                [
                    'name' => trans('gestlab.general.labels.messages.message'),
                    'value' => 'message'
                ],
                [
                    'name' => trans('gestlab.general.labels.messages.attachments'),
                    'value' => 'attachments'
                ],
            ],
            'model' => Message::MENU_NAME,
            'abilities' => method_exists(Message::class, 'getAbilities') ? collect(Message::ABILITIES)->map(function ($item) {
                return $item . '_' . Message::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item . '_' . Message::MENU_NAME;
            }),
            'query' => request()->only(['search', 'filter'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        abort_if(!auth()->user()->can('add_' . Message::MENU_NAME), 403, '');

        return Inertia::render('Messages/Create', [
            'receivers' => User::query()->select('id', 'name', 'email')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(MessageRequest $request)
    {
        abort_if(!auth()->user()->can('add_' . Message::MENU_NAME), 403, '');

        DB::transaction(function () use ($request): void {
            $record = Message::create($request->safe()->except(['attachments']));

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('attachments');
                    $fileType = $file->getClientMimeType();
                    Attachment::create([
                        'message_id' => $record->id,
                        'file_path' => $path,
                        'file_type' => $fileType,
                    ]);
                }
            }

            $receiver = User::query()->find($request->receiver_id);

            if ($receiver) {
                $receiver->notify(new MessageReceivedNotification($record));
            }
        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ]
        ]);
    }

    /**
     * Display the specified resource.
     *
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     */
    public function edit($id)
    {
        abort_if(!auth()->user()->can('edit_' . Message::MENU_NAME), 403, '');

        // Find the record
        $record = Message::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Messages/Edit', [
            'record' => MessageResource::make($record),
            'receivers' => User::query()->select('id', 'name', 'email')->orderBy('name')->get(),
            'attachments' => collect($record->attachments)->map(function ($item) {
                return [
                    'id' => $item->id ?? null,
                    'message_id' => $item->message_id ?? null,
                    'file_path' => $item->file_path,
                    'file_type' => $item->file_type,
                ];
            })
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(MessageRequest $request, $id)
    {
        abort_if(!auth()->user()->can('edit_' . Message::MENU_NAME), 403, '');

        DB::transaction(function () use ($request, $id): void {

            tap(Message::findOrFail($id), function ($record) use ($request) {

                $record->update($request->validated());

                if ($request->hasFile('attachments')) {

                    // Delete old attachments if any
                    foreach ($record->attachments as $attachment) {
                        Storage::delete($attachment->file_path);
                        $attachment->delete();
                    }

                    foreach ($request->file('attachments') as $file) {

                        $path = $file->store('attachments');
                        $fileType = $file->getClientMimeType();

                        Attachment::create([
                            'message_id' => $record->id,
                            'file_path' => $path,
                            'file_type' => $fileType,
                        ]);
                    }
                }
            });
        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     */
    public function destroy()
    {
        abort_if(!auth()->user()->can('delete_' . Message::MENU_NAME), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Message::withTrashed()->findOrFail(request('recordIds')) as $record) {

            foreach ($record->attachments as $attachment) {
                Storage::delete($attachment->file_path);
                $attachment->delete();
            }

            $record->delete();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ]
        ]);
    }

    /**
     * restore the specified resource from storage.
     *
     */
    public function restore()
    {
        abort_if(!auth()->user()->can('restore_' . Message::MENU_NAME), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Message::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
        ]);
    }


    public function getMessage()
    {
        $data = [];

        if (request()->has('q')) {
            $search = request()->q;

            $data = DB::table("messages")
                ->select('messages.*')
                ->where('message', 'LIKE', "%$search%")
                ->get();
        }

        return response()->json($data);
    }

    public function markAsRead($id)
    {
        $message = Message::where('id', $id)->where('receiver_id', auth()->id())->firstOrFail();
        $message->update(['read_at' => now()]);

        return redirect()->back();
    }
}
