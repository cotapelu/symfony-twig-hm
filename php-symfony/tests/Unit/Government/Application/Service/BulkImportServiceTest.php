<?php

namespace App\Tests\Unit\Government\Application\Service;

use App\Government\Application\Service\BulkImportService;
use App\Government\Contracts\DTO\BulkImportResponse;
use App\Government\Domain\Repository\CitizenRepositoryInterface;
use App\Government\Application\Service\CitizenService;
use App\Government\Contracts\DTO\CitizenResponse;
use App\Government\Domain\Entity\Citizen;
use PHPUnit\Framework\TestCase;

class BulkImportServiceTest extends TestCase
{
    private CitizenRepositoryInterface $citizenRepository;
    private CitizenService $citizenService;
    private BulkImportService $service;

    protected function setUp(): void
    {
        $this->citizenRepository = $this->createMock(CitizenRepositoryInterface::class);
        $this->citizenService = $this->createMock(CitizenService::class);
        $this->service = new BulkImportService(
            $this->citizenService,
            $this->citizenRepository
        );
    }

    public function testImportCitizensEmptyArray(): void
    {
        $response = $this->service->importCitizens([]);
        $this->assertInstanceOf(BulkImportResponse::class, $response);
        $this->assertSame(0, $response->successCount);
        $this->assertSame(0, $response->failedCount);
        $this->assertSame(0, $response->totalCount);
        $this->assertEmpty($response->importedIds);
        $this->assertEmpty($response->errors);
    }

    public function testImportCitizensAllSuccess(): void
    {
        $data = [
            [
                'fullName' => 'John Doe',
                'identityNumber' => '123456789',
                'province' => 'HCM',
                'district' => 'District 1',
                'ward' => 'Ben Nghe',
                'street' => 'Nguyen Hue',
            ],
            [
                'fullName' => 'Jane Smith',
                'identityNumber' => '987654321',
                'province' => 'Hanoi',
                'district' => 'Ba Dinh',
                'ward' => 'Dich Vong',
                'street' => 'Xuan Thuy',
            ],
        ];

        $this->citizenRepository
            ->expects($this->exactly(2))
            ->method('findByIdentityNumber')
            ->willReturnMap([
                ['123456789', null],
                ['987654321', null],
            ]);

        $citizen1 = new CitizenResponse(
            id: 'citizen-1',
            fullName: 'John Doe',
            email: null,
            phone: null,
            dateOfBirth: null,
            identityNumber: '123456789',
            gender: 'Male',
            registeredAddress: [],
            isActive: true,
            occupation: null,
            maritalStatus: null,
            householdId: null,
            age: null,
            createdAt: date('c')
        );
        $citizen2 = new CitizenResponse(
            id: 'citizen-2',
            fullName: 'Jane Smith',
            email: null,
            phone: null,
            dateOfBirth: null,
            identityNumber: '987654321',
            gender: 'Female',
            registeredAddress: [],
            isActive: true,
            occupation: null,
            maritalStatus: null,
            householdId: null,
            age: null,
            createdAt: date('c')
        );

        $this->citizenService
            ->expects($this->exactly(2))
            ->method('createCitizen')
            ->willReturnOnConsecutiveCalls($citizen1, $citizen2);

        $response = $this->service->importCitizens($data);
        $this->assertSame(2, $response->successCount);
        $this->assertSame(0, $response->failedCount);
        $this->assertSame(2, $response->totalCount);
        $this->assertSame(['citizen-1', 'citizen-2'], $response->importedIds);
        $this->assertEmpty($response->errors);
    }

    public function testImportCitizensWithValidationErrors(): void
    {
        $data = [
            // missing required fields: fullName empty
            [
                'fullName' => '',
                'identityNumber' => '123',
                'province' => 'HCM',
                'district' => 'District 1',
                'ward' => 'Ward',
                'street' => 'Street',
            ],
            // missing required field: ward empty
            [
                'fullName' => 'Alice',
                'identityNumber' => '999999999',
                'province' => 'Hanoi',
                'district' => 'Ba Dinh',
                'ward' => '',
                'street' => 'Street',
            ],
        ];

        $this->citizenRepository->expects($this->never())->method('findByIdentityNumber');
        $this->citizenService->expects($this->never())->method('createCitizen');

        $response = $this->service->importCitizens($data);
        $this->assertSame(0, $response->successCount);
        $this->assertSame(2, $response->failedCount);
        $this->assertSame(2, $response->totalCount);
        $this->assertEmpty($response->importedIds);
        $this->assertCount(2, $response->errors);
        $this->assertStringContainsString('Missing required field', $response->errors[0]['error']);
        $this->assertStringContainsString('Missing required field', $response->errors[1]['error']);
    }

