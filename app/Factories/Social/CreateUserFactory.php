<?php

namespace App\Factories\Social;

use App\Actions\Social\Contracts\CreatesUser;
use App\Actions\Social\CreateAzureUser;
use App\Actions\Social\CreateGithubUser;
use App\Actions\Social\CreateGoogleUser;
use InvalidArgumentException;

class CreateUserFactory
{
    public function forService(string $service): CreatesUser
    {
        return match ($service) {
            'google' => app(CreateGoogleUser::class),
            'github' => app(CreateGithubUser::class),
            'microsoft' => app(CreateAzureUser::class),
            default => throw new InvalidArgumentException("Unsupported social provider [{$service}]."),
        };
    }
}
