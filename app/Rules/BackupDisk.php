<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class BackupDisk implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //

        $configuredBackupDisks = config('backup.backup.destination.disks');

        $result = in_array($value, $configuredBackupDisks);

        ! $result ? $fail('O disco indicado não é válido.') : '';
    }

    public function message()
    {
        return 'Este disco não está configurado para cópias de segurança.';
    }
}
