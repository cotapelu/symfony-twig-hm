<?php

declare(strict_types=1);

namespace Tests\Platform\MDM;

use PHPUnit\Framework\TestCase;
use Platform\MDM\ReferenceDataService;

class ReferenceDataServiceTest extends TestCase
{
    private ReferenceDataService $referenceDataService;

    protected function setUp(): void
    {
        $this->referenceDataService = new ReferenceDataService();
    }

    public function testGetReferenceDataReturnsArrayWithExpectedKeys(): void
    {
        $result = $this->referenceDataService->getReferenceData('currency', 'USD');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('valid_from', $result);
        $this->assertArrayHasKey('valid_to', $result);
        $this->assertEquals('currency', $result['type']);
        $this->assertEquals('USD', $result['code']);
    }

    public function testSearchReferenceDataReturnsArrayOfResults(): void
    {
        $result = $this->referenceDataService->searchReferenceData('currency', ['active' => true]);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertIsArray($result[0]);
        $this->assertArrayHasKey('type', $result[0]);
        $this->assertArrayHasKey('code', $result[0]);
        $this->assertArrayHasKey('description', $result[0]);
    }

    public function testValidateReferenceDataReturnsTrueForNonEmptyTypeAndCode(): void
    {
        $this->assertTrue($this->referenceDataService->validateReferenceData('currency', 'USD'));
    }

    public function testValidateReferenceDataReturnsFalseForEmptyType(): void
    {
        $this->assertFalse($this->referenceDataService->validateReferenceData('', 'USD'));
    }

    public function testValidateReferenceDataReturnsFalseForEmptyCode(): void
    {
        $this->assertFalse($this->referenceDataService->validateReferenceData('currency', ''));
    }

    public function testValidateReferenceDataReturnsFalseForBothEmpty(): void
    {
        $this->assertFalse($this->referenceDataService->validateReferenceData('', ''));
    }
}