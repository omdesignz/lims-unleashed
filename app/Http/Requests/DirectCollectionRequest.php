<?php

namespace App\Http\Requests;

class DirectCollectionRequest extends CollectionAccessionRequest
{
    protected function collectionType(): string
    {
        return 'direct';
    }
}
