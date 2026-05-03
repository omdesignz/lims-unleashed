<?php

namespace App\Enums\Orders;

enum InventoryItemStatus: string
{
    case ON_HAND = 'ON_HAND';
    case IN_TRANSIT = 'IN_TRANSIT';
    case AVAILABLE = 'AVAILABLE';
    case UNAVAILABLE = 'UNAVAILABLE';
    case SOLD = 'SOLD';
    case EXPIRED = 'EXPIRED';
    case LOST = 'LOST';
    case MISSING = 'MISSING';
    case RETURNED = 'RETURNED';
    case CANCELLED = 'CANCELLED';
    case RECEIVED = 'RECEIVED';
    case PICKED_UP = 'PICKED_UP';
    case DELIVERED = 'DELIVERED';
    case SHIPPED = 'SHIPPED';
    case COMMITED = 'COMMITED';
    case INCOMING = 'INCOMING';
    case OUTGOING = 'OUTGOING';
    case RESTRICTED = 'RESTRICTED';
    case ON_HOLD = 'ON_HOLD';
    case REJECTED = 'REJECTED';
    case OPEN = 'OPEN';
    case CLOSED = 'CLOSED';
    case UNDER_MAINTENANCE = 'UNDER_MAINTENANCE';
    case NEEDS_CALIBRATION = 'NEEDS_CALIBRATION';
    case NEEDS_REPAIR = 'NEEDS_REPAIR';
}