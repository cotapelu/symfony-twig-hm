<?php declare(strict_types=1);

namespace App\Tests\Unit\User\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Service\UserIdGeneratorService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserIdGeneratorServiceTest extends TestCase
{
    public function testGenerateFormat(): void
    {
        $service = new UserIdGeneratorService();
        for ($i = 0; $i < 5; $i++) {
            $id = $service->generate();
            $this->assertMatchesRegularExpression('/^user_[a-f0-9]{32}$/', $id);
        }
    }
}
