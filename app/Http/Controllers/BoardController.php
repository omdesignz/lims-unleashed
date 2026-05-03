<?php

namespace App\Http\Controllers;

use App\Http\Resources\BoardResource;
use App\Models\Board;
use App\Models\Card;
use Inertia\Inertia;

class BoardController extends Controller
{
    public function index()
    {
        return Inertia::render('Boards/Index', [
            'record' => BoardResource::collection(
                Board::query()
                            ->with('lists')
                            ->where('user_id', auth()->id())
                            ->when(request()->input('search'), function($query, $search){
                                $query->where('name', 'like', "%{$search}%");
                            })
                            ->latest()
                            ->paginate(10)
                            ->withQueryString()
                        ),
            'slideOverEdit' => true,            
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.kanban.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.kanban.description'),
                    'value' => 'description'
                ],
                [
                    'name' => trans('gestlab.general.labels.created_at'),
                    'value' => 'created_at'
                ],
            ],
            'model' => Board::MENU_NAME,
            'abilities' => method_exists(Board::class, 'getAbilities') ? collect(Board::ABILITIES)->map(function($item){
                return $item . '_' . Board::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . Board::MENU_NAME;
            }),                           
            'query' => request()->only(['search', 'filter'])
        ]);
    }

    public function show(Board $board, Card $card = null)
    {
        $board->load([
            'lists.cards' => fn($query) => $query->orderBy('position')
        ]);

        return Inertia::render('Boards/Show', [ 
            'board' => $board,
            'card' => $card,
        ]);
    }

    public function update(Board $board)
    {
        request()->validate([
            'name' => ['required', 'max:255']
        ]);

        $board->update([
            'name' => request('name'),
            'description' => request('description'),
            'bgcolor' => request('bgcolor'),
            'iconcolor' => request('iconcolor'),
            'icon' => request('icon'),
        ]);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ]
        ]);
    }

    public function store()
    {
        
        request()->validate([
            'name' => ['required'],
            'bgcolor' => ['required'],
            'icon' => ['required'],
            'iconcolor' => ['required'],
        ]);

        Board::create([
            'user_id' => auth()->id(),
            'name' => request('name'),
            'description' => request('description'),
            'bgcolor' => request('bgcolor'),
            'iconcolor' => request('iconcolor'),
            'icon' => request('icon'),
        ]);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ]
        ]);
    }

    public function destroy()
    {
        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
 
        foreach (Board::find(request('recordIds')) as $record) {
            $record->delete();
        }
        
        return to_route('boards')->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ]
        ]);
    }
}
