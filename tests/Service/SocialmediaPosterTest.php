<?php

namespace Tests\App\Service;

use App\Entity\Initiative;
use App\Entity\Category;
use App\Service\SocialmediaPoster;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SocialmediaPosterTest extends TestCase
{
    public function testPostUpdateForGlobalInitiative()
    {
        // 1. Create Mock Guzzle Client with MockHandler for LinkedIn and Facebook API calls
        $mockHandler = new MockHandler([
            // 1. LinkedIn initializeUpload POST
            new Response(200, [], json_encode([
                'value' => [
                    'uploadUrl' => 'https://api.linkedin.com/upload/url',
                    'image' => 'urn:li:image:K_99_small_uploaded'
                ]
            ])),
            // 2. LinkedIn binary PUT upload (returns 201)
            new Response(201, [], ''),
            // 3. LinkedIn post POST
            new Response(200, [], 'linkedin post success'),
            // 4. Facebook post POST
            new Response(200, [], 'facebook post success')
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handlerStack]);

        // 2. Mock Router
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('initiative_show', ['id' => 123, 'slug' => 'test-initiative'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://www.world-parliament.org/initiative/123/test-initiative');

        // 3. Mock Initiative and Category Entities
        $category = $this->createMock(Category::class);
        $category->method('getId')->willReturn(99);
        $category->method('getType')->willReturn(0); // 0 = TYPE_GLOBAL

        $initiative = $this->createMock(Initiative::class);
        $initiative->method('getId')->willReturn(123);
        $initiative->method('getSlug')->willReturn('test-initiative');
        $initiative->method('getType')->willReturn(0); // 0 = proposal published
        $initiative->method('getCategory')->willReturn(99);
        $initiative->method('getTitle')->willReturn('Test Initiative Title');

        // 4. Mock Repository and EntityManager
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => 99])
            ->willReturn($category);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')
            ->with(Category::class)
            ->willReturn($repository);

        // 5. Temporary project directory setup for the local image file check
        $tempDir = sys_get_temp_dir() . '/wpe_test_project';
        @mkdir($tempDir . '/public/assets/img/category', 0777, true);
        $localImagePath = $tempDir . '/public/assets/img/category/K_99_small.jpg';
        file_put_contents($localImagePath, 'fake image binary data');

        // 6. Instantiate SocialmediaPoster and call postUpdate
        $poster = new SocialmediaPoster(
            $em,
            $router,
            'test_lkin_token',
            'test_lkin_org',
            'test_fb_app',
            'test_fb_secret',
            'test_fb_token',
            'test_fb_site',
            $tempDir,
            $client
        );

        $poster->postUpdate($initiative);

        // Verify all queued requests in mock handler were processed
        $this->assertEquals(0, $mockHandler->count(), "All expected API endpoints should be called");

        // Clean up temporary files
        @unlink($localImagePath);
        @rmdir($tempDir . '/public/assets/img/category');
        @rmdir($tempDir . '/public/assets/img');
        @rmdir($tempDir . '/public');
        @rmdir($tempDir);
    }

    public function testPostUpdateIgnoresNonGlobalInitiative()
    {
        // 1. Create Guzzle client (should not make any HTTP calls)
        $mockHandler = new MockHandler([]);
        $handlerStack = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handlerStack]);

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->expects($this->never())->method('generate');

        // 2. Mock Category with non-global type (e.g. 2 = TYPE_NATIONAL)
        $category = $this->createMock(Category::class);
        $category->method('getId')->willReturn(101);
        $category->method('getType')->willReturn(2); // Non-global

        $initiative = $this->createMock(Initiative::class);
        $initiative->method('getCategory')->willReturn(101);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => 101])
            ->willReturn($category);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')
            ->with(Category::class)
            ->willReturn($repository);

        $poster = new SocialmediaPoster(
            $em,
            $router,
            'test_lkin_token',
            'test_lkin_org',
            'test_fb_app',
            'test_fb_secret',
            'test_fb_token',
            'test_fb_site',
            '/tmp',
            $client
        );

        $poster->postUpdate($initiative);

        $this->assertEquals(0, $mockHandler->count(), "No HTTP requests should be sent since the initiative is not global");
    }

    public function testConstructorFallbackForProjectDir()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $router = $this->createMock(UrlGeneratorInterface::class);

        // Instantiating with 8 arguments (leaving $projectDir as default '')
        $poster = new SocialmediaPoster(
            $em,
            $router,
            'test_lkin_token',
            'test_lkin_org',
            'test_fb_app',
            'test_fb_secret',
            'test_fb_token',
            'test_fb_site'
        );

        // Use reflection to inspect the private $projectDir property
        $reflection = new \ReflectionClass($poster);
        $property = $reflection->getProperty('projectDir');
        $property->setAccessible(true);
        $resolvedDir = $property->getValue($poster);

        // It should resolve to the real root path of this project
        $expectedDir = realpath(__DIR__ . '/../../');
        $this->assertEquals($expectedDir, $resolvedDir);
    }
}
