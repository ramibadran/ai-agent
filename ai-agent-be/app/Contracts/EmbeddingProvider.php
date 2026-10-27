<?php

namespace App\Contracts;

interface EmbeddingProvider
{
    /**
     * @param  array<int, string>  $inputs
     * @return array<int, array<int, float>>
     */
    public function embed(array $inputs): array;

    public function dimensions(): int;
}
