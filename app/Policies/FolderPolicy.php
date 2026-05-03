<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Folder;

class FolderPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function view(User $user, Folder $folder)
    {
        // Check if the user owns the folder or has been granted permission
        return $user->id === $folder->user_id || $folder->sharedWithUsers()->contains($user->id);
    }

    public function share(User $user, Folder $folder)
    {
        return $user->id === $folder->user_id;
    }
}
