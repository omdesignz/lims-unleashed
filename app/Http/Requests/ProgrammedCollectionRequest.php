<?php

namespace App\Http\Requests;

class ProgrammedCollectionRequest extends CollectionAccessionRequest
{
    protected function collectionType(): string
    {
        return 'programmed';
    }
}
