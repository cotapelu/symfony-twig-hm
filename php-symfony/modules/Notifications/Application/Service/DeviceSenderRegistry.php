<?php declare(strict_types=1);

namespace App\Notifications\Application\Service;

use InvalidArgumentException;

class DeviceSenderRegistry
{
    /** @var DeviceSenderInterface[] */
    private array $senders = [];

    /**
     * @param iterable<DeviceSenderInterface> $senders
     */
    public function __construct(iterable $senders)
    {
        foreach ($senders as $sender) {
            if (!$sender instanceof DeviceSenderInterface) {
                throw new InvalidArgumentException('All senders must implement DeviceSenderInterface');
            }
            $this->senders[] = $sender;
        }
    }

    public function getSenderForType(string $deviceType): ?DeviceSenderInterface
    {
        foreach ($this->senders as $sender) {
            if ($sender->supports($deviceType)) {
                return $sender;
            }
        }
        return null;
    }
}
