<?php

namespace App\Enums\Orders;

enum InventoryOrderTrackingStatus: string
{
    case PLACED = 'PLACED';
    case ORDERED = 'ORDERED';
    case PROCESS_IN_PROGRESS = 'PROCESS_IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case DELIVERED = 'DELIVERED';
    case CANCELLED = 'CANCELLED';
    case RECEIVED = 'RECEIVED';
    case REJECTED = 'REJECTED';
    case APPROVED = 'APPROVED';
    case CANCELLED_BY_SUPPLIER = 'CANCELLED_BY_SUPPLIER';
    case CANCELLED_BY_USER = 'CANCELLED_BY_USER';
    case PARTIALLY_RECEIVED = 'PARTIALLY_RECEIVED';
    case PENDING = 'PENDING';
    case DELAYED = 'DELAYED';
}