    public function testImportCitizensWithDuplicate(): void
    {
        $data = [
            [
                'fullName' => 'Bob',
                'identityNumber' => '111222333',
                'province' => 'HCM',
                'district' => 'District 1',
                'ward' => 'Ward',
                'street' => 'Street',
            ],
        ];

        $existingCitizen = $this->createMock(Citizen::class);
        $this->citizenRepository
            ->expects($this->once())
            ->method('findByIdentityNumber')
            ->with('111222333')
            ->willReturn($existingCitizen);

        $this->citizenService->expects($this->never())->method('createCitizen');

        $response = $this->service->importCitizens($data);
        $this->assertSame(0, $response->successCount);
        $this->assertSame(1, $response->failedCount);
        $this->assertSame(1, $response->totalCount);
        $this->assertEmpty($response->importedIds);
        $this->assertCount(1, $response->errors);
        $this->assertStringContainsString('already exists', $response->errors[0]['error']);
    }

    public function testImportCitizensWithServiceException(): void
    {
        $data = [
            [
                'fullName' => 'Charlie',
                'identityNumber' => '999888777',
                'province' => 'Hanoi',
                'district' => 'Cau Giay',
                'ward' => 'Dich Vong',
                'street' => 'Xuan Thuy',
            ],
        ];

        $this->citizenRepository
            ->expects($this->once())
            ->method('findByIdentityNumber')
            ->with('999888777')
            ->willReturn(null);

        $this->citizenService
            ->expects($this->once())
            ->method('createCitizen')
            ->willThrowException(new \RuntimeException('Database error'));

        $response = $this->service->importCitizens($data);
        $this->assertSame(0, $response->successCount);
        $this->assertSame(1, $response->failedCount);
        $this->assertSame(1, $response->totalCount);
        $this->assertEmpty($response->importedIds);
        $this->assertCount(1, $response->errors);
        $this->assertSame('Database error', $response->errors[0]['error']);
    }

    public function testImportCitizensMixed(): void
    {
        $data = [
            // valid
            [
                'fullName' => 'Valid One',
                'identityNumber' => '111111111',
                'province' => 'Hanoi',
                'district' => 'Ba Dinh',
                'ward' => 'Ward',
                'street' => 'Street',
            ],
            // duplicate
            [
                'fullName' => 'Duplicate',
                'identityNumber' => '222222222',
                'province' => 'HCM',
                'district' => 'District 1',
                'ward' => 'Ward',
                'street' => 'Street',
            ],
            // missing required field (ward empty)
            [
                'fullName' => 'Invalid',
                'identityNumber' => '333333333',
                'province' => 'HCM',
                'district' => 'District 1',
                'ward' => '',
                'street' => 'Street',
            ],
            // another valid
            [
                'fullName' => 'Valid Two',
                'identityNumber' => '444444444',
                'province' => 'Da Nang',
                'district' => 'Hai Chau',
                'ward' => 'Ward',
                'street' => 'Street',
            ],
        ];

        $this->citizenRepository
            ->expects($this->exactly(3)) // called for both valid rows and the duplicate
            ->method('findByIdentityNumber')
            ->willReturnMap([
                ['111111111', null],
                ['222222222', $this->createMock(Citizen::class)], // duplicate exists
                ['444444444', null],
            ]);

        $citizenResponse1 = new CitizenResponse(
            id: 'cit-1',
            fullName: 'Valid One',
            email: null,
            phone: null,
            dateOfBirth: null,
            identityNumber: '111111111',
            gender: 'Male',
            registeredAddress: [],
            isActive: true,
            occupation: null,
            maritalStatus: null,
            householdId: null,
            age: null,
            createdAt: date('c')
        );
        $citizenResponse2 = new CitizenResponse(
            id: 'cit-2',
            fullName: 'Valid Two',
            email: null,
            phone: null,
            dateOfBirth: null,
            identityNumber: '444444444',
            gender: 'Female',
            registeredAddress: [],
            isActive: true,
            occupation: null,
            maritalStatus: null,
            householdId: null,
            age: null,
            createdAt: date('c')
        );

        $this->citizenService
            ->expects($this->exactly(2)) // only for the two valid rows
            ->method('createCitizen')
            ->willReturnOnConsecutiveCalls($citizenResponse1, $citizenResponse2);

        $response = $this->service->importCitizens($data);
        $this->assertSame(2, $response->successCount);
        $this->assertSame(2, $response->failedCount);
        $this->assertSame(4, $response->totalCount);
        $this->assertSame(['cit-1', 'cit-2'], $response->importedIds);
        $this->assertCount(2, $response->errors);
    }

    public function testGetImportStatistics(): void
    {
        $stats = $this->service->getImportStatistics();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('totalImports', $stats);
        $this->assertArrayHasKey('lastImportDate', $stats);
        $this->assertArrayHasKey('averageSuccessRate', $stats);
        $this->assertSame(0, $stats['totalImports']);
        $this->assertNull($stats['lastImportDate']);
        $this->assertSame(0, $stats['averageSuccessRate']);
    }

