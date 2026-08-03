<?php

declare(strict_types=1);

class LicenseManager
{
    private string $cacheFile;

    private string $apiUrl = 'https://artigrid.developmentserver.cl/api/license-validate.php';

    public function __construct(string $cacheFile = __DIR__ . '/license.cache')
    {
        $this->cacheFile = $cacheFile;
    }

    public function validate(string $licenseKey): bool
    {
        if (empty($licenseKey)) {
            return false;
        }

        if (
            is_file($this->cacheFile) &&
            trim(file_get_contents($this->cacheFile)) === $licenseKey
        ) {
            return true;
        }

        if (strlen($licenseKey) < 10) {
            return false;
        }

        $serverCheck = $this->verifyWithServer($licenseKey);

        if ($serverCheck === false) {
            return false;
        }

        file_put_contents($this->cacheFile, $licenseKey);

        return true;
    }

    private function verifyWithServer(string $licenseKey)
    {
        $payload = json_encode([
            'license_key' => $licenseKey
        ]);

        $ch = curl_init($this->apiUrl);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: ArtiGrid License Validator'
            ],
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return false;
        }

        $data = json_decode($response, true);

        if (!is_array($data) || empty($data['valid'])) {
            return false;
        }

        return $data;
    }
}
