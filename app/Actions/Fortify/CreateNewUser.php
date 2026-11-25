<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'block_id' => ['required', 'integer', 'exists:blocks,id'],
            'room' => ['required', 'string', 'max:50'],
        ])->validate();

        // Public registration is for residents only. 'role' is not fillable and is
        // never read from the form; the users.role column defaults to 'resident'.
        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'block_id' => $input['block_id'],
            'room' => trim($input['room']),
        ]);
    }
}
