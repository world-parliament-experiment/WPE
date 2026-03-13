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

    public function draftFullInitiative(string $topic, string $personaKey = 'neutral'): array
    {
        $persona = $this->personas[$personaKey] ?? $this->personas['neutral'];
        $prompt = sprintf(
            "Based on your persona as %s, draft a legislative proposal for the World Parliament about: %s. 
            You MUST respond with a JSON object.
            Structure:
            {
              \"title\": \"A concise title\",
              \"description\": \"The full legal text using Markdown (headers, bold, etc.)\"
            }
            IMPORTANT: The response MUST be in the same language as the topic. Return ONLY the raw JSON.",
            $persona['name'],
            $topic
        );

        $result = $this->generateContent($prompt, $personaKey, 'application/json');
        
        $jsonString = $this->cleanJson($result);
        $data = json_decode($jsonString, true);
        
        if (!$data || !isset($data['title']) || !isset($data['description'])) {
            error_log("AI JSON Decode Failed. Raw: " . $result);
            error_log("Cleaned JSON: " . $jsonString);
            // Fallback: If parsing still fails, try to extract parts or just return as is
            return [
                'title' => 'Proposal: ' . mb_substr($topic, 0, 50),
                'description' => $this->markdownToHtml($result)
            ];
        }

        // Convert description markdown to HTML
        $data['description'] = $this->markdownToHtml($data['description']);

        return $data;
    }

    public function generateComment(string $initiativeTitle, string $initiativeDescription, string $personaKey = 'neutral'): string
    {
        $persona = $this->personas[$personaKey] ?? $this->personas['neutral'];
        $prompt = sprintf(
            "You are %s. You are reviewing this legislative proposal for the World Parliament:
            Title: %s
            Content: %s
            
            Write a constructive comment or critique of this proposal from your perspective. 
            Keep it concise (max 100 words).
            Respond in the same language as the proposal.",
            $persona['name'],
            $initiativeTitle,
            $initiativeDescription
        );

        return $this->generateContent($prompt, $personaKey);
    }

    public function generateReply(string $initiativeTitle, string $parentComment, string $personaKey = 'neutral'): string
    {
        $persona = $this->personas[$personaKey] ?? $this->personas['neutral'];
        $prompt = sprintf(
            "You are %s. You are participating in a discussion about this legislative proposal: '%s'.
            Someone left this comment: '%s'
            
            Write a short, engaging reply to this comment from your perspective. 
            You can agree, disagree, or add a new point of view.
            Keep it very concise (max 60 words).
            Respond in the same language as the comment.",
            $persona['name'],
            $initiativeTitle,
            $parentComment
        );

        return $this->generateContent($prompt, $personaKey);
    }

    public function decideReaction(string $title, string $content, string $personaKey = 'neutral'): string
    {
        $persona = $this->personas[$personaKey] ?? $this->personas['neutral'];
        $prompt = sprintf(
            "You are %s. You are reading this content related to the World Parliament:
            Title/Context: %s
            Content: %s
            
            Based on your persona, would you like, dislike, or stay neutral towards this?
            Respond with ONLY one word: 'like', 'dislike', or 'neutral'.",
            $persona['name'],
            $title,
            $content
        );

        $result = strtolower(trim($this->generateContent($prompt, $personaKey)));
        
        if (in_array($result, ['like', 'dislike', 'neutral'])) {
            return $result;
        }

        return 'neutral';
    }

    private function generateContent(string $userPrompt, string $personaKey = 'neutral', string $mimeType = 'text/plain'): string
    {
        $persona = $this->personas[$personaKey] ?? $this->personas['neutral'];
        $systemPrompt = $persona['system_prompt'];

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $this->apiKey;

        $generationConfig = [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 4000,
        ];

        if ($mimeType === 'application/json') {
            $generationConfig['responseMimeType'] = 'application/json';
        }

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
                'generationConfig' => $generationConfig
            ]
        ]);

        $data = $response->toArray();

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Sorry, the AI could not generate content.';
    }

    private function cleanJson(string $text): string
    {
        // Find the first { and last }
        $firstBracket = strpos($text, '{');
        $lastBracket = strrpos($text, '}');

        if ($firstBracket === false || $lastBracket === false) {
            return $text;
        }

        $json = substr($text, $firstBracket, $lastBracket - $firstBracket + 1);
        
        // Remove common AI prefixes/suffixes like ```json or ```
        $json = preg_replace('/^```json\s*/', '', $json);
        $json = preg_replace('/\s*```$/', '', $json);
        
        return trim($json);
    }

    private function markdownToHtml(string $markdown): string
    {
        $html = $markdown;

        // Headers
        $html = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $html);

        // Bold
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/__(.*?)__/', '<strong>$1</strong>', $html);

        // Italic
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);
        $html = preg_replace('/_(.*?)_/', '<em>$1</em>', $html);

        // Newlines to <br />
        $html = nl2br($html);
        $html = preg_replace('/<\/h([1-6])><br \/>/', '</h$1>', $html);
        $html = preg_replace('/<br \/>\s*<h([1-6])>/', '<h$1>', $html);

        return $html;
    }
}
