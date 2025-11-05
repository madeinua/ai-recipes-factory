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
        // Validate webhook URL to prevent SSRF attacks
        $this->validateWebhookUrl($this->url);

        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->asJson()
                ->post($this->url, [
                    'request_id' => $this->requestId,
                    'status'     => $this->status,
                    'recipe_id'  => $this->recipeId,
                    'timestamp'  => now()->toIso8601String(),
                ]);

            if (!$response->successful()) {
                throw new \RuntimeException(
                    "Webhook failed with status {$response->status()}: {$response->body()}"
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Webhook notify failed', [
                'url'       => $this->url,
                'requestId' => $this->requestId,
                'error'     => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Validate webhook URL to prevent SSRF attacks
     *
     * @throws \InvalidArgumentException
     * @param string $url
     * @return void
     */
    private function validateWebhookUrl(string $url): void
    {
        $parsed = parse_url($url);

        if (!$parsed || !isset($parsed['host'])) {
            throw new \InvalidArgumentException('Invalid webhook URL format.');
        }

        $host = $parsed['host'];

        $ip = gethostbyname($host);

        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            // Block private IP ranges (RFC1918)
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false) {
                throw new \InvalidArgumentException('Webhook URL cannot target private IP addresses.');
            }

            // Block reserved IP ranges (loopback, link-local, etc.)
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false) {
                throw new \InvalidArgumentException('Webhook URL cannot target reserved IP addresses.');
            }
        }

        // Block common localhost variations
        $blockedHosts = ['localhost', '127.0.0.1', '0.0.0.0', '::1', '0:0:0:0:0:0:0:1'];
        if (in_array(strtolower($host), $blockedHosts, true)) {
            throw new \InvalidArgumentException('Webhook URL cannot target localhost.');
        }

        // Block cloud metadata endpoints
        $blockedMetadata = [
            '169.254.169.254',  // AWS, Azure, GCP metadata
            'metadata.google.internal',  // GCP
            '100.100.100.200',  // Alibaba Cloud
            'fd00:ec2::254',  // AWS IPv6
        ];

        if (in_array($ip, $blockedMetadata, true) || in_array(strtolower($host), $blockedMetadata, true)) {
            throw new \InvalidArgumentException('Webhook URL cannot target cloud metadata endpoints.');
        }
    }
}
