<?php declare(strict_types=1);

namespace App\Tests\Unit\Common\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Common\Service\OAuthProviderService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class OAuthProviderServiceTest extends TestCase
{
    public function testDoSomethingReturnsFormattedString(): void
    {
        $service = new OAuthProviderService();
        $result = $service->doSomething('test');
        $this->assertSame('OAuthProviderService: test', $result);
    }

    public function testDoSomethingHandlesEmptyString(): void
    {
        $service = new OAuthProviderService();
        $result = $service->doSomething('');
        $this->assertSame('OAuthProviderService: ', $result);
    }

    public function testDoSomethingHandlesSpecialCharacters(): void
    {
        $service = new OAuthProviderService();
        $result = $service->doSomething('a & b <c>');
        $this->assertSame('OAuthProviderService: a & b <c>', $result);
    }
}