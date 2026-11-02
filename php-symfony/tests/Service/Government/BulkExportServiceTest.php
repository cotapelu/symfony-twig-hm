<?php

declare(strict_types=1);

namespace App\Tests\Service\Government;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Government\Application\Service\BulkExportService;
use App\Government\Domain\Entity\Citizen;
use App\Government\Domain\Repository\CitizenRepositoryInterface;
use App\Government\Domain\ValueObject\Address;
use App\Government\Domain\ValueObject\CitizenId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BulkExportServiceTest extends TestCase
{
    private CitizenRepositoryInterface&MockObject $citizenRepository;
    private BulkExportService $bulkExportService;

    protected function setUp(): void
    {
        $this->citizenRepository = $this->createMock(CitizenRepositoryInterface::class);
        $this->bulkExportService = new BulkExportService($this->citizenRepository);
    }

    public function testExportCitizensToCSV(): void
    {
        $citizen1 = $this->createMock(Citizen::class);
        $citizen1->method('getId')->willReturn(new CitizenId('1'));
        $citizen1->method('getFullName')->willReturn('Nguyen Van A');
        $citizen1->method('getEmail')->willReturn('nguyenvana@example.com');
        $citizen1->method('getPhone')->willReturn('0909123456');
        $citizen1->method('getIdentityNumber')->willReturn('0123456789');
        $citizen1->method('getGender')->willReturn('male');
        $citizen1->method('isActive')->willReturn(true);
        $citizen1->method('toArray')->willReturn([
            'id' => '1',
            'fullName' => 'Nguyen Van A',
            'email' => 'nguyenvana@example.com',
            'phone' => '0909123456',
            'identityNumber' => '0123456789',
            'gender' => 'male',
            'isActive' => true
        ]);

        $citizen2 = $this->createMock(Citizen::class);
        $citizen2->method('getId')->willReturn(new CitizenId('2'));
        $citizen2->method('getFullName')->willReturn('Tran Thi B');
        $citizen2->method('getEmail')->willReturn('tranthib@example.com');
        $citizen2->method('getPhone')->willReturn('0912345678');
        $citizen2->method('getIdentityNumber')->willReturn('9876543210');
        $citizen2->method('getGender')->willReturn('female');
        $citizen2->method('isActive')->willReturn(false);
        $citizen2->method('toArray')->willReturn([
            'id' => '2',
            'fullName' => 'Tran Thi B',
            'email' => 'tranthib@example.com',
            'phone' => '0912345678',
            'identityNumber' => '9876543210',
            'gender' => 'female',
            'isActive' => false
        ]);

        $this->citizenRepository->method('findAll')
            ->willReturn([$citizen1, $citizen2]);

        $csv = $this->bulkExportService->exportCitizensToCSV();

        $this->assertStringContainsString('id,fullName,email,phone,identityNumber,gender,isActive', $csv);
        $this->assertStringContainsString('1,Nguyen Van A,nguyenvana@example.com,0909123456,0123456789,male,true', $csv);
        $this->assertStringContainsString('2,Tran Thi B,tranthib@example.com,0912345678,9876543210,female,false', $csv);
    }

    public function testExportCitizensToCSVWithSpecialCharacters(): void
    {
        $citizen = $this->createMock(Citizen::class);
        $citizen->method('getId')->willReturn(new CitizenId('1'));
        $citizen->method('getFullName')->willReturn('Nguyen, Van A'); // Contains comma
        $citizen->method('getEmail')->willReturn('nguyenvana@example.com');
        $citizen->method('getPhone')->willReturn('0909123456');
        $citizen->method('getIdentityNumber')->willReturn('0123456789');
        $citizen->method('getGender')->willReturn('male');
        $citizen->method('isActive')->willReturn(true);

        $this->citizenRepository->method('findAll')
            ->willReturn([$citizen]);

        $csv = $this->bulkExportService->exportCitizensToCSV();

        $this->assertStringContainsString('"Nguyen, Van A"', $csv); // Should be quoted
    }

    public function testExportCitizensToJSON(): void
    {
        $citizen = $this->createMock(Citizen::class);
        $citizen->method('getId')->willReturn(new CitizenId('1'));
        $citizen->method('getFullName')->willReturn('Nguyen Van A');
        $citizen->method('getEmail')->willReturn('nguyenvana@example.com');
        $citizen->method('getPhone')->willReturn('0909123456');
        $citizen->method('getIdentityNumber')->willReturn('0123456789');
        $citizen->method('getGender')->willReturn('male');
        $citizen->method('isActive')->willReturn(true);
        $citizen->method('toArray')->willReturn([
            'id' => '1',
            'fullName' => 'Nguyen Van A',
            'email' => 'nguyenvana@example.com',
            'phone' => '0909123456',
            'identityNumber' => '0123456789',
            'gender' => 'male',
            'isActive' => true
        ]);

        $this->citizenRepository->method('findAll')
            ->willReturn([$citizen]);

        $json = $this->bulkExportService->exportCitizensToJSON();

        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals('Nguyen Van A', $data[0]['fullName']);
        $this->assertEquals('nguyenvana@example.com', $data[0]['email']);
    }

    public function testExportCitizensToJSONWithFilters(): void
    {
        $citizen = $this->createMock(Citizen::class);
        $citizen->method('toArray')->willReturn([
            'id' => '1',
            'fullName' => 'Nguyen Van A',
            'email' => 'nguyenvana@example.com',
            'phone' => '0909123456',
            'identityNumber' => '0123456789',
            'gender' => 'male',
            'isActive' => true
        ]);

        $this->citizenRepository->method('findAll')
            ->with(['province' => 'Hanoi'])
            ->willReturn([$citizen]);

        $json = $this->bulkExportService->exportCitizensToJSON(['province' => 'Hanoi']);

        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
    }
}