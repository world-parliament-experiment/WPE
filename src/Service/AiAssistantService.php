<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class AiAssistantService
{
    private $httpClient;
    private $apiKey;
    private $projectDir;
    private $personas;

    public function __construct(HttpClientInterface $httpClient, string $apiKey, KernelInterface $kernel)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->projectDir = $kernel->getProjectDir();
        $this->loadPersonas();
    }

    private function loadPersonas(): void
    {
        $jsonPath = $this->projectDir . '/config/ai_personas.json';
        if (file_exists($jsonPath)) {
            $jsonContent = file_get_contents($jsonPath);
            $data = json_decode($jsonContent, true);
            $this->personas = $data['personas'] ?? [];
        } else {
            $this->personas = [];
        }
    }

    public function getPersonas(): array
    {
        return $this->personas;
    }

    public function draftProposal(string $userPrompt, string $personaKey = 'neutral'): string
    {
        $persona = $this->personas[$personaKey] ?? $this->personas['neutral'];
        $systemPrompt = $persona['system_prompt'] . " IMPORTANT: You MUST respond in the same language as the user's request. Keep the response concise (maximum 500 words) to fit the form requirements.";

        // Gemini API endpoint
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $this->apiKey;

        $response = $this->httpClient->request('POST', $url, [
            'json' => [
                'system_instruction' => [
                    'parts' => [
                        ['text' => $systemPrompt]
                    ]
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $userPrompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 800,
                ]
            ]
        ]);

        $data = $response->toArray();

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Sorry, the AI could not generate a draft.';
    }
}
