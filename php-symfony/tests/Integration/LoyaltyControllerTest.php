<?php

declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AllowMockObjectsWithoutExpectations]
class LoyaltyControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ?EntityManagerInterface $entityManager = null;
    private UserPasswordHasherInterface $passwordHasher;
    private string $authToken;
    private ?User $testUser = null;

    protected function setUp(): void
    {
        $this->markTestSkipped('LoyaltyControllerTest skipped: loyalty routes/service not yet integrated.');
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $this->testUser = new User(
            'test_loyalty_user_' . uniqid(),
            'test_loyalty@example.com',
            ['ROLE_USER']
        );
        $this->testUser->setPassword($this->passwordHasher->hashPassword($this->testUser, 'password123'));
        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();

        $this->client->jsonRequest('POST', '/api/login', [
            'email' => $this->testUser->getEmail(),
            'password' => 'password123',
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->authToken = $data['token'] ?? '';
    }

    protected function tearDown(): void
    {
        // Clean up test user if both exist
        if ($this->testUser !== null && $this->entityManager !== null) {
            $this->entityManager->remove($this->testUser);
            $this->entityManager->flush();
        }
        parent::tearDown();
    }

    public function testCreateProgram(): void
    {
        $this->client->setServerParameter('HTTP_Authorization', 'Bearer ' . $this->authToken);
        $this->client->jsonRequest('POST', '/api/loyalty/programs', [
            'name' => 'Test Program',
            'description' => 'Test Description',
            'program_type' => 'points',
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testGetPrograms(): void
    {
        $this->client->setServerParameter('HTTP_Authorization', 'Bearer ' . $this->authToken);
        $this->client->request('GET', '/api/loyalty/programs');
        $this->assertResponseIsSuccessful();
    }

    public function testCreateMember(): void
    {
        // Create a program first
        $this->client->setServerParameter('HTTP_Authorization', 'Bearer ' . $this->authToken);
        $this->client->jsonRequest('POST', '/api/loyalty/programs', [
            'name' => 'Membership Program',
            'description' => 'Desc',
            'program_type' => 'points',
        ]);
        $this->assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        $programData = json_decode($response->getContent(), true);
        $programId = $programData['data']['id'] ?? null;
        $this->assertNotNull($programId);

        // Enroll member
        $this->client->jsonRequest('POST', '/api/loyalty/members', [
            'program_id' => $programId,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testAddPointsValidation(): void
    {
        // Create program and member first
        $this->client->setServerParameter('HTTP_Authorization', 'Bearer ' . $this->authToken);
        $this->client->jsonRequest('POST', '/api/loyalty/programs', [
            'name' => 'Test Program',
            'description' => 'Desc',
            'program_type' => 'points',
        ]);
        $this->assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        $programData = json_decode($response->getContent(), true);
        $programId = $programData['data']['id'] ?? null;
        $this->assertNotNull($programId);

        $this->client->jsonRequest('POST', '/api/loyalty/members', [
            'program_id' => $programId,
        ]);
        $this->assertResponseIsSuccessful();
        $memberResponse = $this->client->getResponse();
        $memberData = json_decode($memberResponse->getContent(), true);
        $memberId = $memberData['data']['id'] ?? null;
        $this->assertNotNull($memberId);

        // Attempt to add negative points (should fail with 400)
        $this->client->jsonRequest('POST', '/api/loyalty/members/' . $memberId . '/points', [
            'points' => -10,
        ]);

        $this->assertResponseStatusCodeSame(400);
    }
}