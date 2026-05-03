<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, string>  $input
     */
    public function update(User $user, array $input): void
    {
        // dd($input);

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'numeric'],
            'dob' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:1'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ],[],['name' => 'nome', 'email' => 'endereço de email', 'username' => 'usuário', 'id_number' => 'contribuinte', 'primary_phone' => 'contacto', 'dob' => 'data de nascimento', 'gender' => 'gênero sexual']
        )->validateWithBag('updateProfileInformation');

        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
                // 'username' => $input['username'],
                // 'id_number' => $input['id_number'],
                // 'phone' => $input['phone'],
                // 'dob' => $input['dob'],
                // 'gender' => $input['gender'],
                // 'photo' => $input['photo'],
            ])->save();

            if($input['photo']){

                $user->addMedia($input['photo']) //starting method
                    ->withCustomProperties(['mime-type' => 'image/jpeg']) //middle method
                    ->preservingOriginal() //middle method
                    ->toMediaCollection('avatar'); //finishing method
            }
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
