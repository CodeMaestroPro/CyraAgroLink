<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Optional OpenAI-compatible chat advisor for open-ended farming questions.
 */
class LlmFarmingAdvisor
{
    public function enabled(): bool
    {
        $provider = (string) config('ai.provider', 'local');
        $key = (string) config('ai.api_key', '');

        return in_array($provider, ['openai', 'llm'], true) && $key !== '';
    }

    /**
     * Ask the remote model; returns null on any failure so local engine can answer.
     */
    public function answer(string $prompt, string $farmerName): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        $system = <<<'PROMPT'
You are CyraAI, the farming advisor for CyraAgroLink (Nigeria / West Africa focus).

CRITICAL RULE: Answer the farmer's EXACT individual question only. Do NOT give a generic crop/livestock overview, topic dump, or unrelated tips.
- If they ask planting time → give seasonal timing for that crop/zone only.
- If they ask fertilizer → give rates/products for that crop only.
- If they ask feeding → give ration guidance for that animal only.
- If they ask disease/pest control → give control steps for that problem only.
Never pad with “practical next steps” boilerplate or invite them to ask something else unless a critical detail (crop/animal/location) is missing.
Be accurate and practical (days, rates, steps). Mention label/vet caveats for chemicals or drugs.
If the question is not about farming, say briefly that you only handle farming questions.
Keep answers concise (about 80–180 words), plain language.
PROMPT;

        try {
            $response = Http::withToken((string) config('ai.api_key'))
                ->timeout((int) config('ai.timeout', 35))
                ->acceptJson()
                ->post(config('ai.base_url').'/chat/completions', [
                    'model' => config('ai.model'),
                    'temperature' => 0.3,
                    'max_tokens' => (int) config('ai.max_tokens', 700),
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        [
                            'role' => 'user',
                            'content' => "Farmer name: {$farmerName}\nQuestion: {$prompt}",
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('CyraAI LLM request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $content = data_get($response->json(), 'choices.0.message.content');

            return is_string($content) && trim($content) !== '' ? trim($content) : null;
        } catch (Throwable $e) {
            Log::warning('CyraAI LLM exception', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
