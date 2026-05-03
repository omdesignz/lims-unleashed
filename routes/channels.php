<?php

use App\Models\InventoryOrder;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('users.{id}', function (User $user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('orders.{orderId}', function (User $user, $orderId) {

    if ($user->id !== InventoryOrder::findOrNew($orderId)->user_id) {
        return false;
    }

    return true;
});

Broadcast::channel('things', function ($user) {
    return true;
}); 

Broadcast::channel('inventory', function ($user) {
    return $user !== null && $user->can('view_inventory');
});
