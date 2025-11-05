<?php

namespace App\Http\Requests\Recipe;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class GenerateRecipeRequest extends FormRequest
{
    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'ingredients' => ['required', 'string', 'min:2', 'max:3000'],
            'webhook_url' => ['nullable', 'url', 'max:500'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $webhookUrl = $this->input('webhook_url');

            if ($webhookUrl) {
                try {
                    $this->validateWebhookUrl($webhookUrl);
                } catch (\InvalidArgumentException $e) {
                    $validator->errors()->add('webhook_url', $e->getMessage());
                }
            }
        });
    }

    /**
     * Validate webhook URL to prevent SSRF attacks
     *
     * @param string $url
     * @throws \InvalidArgumentException
     * @return void
     */
    private function validateWebhookUrl(string $url): void
    {
        $parsed = parse_url($url);

        if (!$parsed || !isset($parsed['host'])) {
            throw new \InvalidArgumentException('Invalid webhook URL format.');
        }

        $host = $parsed['host'];

        // Resolve hostname to IP address
        $ip = gethostbyname($host);

        // If gethostbyname fails to resolve, it returns the hostname unchanged
        if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            // Unable to resolve and not an IP - might be fine, but be cautious
            // Allow it to proceed but could optionally block unresolvable hosts
        }

        // Block if resolved to an IP address
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
