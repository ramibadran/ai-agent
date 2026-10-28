<?php

namespace App\Contracts;

interface AnswerGenerator
{
    /**
     * Generate an answer grounded only in supplied knowledge context.
     *
     * @param  list<array{point_id: string|int, content_id: int, chunk_number: int, title: string, source_url: string|null, text: string, score: float}>  $context
     * @return array{answer: string, model: string, prompt_tokens: int|null, completion_tokens: int|null, total_tokens: int|null, latency_ms: int, cost_usd: string}
     */
    public function generate(string $question, array $context): array;
}
