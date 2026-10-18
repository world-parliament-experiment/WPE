<?php

namespace Tests\App\Service;

use App\Service\AiAssistantService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AiAssistantServiceTest extends TestCase
{
    public function testDraftProposal(): void
    {
        // 1. Mock HttpClient and Response
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')->willReturn([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => "Drafted proposal body text."
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->stringContains('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent'),
                $this->callback(function ($options) {
                    // Check if the system instruction or prompts are set correctly
                    $json = $options['json'];
                    return isset($json['system_instruction']['parts'][0]['text']) &&
                           isset($json['contents'][0]['parts'][0]['text']) &&
                           $json['generationConfig']['temperature'] === 0.7;
                })
            )
            ->willReturn($mockResponse);

        // 2. Mock Kernel
        $mockKernel = $this->createMock(KernelInterface::class);
        $mockKernel->method('getProjectDir')->willReturn(dirname(dirname(__DIR__)));

        // 3. Instantiate Service
        $service = new AiAssistantService($mockHttpClient, 'test-api-key', $mockKernel);

        // 4. Test draftProposal
        $result = $service->draftProposal('Test Prompt', 'neutral');

        $this->assertEquals("Drafted proposal body text.", $result);
    }

    public function testDraftCounterProposal(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')->willReturn([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'title' => 'Revised Counter Proposal Title',
                                    'description' => '### Proposal\nDetailed counter proposal text.'
                                ])
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $mockKernel = $this->createMock(KernelInterface::class);
        $mockKernel->method('getProjectDir')->willReturn(dirname(dirname(__DIR__)));

        $service = new AiAssistantService($mockHttpClient, 'test-api-key', $mockKernel);

        $result = $service->draftCounterProposal(
            'Original Title',
            'Original Description',
            'We need more focus on local communities.',
            'neutral'
        );

        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertEquals('Revised Counter Proposal Title', $result['title']);
        $this->assertStringContainsString('Detailed counter proposal text.', $result['description']);
    }

    public function testInventTopicWithExistingTitlesExclusion(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')->willReturn([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => "Global Oceanic Waste Cleanup Mechanism"
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->anything(),
                $this->callback(function ($options) {
                    $prompt = $options['json']['contents'][0]['parts'][0]['text'];
                    return str_contains($prompt, 'The following proposals already exist in this category')
                        && str_contains($prompt, 'Carbon Tax Initiative')
                        && str_contains($prompt, 'Plastic Ban Bill');
                })
            )
            ->willReturn($mockResponse);

        $mockKernel = $this->createMock(KernelInterface::class);
        $mockKernel->method('getProjectDir')->willReturn(dirname(dirname(__DIR__)));

        $service = new AiAssistantService($mockHttpClient, 'test-api-key', $mockKernel);

        $topic = $service->inventTopic('neutral', 'Environment', [
            'Carbon Tax Initiative',
            'Plastic Ban Bill'
        ]);

        $this->assertEquals("Global Oceanic Waste Cleanup Mechanism", $topic);
    }

    public function testCheckSemanticDuplicateReturnsTrueWhenDuplicate(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')->willReturn([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'is_duplicate' => true,
                                    'duplicate_of_id' => 42,
                                    'reason' => 'Both proposals establish an identical global carbon tax.'
                                ])
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $mockKernel = $this->createMock(KernelInterface::class);
        $mockKernel->method('getProjectDir')->willReturn(dirname(dirname(__DIR__)));

        $service = new AiAssistantService($mockHttpClient, 'test-api-key', $mockKernel);

        $result = $service->checkSemanticDuplicate(
            'Global Carbon Tax Framework',
            'Impose mandatory tax on fossil fuel carbon emissions.',
            [
                ['id' => 42, 'title' => 'International Carbon Pricing Scheme', 'description' => 'A tax on carbon emissions.']
            ],
            'neutral',
            'Economy'
        );

        $this->assertTrue($result['is_duplicate']);
        $this->assertEquals(42, $result['duplicate_of_id']);
        $this->assertStringContainsString('Both proposals establish an identical global carbon tax', $result['reason']);
    }

    public function testCheckSemanticDuplicateReturnsFalseWhenUnique(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')->willReturn([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'is_duplicate' => false,
                                    'duplicate_of_id' => null,
                                    'reason' => 'Proposals cover different issues.'
                                ])
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $mockKernel = $this->createMock(KernelInterface::class);
        $mockKernel->method('getProjectDir')->willReturn(dirname(dirname(__DIR__)));

        $service = new AiAssistantService($mockHttpClient, 'test-api-key', $mockKernel);

        $result = $service->checkSemanticDuplicate(
            'Deep Sea Marine Protected Areas',
            'Establish international marine sanctuaries in international waters.',
            [
                ['id' => 10, 'title' => 'Global Space Traffic Management', 'description' => 'Regulate satellites in orbit.']
            ],
            'neutral',
            'Environment'
        );

        $this->assertFalse($result['is_duplicate']);
        $this->assertNull($result['duplicate_of_id']);
    }

    public function testCheckSemanticDuplicateReturnsFalseImmediatelyWhenListIsEmpty(): void
    {
        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->expects($this->never())->method('request');

        $mockKernel = $this->createMock(KernelInterface::class);
        $mockKernel->method('getProjectDir')->willReturn(dirname(dirname(__DIR__)));

        $service = new AiAssistantService($mockHttpClient, 'test-api-key', $mockKernel);

        $result = $service->checkSemanticDuplicate(
            'Any Title',
            'Any Description',
            [],
            'neutral',
            'Environment'
        );

        $this->assertFalse($result['is_duplicate']);
        $this->assertNull($result['duplicate_of_id']);
    }
}
