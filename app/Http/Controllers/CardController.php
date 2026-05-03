<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\CardMember;

class CardController extends Controller
{
    public function store()
    {
        request()->validate([
            'board_id' => ['required', 'exists:boards,id'],
            'card_list_id' => ['required', 'exists:card_lists,id'],
            'title' => ['required']
        ]);

        Card::create([
            'board_id' => request('board_id'),
            'card_list_id' => request('card_list_id'),
            'title' => request('title'),
            'user_id' => auth()->id()
        ]);

        return redirect()->back();
    }

    public function update(Card $card)
    {

        // dd(request()->all());
        request()->validate([
            'title' => ['required']
        ]);

        $card->update([
            'title' => request('title'),
            'description' => request()->has('description') ? request('description') : $card->description,
        ]);

        CardMember::where('card_id', $card->id)->forcedelete();

        // Check if there are card members and add them
        if(request()->has('members') && count(request('members')) > 0){

            // CardMember::where('card_id', $card->id)->forcedelete();

            foreach(request('members') as $member) {
           
                $record = new CardMember;

                $record->user_id = $member['user_id']['value'];
                $record->card_id = $card->id;
                $record->is_responsible = $member['is_responsible'];

                $record->save();      
            }
        }
        

        if (request()->has('redirectUrl')) {
            return redirect(request('redirectUrl'));
        }

        return redirect()->back();
    }

    public function move(Card $card)
    {
        request()->validate([
            'cardListId' => ['required', 'exists:card_lists,id'],
            'position' => ['required', 'numeric']
        ]);

        $card->update([
            'card_list_id' => request('cardListId'),
            'position' => round(request('position'), 5)
        ]);

        return redirect()->back();
    }

    public function destroy(Card $card)
    {
        $card->delete();

        return to_route('boards.show', ['board' => $card->board_id])->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ]
        ]);
    }
}