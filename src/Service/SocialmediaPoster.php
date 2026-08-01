<?php

namespace App\Service;

use App\Entity\Initiative;
use App\Entity\Category;
use App\Enum\CategoryEnum;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SocialmediaPoster
{
    private $fb_app;
    private $fb_secret;
    private $fb_token;
    private $fb_site;
    private $client;
    private $lkin_access_token;
    private $lkin_organization;
    private $entityManager;
    private $em;
    private $router;
    private $projectDir;

    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $router,
        string $lkin_access_token,
        string $lkin_organization,
        string $fb_app,
        string $fb_secret,
        string $fb_token,
        string $fb_site,
        string $projectDir = '',
        Client $client = null
    ) {
        $this->client = $client ?? new Client();
        $this->lkin_access_token = $lkin_access_token;
        $this->lkin_organization = $lkin_organization;
        $this->fb_secret = $fb_secret;
        $this->fb_app = $fb_app;
        $this->fb_token = $fb_token;
        $this->fb_site = $fb_site;
        $this->entityManager = $entityManager;
        $this->em = $entityManager;
        $this->router = $router; 
        $this->projectDir = !empty($projectDir) ? $projectDir : realpath(__DIR__ . '/../../');
    }

    public function postUpdate(Initiative $initiative)
    {
        $type = $initiative->getType();
        $category_id = $initiative->getCategory();
        $category = $this->em->getRepository(Category::class)->findOneBy(['id' => $category_id]);
        
        // Only post for global initiatives
        if ($category && $category->getType() === 0) {
            $title = $initiative->getTitle();
            $source = $this->router->generate('initiative_show', ['id' => $initiative->getId(), 'slug' => $initiative->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
            
            if ($type === 0) {
                $message = "A new proposal has been published at the World Parliament Experiment. Join the discussion to make your voice as a Global Citizen heard!";
            } elseif ($type === 1) {
                $message = "Voting has started at the World Parliament Experiment. Make sure to exercise your voting right as a Global Citizen!";
            } else {
                return;
            }

            // Path to the local category background image
            $localFilePath = $this->projectDir . '/public/assets/img/category/K_' . $category->getId() . '_small.jpg';

            // Generate absolute pictureUrl
            $pictureUrl = null;
            $parsedUrl = parse_url($source);
            if (isset($parsedUrl['scheme'], $parsedUrl['host'])) {
                $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                if (isset($parsedUrl['port'])) {
                    $baseUrl .= ':' . $parsedUrl['port'];
                }
                $pictureUrl = $baseUrl . '/assets/img/category/K_' . $category->getId() . '_small.jpg';
            }

            $this->postLinkedInUpdate($message, $source, $title, $localFilePath);
            $this->postFacebookUpdate($message, $source, $title, $pictureUrl);
        }
    }

    public function postLinkedInUpdate($message, $source, $title, $localFilePath = null)
    {
        if (empty($this->lkin_access_token) || empty($this->lkin_organization)) {
            error_log("LinkedIn tokens not configured, skipping post.");
            return false;
        }

        $imageUrn = null;
        if ($localFilePath && file_exists($localFilePath)) {
            try {
                $initResponse = $this->client->request('POST', 'https://api.linkedin.com/rest/images?action=initializeUpload', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->lkin_access_token,
                        'LinkedIn-Version' => '202601',
                        'X-Restli-Protocol-Version' => '2.0.0',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'initializeUploadRequest' => [
                            'owner' => "urn:li:organization:" . $this->lkin_organization,
                        ],
                    ],
                ]);
                $initData = json_decode($initResponse->getBody(), true);
                $uploadUrl = $initData['value']['uploadUrl'] ?? null;
                $tempUrn = $initData['value']['image'] ?? null;

                if ($uploadUrl && $tempUrn) {
                    $uploadResponse = $this->client->request('PUT', $uploadUrl, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->lkin_access_token,
                            'Content-Type' => 'image/jpeg',
                        ],
                        'body' => fopen($localFilePath, 'r'),
                    ]);
                    if ($uploadResponse->getStatusCode() === 201 || $uploadResponse->getStatusCode() === 200) {
                        $imageUrn = $tempUrn;
                    }
                }
            } catch (\Exception $e) {
                error_log("LinkedIn image upload error: " . $e->getMessage());
            }
        }

        try {
            $articleData = [
                'source' => $source,
                'title' => $title
            ];
            if ($imageUrn) {
                $articleData['thumbnail'] = $imageUrn;
            }

            $response = $this->client->request('POST', "https://api.linkedin.com/rest/posts/", [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->lkin_access_token,
                    'X-Restli-Protocol-Version' => '2.0.0',
                    'Linkedin-Version' => '202601'
                ],
                'json' => [
                    'author' => "urn:li:organization:" . $this->lkin_organization,
                    'commentary' => $message,
                    'visibility' => "PUBLIC",
                    'distribution' => [
                        'feedDistribution' => "MAIN_FEED",
                        'targetEntities' => [],
                        'thirdPartyDistributionChannels' => []
                    ],
                    'content' => [
                        'article' => $articleData,
                    ],
                    'lifecycleState' => "PUBLISHED",
                    'isReshareDisabledByAuthor' => false
                ]
            ]);
            return $response->getBody();
        } catch (GuzzleException $e) {
            error_log("LinkedIn API error: " . $e->getMessage());
            return false;
        } 
    }
    
    public function postFacebookUpdate($message, $source, $title, $pictureUrl = null)
    {
        if (empty($this->fb_token) || empty($this->fb_site)) {
            error_log("Facebook tokens not configured, skipping post.");
            return false;
        }
        $fullMessage = $message . "\n" . $title . "\n" . $source;

        try {
            $formParams = [
                'message' => $fullMessage,
                'access_token' => $this->fb_token,
            ];
            if ($pictureUrl) {
                $formParams['link'] = $source;
                $formParams['picture'] = $pictureUrl;
            }

            $response = $this->client->request('POST', "https://graph.facebook.com/{$this->fb_site}/feed", [    
                'form_params' => $formParams
            ]);
            return $response->getBody();
        } catch (GuzzleException $e) {
            error_log("Facebook API error: " . $e->getMessage());
            return false;
        } 
    }
}
