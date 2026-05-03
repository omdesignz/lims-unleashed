<?php

namespace App\Enums\Proposals;

enum ProposalTrackingStatus: string
{
    case PENDING = 'PENDING';
    case SENT = 'SENT';
    case ACCEPTED = 'ACCEPTED';
    case REJECTED = 'REJECTED';
    case EXPIRED = 'EXPIRED';
    case CANCELLED = 'CANCELLED';
}

