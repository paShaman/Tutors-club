<?php

declare(strict_types=1);

namespace App\Services;

interface SocialOAuthProvider
{
    public function key(): string;

    public function configured(): bool;

    public function authorizeUrl(string $state): ?string;

    public function profileFromCode(string $code): ?array;
}
