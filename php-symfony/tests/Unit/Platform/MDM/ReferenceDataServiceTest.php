<?php

declare(strict_types=1);

namespace App\Tests\Unit\Platform\MDM;

use Platform\MDM\Exception\MDMException;
use Platform\MDM\ReferenceDataService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ReferenceDataServiceTest extends TestCase
{
    private ReferenceDataService $service;

    protected function setUp(): void
    {
        $this->service = new ReferenceDataService();
    }

    #[Test]
    public function it_returns_reference_data_for_valid_type_and_code(): void
    {
        $result = $this->service->getReferenceData('country', 'US');

        $this->assertSame('country', $result['type']);
        $this->assertSame('US', $result['code']);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('valid_from', $result);
    }

    #[Test]
    public function it_throws_for_empty_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getReferenceData('', 'US');
    }

    #[Test]
    public function it_throws_for_invalid_type(): void
    {
        $this->expectException(MDMException::class);
        $this->service->getReferenceData('invalid_type', 'US');
    }

    #[Test]
    public function it_throws_for_empty_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getReferenceData('country', '');
    }

    #[Test]
    public function it_throws_for_code_too_long(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getReferenceData('country', str_repeat('a', 51));
    }

    #[Test]
    public function it_searches_reference_data(): void
    {
        $results = $this->service->searchReferenceData('country', ['name' => 'United']);

        $this->assertCount(2, $results);
        $this->assertSame('country', $results[0]['type']);
    }

    #[Test]
    public function it_throws_for_empty_search_criteria(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->searchReferenceData('country', []);
    }

    #[Test]
    public function it_validates_reference_data(): void
    {
        $this->assertTrue($this->service->validateReferenceData('country', 'US'));
    }

    #[Test]
    public function it_returns_false_for_invalid_reference_data(): void
    {
        $this->assertFalse($this->service->validateReferenceData('invalid_type', 'US'));
    }
}
