<?php

declare(strict_types=1);

namespace Platform\Config;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use Platform\Config\Exception\ConfigException;

/**
 * @covers \Platform\Config\ConfigurationManager
 */
#[AllowMockObjectsWithoutExpectations]
class ConfigurationManagerTest extends TestCase
{
    private ConfigurationManager $config;

    protected function setUp(): void
    {
        $this->config = new ConfigurationManager();
    }

    public function testGetDefaultValueWhenKeyNotExists(): void
    {
        $result = $this->config->get('nonexistent.key', 'default');
        $this->assertEquals('default', $result);
    }

    public function testGetNullWhenKeyNotExistsAndNoDefault(): void
    {
        $result = $this->config->get('nonexistent.key');
        $this->assertNull($result);
    }

    public function testSetAndGetConfigurationValue(): void
    {
        $this->config->set('database.host', 'localhost');
        $result = $this->config->get('database.host');
        $this->assertEquals('localhost', $result);
    }

    public function testSetAndGetNestedConfigurationValue(): void
    {
        $this->config->set('database.connection.host', 'localhost');
        $result = $this->config->get('database.connection.host');
        $this->assertEquals('localhost', $result);
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        $this->config->set('database.host', 'localhost');
        $this->assertTrue($this->config->has('database.host'));
    }

    public function testHasReturnsFalseForNonexistentKey(): void
    {
        $this->assertFalse($this->config->has('nonexistent.key'));
    }

    public function testAllReturnsAllConfiguration(): void
    {
        $this->config->set('database.host', 'localhost');
        $this->config->set('database.port', 5432);
        $this->config->set('app.name', 'MyApp');
        
        $all = $this->config->all();
        $this->assertIsArray($all);
        $this->assertArrayHasKey('database', $all);
        $this->assertArrayHasKey('app', $all);
        $this->assertEquals('localhost', $all['database']['host']);
        $this->assertEquals(5432, $all['database']['port']);
        $this->assertEquals('MyApp', $all['app']['name']);
    }

    public function testLoadFromFileJson(): void
    {
        // Create a temporary JSON file with proper extension
        $tempFile = tempnam(sys_get_temp_dir(), 'config_') . '.json';
        file_put_contents($tempFile, '{"database":{"host":"localhost","port":5432}}');
        
        $config = new ConfigurationManager();
        $config->loadFromFile($tempFile);
        
        $this->assertEquals('localhost', $config->get('database.host'));
        $this->assertEquals(5432, $config->get('database.port'));
        
        // Clean up
        unlink($tempFile);
    }

    public function testLoadFromFilePhp(): void
    {
        // Create a temporary PHP file with proper extension
        $tempFile = tempnam(sys_get_temp_dir(), 'config_') . '.php';
        file_put_contents($tempFile, '<?php return ["database" => ["host" => "localhost", "port" => 5432]];');
        
        $config = new ConfigurationManager();
        $config->loadFromFile($tempFile);
        
        $this->assertEquals('localhost', $config->get('database.host'));
        $this->assertEquals(5432, $config->get('database.port'));
        
        // Clean up
        unlink($tempFile);
    }

    public function testLoadFromFileThrowsExceptionWhenNotFound(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Configuration file not found: /nonexistent/file.json');
        
        $this->config->loadFromFile('/nonexistent/file.json');
    }

    public function testLoadFromFileThrowsExceptionForUnsupportedFormat(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Unsupported configuration file format:');
        
        // Create a temporary file with unsupported extension
        $tempFile = tempnam(sys_get_temp_dir(), 'config') . '.xml';
        
        // Ensure the file exists (touch creates it if it doesn't exist)
        touch($tempFile);
        
        $this->config->loadFromFile($tempFile);
        
        // Clean up
        unlink($tempFile);
    }

    public function testLoadFromEnvironment(): void
    {
        // Save original environment
        $originalEnv = $_ENV ?? [];
        
        // Set test environment variables
        $_ENV['APP_NAME'] = 'TestApp';
        $_ENV['APP_VERSION'] = '1.0.0';
        $_ENV['DATABASE_HOST'] = 'testhost';
        $_ENV['DATABASE_PORT'] = '5433';
        
        $config = new ConfigurationManager();
        $config->loadFromEnvironment();
        
        $this->assertEquals('TestApp', $config->get('app.name'));
        $this->assertEquals('1.0.0', $config->get('app.version'));
        $this->assertEquals('testhost', $config->get('database.host'));
        $this->assertEquals('5433', $config->get('database.port'));
        
        // Restore original environment
        $_ENV = $originalEnv;
    }

    public function testLoadFromEnvironmentWithPrefix(): void
    {
        // Save original environment
        $originalEnv = $_ENV ?? [];
        
        // Set test environment variables
        $_ENV['APP_NAME'] = 'TestApp';
        $_ENV['APP_VERSION'] = '1.0.0';
        $_ENV['DATABASE_HOST'] = 'testhost';
        $_ENV['OTHER_VAR'] = 'should_be_ignored';
        
        $config = new ConfigurationManager();
        $config->loadFromEnvironment([], 'APP_');
        
        $this->assertEquals('TestApp', $config->get('name'));
        $this->assertEquals('1.0.0', $config->get('version'));
        $this->assertNull($config->get('database.host')); // Should not be loaded
        
        // Restore original environment
        $_ENV = $originalEnv;
    }

    public function testConstructorWithInitialConfig(): void
    {
        $initialConfig = [
            'database' => [
                'host' => 'localhost',
                'port' => 5432
            ],
            'app' => [
                'name' => 'TestApp'
            ]
        ];
        
        $config = new ConfigurationManager($initialConfig);
        
        $this->assertEquals('localhost', $config->get('database.host'));
        $this->assertEquals(5432, $config->get('database.port'));
        $this->assertEquals('TestApp', $config->get('app.name'));
    }

    public function testValidateStringNotEmptyThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration key cannot be empty');
        
        // Access private method via reflection
        $reflection = new \ReflectionClass($this->config);
        $method = $reflection->getMethod('validateStringNotEmpty');
        $method->setAccessible(true);
        $method->invoke($this->config, '', 'Configuration key');
    }

    public function testNormalizeArrayKeys(): void
    {
        // Access private method via reflection
        $reflection = new \ReflectionClass($this->config);
        $method = $reflection->getMethod('normalizeArrayKeys');
        $method->setAccessible(true);
        
        $input = [
            'Database' => [
                'Host' => 'localhost',
                'PORT' => 5432
            ],
            'APP_NAME' => 'TestApp',
            0 => 'zero_value'
        ];
        
        $result = $method->invoke($this->config, $input);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('database', $result);
        $this->assertArrayHasKey('app_name', $result);
        $this->assertArrayHasKey('0', $result);
        $this->assertEquals('localhost', $result['database']['host']);
        $this->assertEquals(5432, $result['database']['port']);
        $this->assertEquals('TestApp', $result['app_name']);
        $this->assertEquals('zero_value', $result['0']);
    }
}