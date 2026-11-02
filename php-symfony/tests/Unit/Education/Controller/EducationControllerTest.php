<?php

declare(strict_types=1);

namespace Tests\Unit\Education\Controller;

use App\Education\Controller\EducationController;
use App\Education\Service\EducationService;
use App\Education\Entity\Course;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class EducationControllerTest extends TestCase
{
    private EducationService $serviceMock;
    private EducationController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(EducationService::class);
        $this->controller = new EducationController($this->serviceMock);
    }

    private function invokePrivate(object $obj, string $method, array $args = [])
    {
        $ref = new ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }

    private function setAuthenticatedUser(int $userId = 123): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn((string)$userId);
        $this->controller->setTestUser($user);
        $this->controller->setContainer(new ContainerBuilder());
    }

    // ==================== Pagination ====================

    public function testExtractPagination_Defaults(): void
    {
        $request = Request::create('/api/education/courses');
        $result = $this->invokePrivate($this->controller, 'extractPagination', [$request]);
        $this->assertSame(1, $result['page']);
        $this->assertSame(20, $result['limit']);
        $this->assertSame(0, $result['offset']);
    }

    public function testExtractPagination_Custom(): void
    {
        $request = Request::create('/api/education/courses', 'GET', ['page' => '2', 'limit' => '10']);
        $result = $this->invokePrivate($this->controller, 'extractPagination', [$request]);
        $this->assertSame(2, $result['page']);
        $this->assertSame(10, $result['limit']);
        $this->assertSame(10, $result['offset']);
    }

    // ==================== Validation Helpers ====================

    public function testRequireTitle_Missing_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Title is required');
        $this->invokePrivate($this->controller, 'requireTitle', [['other' => 'data']]);
    }

    public function testRequireTitle_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'requireTitle', [['title' => 'My Course']]);
        $this->assertTrue(true);
    }

    public function testRequireCategory_Missing_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid or missing category');
        $this->invokePrivate($this->controller, 'requireCategory', [['other' => 'data']]);
    }

    public function testRequireCategory_Invalid_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid or missing category');
        $this->invokePrivate($this->controller, 'requireCategory', [['category' => 'unknown']]);
    }

    public function testRequireCategory_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'requireCategory', [['category' => 'programming']]);
        $this->assertTrue(true);
    }

    public function testValidateLevelIfSet_Invalid_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid level');
        $this->invokePrivate($this->controller, 'validateLevelIfSet', [['level' => 'expert']]);
    }

    public function testValidateLevelIfSet_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validateLevelIfSet', [['level' => 'advanced']]);
        $this->assertTrue(true);
    }

    public function testValidateDurationIfSet_TooShort_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duration must be between 15 and 1440 minutes');
        $this->invokePrivate($this->controller, 'validateDurationIfSet', [['duration' => 10]]);
    }

    public function testValidateDurationIfSet_TooLong_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duration must be between 15 and 1440 minutes');
        $this->invokePrivate($this->controller, 'validateDurationIfSet', [['duration' => 2000]]);
    }

    public function testValidateDurationIfSet_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validateDurationIfSet', [['duration' => 60]]);
        $this->assertTrue(true);
    }

    public function testValidatePriceIfSet_Negative_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Price must be between 0 and 99999.99');
        $this->invokePrivate($this->controller, 'validatePriceIfSet', [['price' => -10]]);
    }

    public function testValidatePriceIfSet_TooHigh_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Price must be between 0 and 99999.99');
        $this->invokePrivate($this->controller, 'validatePriceIfSet', [['price' => 100000]]);
    }

    public function testValidatePriceIfSet_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validatePriceIfSet', [['price' => 50]]);
        $this->assertTrue(true);
    }

    public function testValidateCourseCreationData_MissingTitle_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Title is required');
        $this->invokePrivate($this->controller, 'validateCourseCreationData', [['category' => 'programming']]);
    }

    public function testValidateCourseCreationData_InvalidCategory_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid or missing category');
        $this->invokePrivate($this->controller, 'validateCourseCreationData', [['title' => 'Test', 'category' => 'bad']]);
    }

    public function testValidateCourseCreationData_Valid_NoException(): void
    {
        $data = ['title' => 'Test', 'category' => 'programming', 'duration' => 60, 'price' => 100];
        $this->invokePrivate($this->controller, 'validateCourseCreationData', [$data]);
        $this->assertTrue(true);
    }

    public function testValidateTitleIfSet_Empty_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Title cannot be empty');
        $this->invokePrivate($this->controller, 'validateTitleIfSet', [['title' => '']]);
    }

    public function testValidateCategoryIfSet_Invalid_Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid category');
        $this->invokePrivate($this->controller, 'validateCategoryIfSet', [['category' => 'unknown']]);
    }

    // ==================== Endpoints ====================

    public function testCreateCourse_Unauthenticated_Returns401(): void
    {
        $this->controller->setContainer(new ContainerBuilder());
        $request = Request::create('/api/education/courses', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['title' => 'Course', 'category' => 'programming']));
        $response = $this->controller->createCourse($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testCreateCourse_ValidationError_Returns400(): void
    {
        $this->setAuthenticatedUser();
        $data = ['title' => '', 'category' => 'programming'];
        $request = Request::create('/api/education/courses', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $response = $this->controller->createCourse($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
    }

    public function testCreateCourse_Success_Returns201(): void
    {
        $course = new Course(123, 'My Course', 'programming', 60, 0.0);
        $course->setId(100);
        $this->serviceMock->expects($this->once())
            ->method('createCourse')
            ->with($this->equalTo(123), $this->equalTo('My Course'), $this->equalTo('programming'))
            ->willReturn($course);

        $this->setAuthenticatedUser(123);
        $data = ['title' => 'My Course', 'category' => 'programming'];
        $request = Request::create('/api/education/courses', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $response = $this->controller->createCourse($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
    }

    public function testGetCourse_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser();
        $this->serviceMock->method('getCourse')->with(999)->willReturn(null);
        $response = $this->controller->getCourse(999);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetCourse_Found_Returns200(): void
    {
        $course = new Course(1, 'My Course', 'programming', 60, 0.0);
        $course->setId(100);
        $this->serviceMock->method('getCourse')->with(100)->willReturn($course);
        $this->setAuthenticatedUser();
        $response = $this->controller->getCourse(100);
        $this->assertSame(200, $response->getStatusCode());
    }
}