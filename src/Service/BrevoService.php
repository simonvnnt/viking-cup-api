<?php

namespace App\Service;

use App\Helper\ConfigHelper;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BrevoService
{
    private ?string $brevoBaseUrl;
    private ?string $brevoApiKey;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly ConfigHelper $configHelper
    ) {
        $this->brevoBaseUrl = $this->configHelper->getValue('BREVO_BASE_URL');
        $this->brevoApiKey = $this->configHelper->getValue('BREVO_API_KEY');
    }

    public function createOrUpdateContact(
        string $email,
        array $attributes = [],
        array $listIds = []
    ): void {
        $this->client->request('POST', $this->brevoBaseUrl . '/contacts', [
            'headers' => [
                'api-key' => $this->brevoApiKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ],
            'json' => [
                'email' => $email,
                'attributes' => $attributes,
                'listIds' => $listIds,
                'updateEnabled' => true
            ],
        ]);
    }
}
