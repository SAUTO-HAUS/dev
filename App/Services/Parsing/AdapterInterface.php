<?php

namespace App\Services\Parsing;

interface AdapterInterface
{
    public function getSourceCode(): string;

    public function getSourceName(): string;

    public function searchByFilter(array $criteria): array;

    public function fetchByUrl(string $url): ?array;

    public function fetchById(string $sourceId): ?array;

    public function checkAvailability(string $sourceId): bool;

    public function detectsUrl(string $url): bool;
}
