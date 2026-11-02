<?php declare(strict_types=1);

namespace App\Tests\Integration\FeatureFlags;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\FeatureFlags\Entity\FeatureFlag;
use Doctrine\ORM\EntityManagerInterface;

#[AllowMockObjectsWithoutExpectations]
class FeatureFlagIntegrationDbTest extends DatabaseIntegrationTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = self::getEntityManager();
    }

    public function testCreateFeatureFlagAndPersist(): void
    {
        $flag = new FeatureFlag(
            name: 'new_dashboard',
            enabled: true,
            description: 'Enable new dashboard experience'
        );
        $this->em->persist($flag);
        $this->em->flush();

        $this->assertNotNull($flag->getId());
        $this->assertSame('new_dashboard', $flag->getName());
        $this->assertTrue($flag->isEnabled());
        $this->assertSame('Enable new dashboard experience', $flag->getDescription());
    }

    public function testFindFeatureFlagById(): void
    {
        $flag = new FeatureFlag(
            name: 'dark_mode',
            enabled: false,
            description: 'Dark mode support'
        );
        $this->em->persist($flag);
        $this->em->flush();

        $id = $flag->getId();
        $repo = $this->em->getRepository(FeatureFlag::class);
        $found = $repo->find($id);
        $this->assertNotNull($found);
        $this->assertSame('dark_mode', $found->getName());
    }

    public function testUpdateFeatureFlag(): void
    {
        $flag = new FeatureFlag(
            name: 'beta_features',
            enabled: false,
            description: null
        );
        $this->em->persist($flag);
        $this->em->flush();

        $flag->setEnabled(true);
        $flag->setDescription('Access to beta features');
        $this->em->flush();

        $repo = $this->em->getRepository(FeatureFlag::class);
        $updated = $repo->find($flag->getId());
        $this->assertTrue($updated->isEnabled());
        $this->assertSame('Access to beta features', $updated->getDescription());
    }

    public function testDeleteFeatureFlag(): void
    {
        $flag = new FeatureFlag(
            name: 'temp_flag',
            enabled: false
        );
        $this->em->persist($flag);
        $this->em->flush();

        $id = $flag->getId();
        $this->em->remove($flag);
        $this->em->flush();

        $repo = $this->em->getRepository(FeatureFlag::class);
        $this->assertNull($repo->find($id));
    }
}
