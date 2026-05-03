<?php

namespace App\Enums\Collections;

enum CollectionProductTrackingStatus: string
{
    case COLLECTED = 'Collected';
    case PENDING_ANALYSIS = 'Pending analysis';
    case PLACED_ANALYSIS = 'Placed analysis';
    case ANALYSIS_IN_PROGRESS = 'Analysis in progress';
    case ANALYSIS_COMPLETED = 'Analysis completed';
}

