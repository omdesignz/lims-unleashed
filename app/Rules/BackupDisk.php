<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BackupDisk implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //

        $configuredBackupDisks = config('backup.backup.destination.disks');

        $result = in_array($value, $configuredBackupDisks);

        !$result ? $fail('The disk is invalid.') : '';
    }

    public function message()
    {
        return 'This disk is not configured as a backup disk.';
    }
}
