<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\CardList;

class CardListController extends Controller
{
    public function store(Board $board)
    {
        request()->validate([
            'name' => ['required']
        ]);

        CardList::create([
            'board_id' => $board->id,
            'user_id' => auth()->id(),
            'name' => request('name')
        ]);

        return redirect()->back();
    }

    public function destroy(Board $board, CardList $list)
    {
        // dd($board);

        $list->delete();
        // return redirect()->route('boards.show', ['board' => $list->board_id]);

        return to_route('boards.show', ['board' => $list->board_id])->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ]
        ]);
    }
}