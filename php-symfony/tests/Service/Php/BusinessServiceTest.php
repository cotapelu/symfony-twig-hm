<?php declare(strict_types=1);

namespace App\Tests\Service\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\Business;
use App\Repository\Php\BusinessRepository;
use App\Service\Php\BusinessService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[AllowMockObjectsWithoutExpectations]
class BusinessServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private BusinessRepository&MockObject $repository;
    private BusinessService $service;

    private string $ownerId = 'user-owner-1';
    private string $otherOwnerId = 'user-owner-2';
    private string $businessId;
    private Business $business;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(BusinessRepository::class);
        $this->service = new BusinessService($this->em, $this->repository);

        $this->businessId = bin2hex(random_bytes(16));
        $this->business = new Business(
            $this->businessId,
            $this->ownerId,
            'Test Business',
            'RETAIL'
        );
    }

    public function testCreateBusinessSuccess(): void
    {
        $ownerId = $this->ownerId;
        $name = 'My Biz';
        $type = 'SERVICE';
        $address = '123 Main';
        $phone = '0123456789';
        $email = 'test@example.com';
        $taxCode = 'TAX123';
        $metadata = ['foo' => 'bar'];

        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Business $biz) use (&$captured, $ownerId, $name, $type, $address, $phone, $email, $taxCode, $metadata) {
                $captured = $biz;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createBusiness($ownerId, $name, $type, $address, $phone, $email, $taxCode, $metadata);

        $this->assertInstanceOf(Business::class, $result);
        $this->assertSame($ownerId, $result->getOwnerId());
        $this->assertSame($name, $result->getName());
        $this->assertSame($type, $result->getType());
        $this->assertSame($address, $result->getAddress());
        $this->assertSame($phone, $result->getPhone());
        $this->assertSame($email, $result->getEmail());
        $this->assertSame($taxCode, $result->getTaxCode());
        $this->assertSame($metadata, $result->getMetadata());
        $this->assertSame($captured, $result);
    }

    public function testCreateBusinessValidationEmptyOwnerId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Owner ID is required');
        $this->service->createBusiness('', 'Name', 'TYPE');
    }

    public function testCreateBusinessValidationEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Business name is required');
        $this->service->createBusiness($this->ownerId, '   ', 'TYPE');
    }

    public function testCreateBusinessValidationEmptyType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Business type is required');
        $this->service->createBusiness($this->ownerId, 'Name', '');
    }

    public function testGetBusinessSuccess(): void
    {
        $this->service->setCurrentUser($this->ownerId);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->businessId)
            ->willReturn($this->business);

        $result = $this->service->getBusiness($this->businessId);
        $this->assertSame($this->business, $result);
    }

    public function testGetBusinessNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->businessId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Business not found');
        $this->service->getBusiness($this->businessId);
    }

    public function testGetBusinessAccessDeniedWhenNoUserSet(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->businessId)
            ->willReturn($this->business);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('User context not set');
        $this->service->getBusiness($this->businessId);
    }

    public function testGetBusinessAccessDeniedWrongUser(): void
    {
        $this->service->setCurrentUser($this->otherOwnerId);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->businessId)
            ->willReturn($this->business);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Access denied: Not your business');
        $this->service->getBusiness($this->businessId);
    }

    public function testUpdateBusinessSuccess(): void
    {
        $this->service->setCurrentUser($this->ownerId);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->businessId)
            ->willReturn($this->business);

        $newAddress = '456 New St';
        $newPhone = '0987654321';
        $newEmail = 'new@example.com';
        $newTax = 'NEWTAX';
        $newStatus = 'INACTIVE';
        $newMetadata = ['updated' => true];

        $this->em->expects($this->once())->method('flush');

        $data = [
            'address' => $newAddress,
            'phone' => $newPhone,
            'email' => $newEmail,
            'taxCode' => $newTax,
            'status' => $newStatus,
            'metadata' => $newMetadata,
        ];

        $result = $this->service->updateBusiness($this->businessId, $data);
        $this->assertSame($this->business, $result);
        $this->assertSame($newAddress, $result->getAddress());
        $this->assertSame($newPhone, $result->getPhone());
        $this->assertSame($newEmail, $result->getEmail());
        $this->assertSame($newTax, $result->getTaxCode());
        $this->assertSame($newStatus, $result->getStatus());
        $this->assertSame($newMetadata, $result->getMetadata());
    }

    public function testUpdateBusinessValidationEmptyName(): void
    {
        $this->service->setCurrentUser($this->ownerId);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->businessId)
            ->willReturn($this->business);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Business name cannot be empty');
        $this->service->updateBusiness($this->businessId, ['name' => '   ']);
    }

    public function testUpdateBusinessValidationEmptyType(): void
    {
        $this->service->setCurrentUser($this->ownerId);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->businessId)
            ->willReturn($this->business);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Business type cannot be empty');
        $this->service->updateBusiness($this->businessId, ['type' => '']);
    }

    public function testUpdateBusinessValidationInvalidEmail(): void
    {
        $this->service->setCurrentUser($this->ownerId);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->businessId)
            ->willReturn($this->business);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');
        $this->service->updateBusiness($this->businessId, ['email' => 'invalid']);
    }

    public function testUpdateBusinessOwnerChangeDenied(): void
    {
        $this->service->setCurrentUser($this->ownerId);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->businessId)
            ->willReturn($this->business);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Cannot transfer ownership to another user');
        $this->service->updateBusiness($this->businessId, ['ownerId' => $this->otherOwnerId]);
    }

    public function testDeleteBusinessSuccess(): void
    {
        $this->service->setCurrentUser($this->ownerId);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->businessId)
            ->willReturn($this->business);

        $this->em->expects($this->once())->method('remove')->with($this->business);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteBusiness($this->businessId);
    }

    public function testDeleteBusinessNotFound(): void
    {
        $this->service->setCurrentUser($this->ownerId);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->businessId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Business not found');
        $this->service->deleteBusiness($this->businessId);
    }

    public function testDeleteBusinessAccessDeniedWrongUser(): void
    {
        $this->service->setCurrentUser($this->otherOwnerId);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->businessId)
            ->willReturn($this->business);

        $this->expectException(AccessDeniedHttpException::class);
        $this->service->deleteBusiness($this->businessId);
    }

    public function testFindByOwnerId(): void
    {
        $owner = 'owner-123';
        $results = [$this->business];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['ownerId' => $owner])
            ->willReturn($results);

        $result = $this->service->findByOwnerId($owner);
        $this->assertSame($results, $result);
    }

    public function testFindByType(): void
    {
        $type = 'RETAIL';
        $results = [$this->business];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['type' => $type])
            ->willReturn($results);

        $result = $this->service->findByType($type);
        $this->assertSame($results, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'INACTIVE';
        $results = [$this->business];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($results);

        $result = $this->service->findByStatus($status);
        $this->assertSame($results, $result);
    }
}
