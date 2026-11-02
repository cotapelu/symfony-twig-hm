<?php declare(strict_types=1);

namespace App\Tests\Unit\BusinessAccount\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\BusinessAccount\Entity\Business;
use App\BusinessAccount\Repository\BusinessRepository;
use App\BusinessAccount\Service\BusinessVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BusinessVerificationServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private BusinessRepository&MockObject $businessRepo;
    private BusinessVerificationService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->businessRepo = $this->createMock(BusinessRepository::class);
        $this->em->method('getRepository')->with('App\BusinessAccount\Entity\Business')->willReturn($this->businessRepo);
        $this->service = new BusinessVerificationService($this->em);
    }

    public function testVerifyBusiness(): void
    {
        $business = $this->createMock(Business::class);
        $business->expects($this->once())->method('setIsVerified')->with(true);
        $business->expects($this->once())->method('setStatus')->with('active');
        $this->em->expects($this->once())->method('flush');
        $result = $this->service->verifyBusiness($business);
        $this->assertSame($business, $result);
    }

    public function testApproveBusiness(): void
    {
        $business = $this->createMock(Business::class);
        $business->expects($this->once())->method('setStatus')->with('active');
        $this->em->expects($this->once())->method('flush');
        $result = $this->service->approveBusiness($business);
        $this->assertSame($business, $result);
    }

    public function testSuspendBusiness(): void
    {
        $business = $this->createMock(Business::class);
        $business->expects($this->once())->method('setStatus')->with('suspended');
        $this->em->expects($this->once())->method('flush');
        $result = $this->service->suspendBusiness($business);
        $this->assertSame($business, $result);
    }

    public function testGetBusinessById(): void
    {
        $id = 123;
        $business = $this->createMock(Business::class);
        $this->businessRepo->expects($this->once())->method('find')->with($id)->willReturn($business);
        $result = $this->service->getBusinessById($id);
        $this->assertSame($business, $result);
    }

    public function testGetBusinessByIdWhenNotFound(): void
    {
        $id = 999;
        $this->businessRepo->expects($this->once())->method('find')->with($id)->willReturn(null);
        $result = $this->service->getBusinessById($id);
        $this->assertNull($result);
    }
}
