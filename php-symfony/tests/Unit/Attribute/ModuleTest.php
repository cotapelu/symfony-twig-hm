<?php declare(strict_types=1);

namespace App\Tests\Unit\Attribute;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Attribute\Module;
use App\Attribute\Service;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ModuleTest extends TestCase
{
    public function testModuleConstructorAndProperties(): void
    {
        $module = new Module(
            name: 'TestModule',
            dependencies: ['Dep1', 'Dep2'],
            autoLoad: true
        );

        $this->assertSame('TestModule', $module->name);
        $this->assertSame(['Dep1', 'Dep2'], $module->dependencies);
        $this->assertTrue($module->autoLoad);
    }

    public function testModuleDefaults(): void
    {
        $module = new Module(name: 'SimpleModule');

        $this->assertSame('SimpleModule', $module->name);
        $this->assertSame([], $module->dependencies);
        $this->assertTrue($module->autoLoad);
    }

    public function testModuleWithAutoLoadFalse(): void
    {
        $module = new Module(name: 'NoAutoLoad', autoLoad: false);

        $this->assertFalse($module->autoLoad);
    }
}

#[AllowMockObjectsWithoutExpectations]
class ServiceTest extends TestCase
{
    public function testServiceConstructorWithId(): void
    {
        $service = new Service(id: 'my_service', tags: ['tag1', 'tag2']);

        $this->assertSame('my_service', $service->id);
        $this->assertSame(['tag1', 'tag2'], $service->tags);
    }

    public function testServiceConstructorWithoutId(): void
    {
        $service = new Service();

        $this->assertSame('', $service->id);
        $this->assertSame([], $service->tags);
    }

    public function testServiceWithOnlyTags(): void
    {
        $service = new Service(id: null, tags: ['tag']);

        $this->assertSame('', $service->id);
        $this->assertSame(['tag'], $service->tags);
    }
}