    public function testParseCSV(): void
    {
        $csvContent = "fullName,identityNumber,province,district,ward,street\nJohn,123456789,Hanoi,Ba Dinh,Dich Vong,Xuan Thuy\nJane,987654321,HCM,District 1,Ben Nghe,Nguyen Hue\n";
        $result = $this->service->parseCSV($csvContent);
        $expected = [
            [
                'fullName' => 'John',
                'identityNumber' => '123456789',
                'province' => 'Hanoi',
                'district' => 'Ba Dinh',
                'ward' => 'Dich Vong',
                'street' => 'Xuan Thuy',
            ],
            [
                'fullName' => 'Jane',
                'identityNumber' => '987654321',
                'province' => 'HCM',
                'district' => 'District 1',
                'ward' => 'Ben Nghe',
                'street' => 'Nguyen Hue',
            ],
        ];
        $this->assertSame($expected, $result);
    }

    public function testParseCSVWithEmptyLinesAndTrailingNewline(): void
    {
        $csvContent = "fullName,identityNumber,province,district,ward,street\nAlice,111222333,Hanoi,Cau Giay,Dich Vong,Xuan Thuy\n\nBob,444555666,HCM,District 1,Ben Nghe,Nguyen Hue\n\n";
        $result = $this->service->parseCSV($csvContent);
        $this->assertCount(2, $result);
        $this->assertSame('Alice', $result[0]['fullName']);
        $this->assertSame('Bob', $result[1]['fullName']);
    }

    // ================= BulkImportService additional tests =================
    public function testImportCitizensFailsInvalidIdentityNumberFormat(): void
    {
        $data = [
            'fullName' => 'Test',
            'identityNumber' => '123',
            'province' => 'Hanoi',
            'district' => 'Ba Dinh',
            'ward' => 'Dich Vong',
            'street' => 'Xuan Thuy',
        ];
        $response = $this->service->importCitizens([$data]);
        $this->assertSame(0, $response->successCount);
        $this->assertSame(1, $response->failedCount);
        $this->assertCount(1, $response->errors);
        $this->assertStringContainsString('Identity number must be 9-12 digits', $response->errors[0]['error']);
    }

    public function testImportCitizensFailsInvalidEmailFormat(): void
    {
        $data = [
            'fullName' => 'Test',
            'identityNumber' => '123456789',
            'province' => 'Hanoi',
            'district' => 'Ba Dinh',
            'ward' => 'Dich Vong',
            'street' => 'Xuan Thuy',
            'email' => 'notanemail',
        ];
        $response = $this->service->importCitizens([$data]);
        $this->assertSame(0, $response->successCount);
        $this->assertSame(1, $response->failedCount);
        $this->assertStringContainsString('Invalid email format', $response->errors[0]['error']);
    }

    public function testImportCitizensFailsInvalidPhoneFormat(): void
    {
        $data = [
            'fullName' => 'Test',
            'identityNumber' => '123456789',
            'province' => 'Hanoi',
            'district' => 'Ba Dinh',
            'ward' => 'Dich Vong',
            'street' => 'Xuan Thuy',
            'phone' => '12345',
        ];
        $response = $this->service->importCitizens([$data]);
        $this->assertSame(0, $response->successCount);
        $this->assertSame(1, $response->failedCount);
        $this->assertStringContainsString('Phone number must be 10-11 digits', $response->errors[0]['error']);
    }

    public function testImportCitizensFailsInvalidDateOfBirthFormat(): void
    {
        $data = [
            'fullName' => 'Test',
            'identityNumber' => '123456789',
            'province' => 'Hanoi',
            'district' => 'Ba Dinh',
            'ward' => 'Dich Vong',
            'street' => 'Xuan Thuy',
            'dateOfBirth' => '2020-13-01',
        ];
        $response = $this->service->importCitizens([$data]);
        $this->assertSame(0, $response->successCount);
        $this->assertSame(1, $response->failedCount);
        $this->assertCount(1, $response->errors);
        $this->assertStringContainsString('Date of birth must be in YYYY-MM-DD format', $response->errors[0]['error']);
    }

    public function testGenerateCSVTemplateReturnsExpectedFormat(): void
    {
        $csv = $this->service->generateCSVTemplate();
        $lines = explode("\n", trim($csv));
        $this->assertCount(2, $lines);
        $headers = str_getcsv($lines[0]);
        $expectedHeaders = ['fullName','identityNumber','province','district','ward','street','apartmentNumber','email','phone','dateOfBirth','gender','ethnicity','nationality','occupation','educationLevel','maritalStatus','householdId'];
        $this->assertSame($expectedHeaders, $headers);
        $exampleRow = str_getcsv($lines[1]);
        $expectedRow = ['Nguyen Van A','012345678901','Ha Noi','Ba Dinh','Phuc Xa','Tran Phu','12A','nguyenvana@example.com','0901234567','1990-01-01','male','Kinh','Vietnam','Engineer','University','married','HH001'];
        $this->assertSame($expectedRow, $exampleRow);
    }
}
