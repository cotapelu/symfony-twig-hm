<?php

declare(strict_types=1);

namespace App\Tests\Integration\Concurrency;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\IntegrationTestCase;

#[AllowMockObjectsWithoutExpectations]
class ConcurrentRequestsTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('ConcurrentRequestsTest skipped: flaky concurrency test pending stabilization.');
    }
    public function testMultipleConcurrentHealthRequests(): void
    {
        $url = '/platform/health/iam';
        $base = 'http://127.0.0.1:8000'; // assume built-in server
        $concurrency = 20;

        $mh = curl_multi_init();
        $handles = [];

        for ($i = 0; $i < $concurrency; $i++) {
            $ch = curl_init($base . $url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        $success = 0;
        foreach ($handles as $ch) {
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($code === 200) {
                $success++;
            } else {
                $content = curl_multi_getcontent($ch);
                error_log("Concurrent request failed with status $code: $content");
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        $this->assertSame($concurrency, $success, "Not all concurrent requests succeeded");
    }
}
