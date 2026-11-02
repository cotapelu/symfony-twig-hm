<?php

declare(strict_types=1);

namespace App\Audit\Service;

class SimpleAuditLogger
{
    private string $logFile;

    public function __construct(string $projectDir)
    {
        $this->logFile = rtrim($projectDir, '/') . '/audit.log';
    }

    /**
     * Log an audit entry
     *
     * @param string $userId User performing the action (or 'system')
     * @param string $action Action type: create, update, delete
     * @param string $entity Entity class name
     * @param array $data Additional data (e.g., change set)
     */
    public function log(string $userId, string $action, string $entity, array $data = [])
    {
        $entry = [
            'timestamp' => (new \DateTime())->format('c'),
            'user_id' => $userId,
            'action' => $action,
            'entity' => $entity,
            'data' => $data,
        ];
        // Ensure log directory exists
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->logFile, json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }
}
