<?php

namespace App\Controller;

use App\Service\AiAssistantService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use JMS\Serializer\SerializerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @Route("/user/ai")
 */
class AiAssistantController extends BaseController
{
    private $aiAssistant;

    public function __construct(AiAssistantService $aiAssistant, SerializerInterface $serializer, ManagerRegistry $managerRegistry)
    {
        parent::__construct($serializer, $managerRegistry);
        $this->aiAssistant = $aiAssistant;
    }

    /**
     * @Route("/draft", name="user_ai_draft", methods={"POST"}, options={"expose"=true})
     */
    public function draftAction(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_decode($request->getContent(), true);
        $prompt = $data['prompt'] ?? '';
        $persona = $data['persona'] ?? 'neutral';

        if (empty($prompt)) {
            return $this->json(['error' => 'Prompt is required'], 400);
        }

        try {
            $draft = $this->aiAssistant->draftProposal($prompt, $persona);
            return $this->json(['draft' => $draft]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @Route("/personas", name="user_ai_get_personas", methods={"GET"}, options={"expose"=true})
     */
    public function getPersonasAction(): JsonResponse
    {
        return $this->json(['personas' => $this->aiAssistant->getPersonas()]);
    }
}
