<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class NotifyWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    /**
     * @param string $url
     * @param string $requestId
     * @param string $status
     * @param string|null $recipeId
     */
    public function __construct(
        public string $url,
        public string $requestId,
        public string $status,
        public ?string $recipeId = null,
    ) {
    }

    /**
     * @throws \Throwable
     * @return void
     */
    public function handle(): void
    {
        try {
            Http::timeout(5)
                ->acceptJson()
                ->asJson()
                ->post($this->url, [
                    'request_id' => $this->requestId,
                    'status'     => $this->status,
                    'recipe_id'  => $this->recipeId,
                    'timestamp'  => now()->toIso8601String(),
                ]);
        } catch (\Throwable $e) {
            Log::warning('Webhook notify failed', [
                'url'       => $this->url,
                'requestId' => $this->requestId,
                'error'     => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
