<?php declare(strict_types=1);

namespace App\Tests\Unit\DigitalWallet\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\DigitalWallet\Entity\WalletTransaction;
use App\DigitalWallet\Entity\DigitalWallet;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class WalletTransactionTest extends TestCase
{
    private function createTransaction(array $overrides = []): WalletTransaction
    {
        $tx = new WalletTransaction();
        // Set required non-null fields
        $tx->setWalletId('wallet-123');
        $tx->setTransactionType('deposit');
        $tx->setAmount(1000);
        // Apply overrides
        foreach ($overrides as $prop => $value) {
            $setter = 'set' . ucfirst($prop);
            if (method_exists($tx, $setter)) {
                $tx->$setter($value);
            }
        }
        return $tx;
    }

    public function testConstructorInitializesDefaults(): void
    {
        $tx = $this->createTransaction([]);
        $this->assertNull($tx->getId());
        $this->assertSame('VND', $tx->getCurrency());
        $this->assertSame('pending', $tx->getStatus());
        $this->assertSame(0, $tx->getFee());
        $this->assertInstanceOf(\DateTimeInterface::class, $tx->getCreatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $tx = $this->createTransaction([]);

        $wallet = $this->createMock(DigitalWallet::class);
        // The entity has a wallet property but I didn't see a setter in the snippet. Let's check.
        // Actually there is no setWallet method shown; maybe it's set via reflection or the property is directly accessible? Let's check full file.
        // For test purposes, we'll avoid setting wallet if setter doesn't exist.

        $tx->setWalletId('wallet-999');
        $this->assertSame('wallet-999', $tx->getWalletId());

        $tx->setTransactionType('withdrawal');
        $this->assertSame('withdrawal', $tx->getTransactionType());

        $tx->setAmount(500);
        $this->assertSame(500, $tx->getAmount());

        $tx->setCurrency('USD');
        $this->assertSame('USD', $tx->getCurrency());

        $tx->setStatus('completed');
        $this->assertSame('completed', $tx->getStatus());

        $tx->setDescription('Test description');
        $this->assertSame('Test description', $tx->getDescription());

        $tx->setReferenceId('ref-123');
        $this->assertSame('ref-123', $tx->getReferenceId());

        $tx->setRecipientWalletId('wallet-456');
        $this->assertSame('wallet-456', $tx->getRecipientWalletId());

        $tx->setFee(10);
        $this->assertSame(10, $tx->getFee());
    }

    public function testNullableProperties(): void
    {
        $tx = $this->createTransaction([]);

        $this->assertNull($tx->getDescription());
        $tx->setDescription(null);
        $this->assertNull($tx->getDescription());

        $this->assertNull($tx->getReferenceId());
        $tx->setReferenceId(null);
        $this->assertNull($tx->getReferenceId());

        $this->assertNull($tx->getRecipientWalletId());
        $tx->setRecipientWalletId(null);
        $this->assertNull($tx->getRecipientWalletId());

        $this->assertSame(0, $tx->getFee()); // default 0
        $tx->setFee(null); // Actually setFee(?int) allows null
        $this->assertNull($tx->getFee());
    }
}
