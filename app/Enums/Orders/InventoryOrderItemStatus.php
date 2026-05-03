<?php

namespace App\Enums\Orders;

enum InventoryOrderItemStatus: string
{
    case ORDERED = 'ORDERED';
    case ACCEPTABLE = 'ACCEPTABLE';
    case NOT_ACCEPTABLE = 'NOT_ACCEPTABLE';
    case PARTIALLY_RECEIVED = 'PARTIALLY_RECEIVED';
    case RECEIVED = 'RECEIVED';
    case CANCELLED = 'CANCELLED';
    case REJECTED = 'REJECTED';
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case CANCELLED_BY_SUPPLIER = 'CANCELLED_BY_SUPPLIER';
    case CANCELLED_BY_USER = 'CANCELLED_BY_USER';
    case DELAYED = 'DELAYED';
}