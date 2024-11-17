<?php

namespace AppBundle\Service;
use AppBundle\Entity\Initiative;
use AppBundle\Entity\Category;
use AppBundle\Enum\CategoryEnum;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
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
    private $router;

    public function __construct(EntityManagerInterface $entityManager, UrlGeneratorInterface $router, string $lkin_access_token, string $lkin_organization, string $fb_app, string $fb_secret, string $fb_token, string $fb_site)
    {
        $this->client = new Client();
        $this->lkin_access_token = $lkin_access_token;
        $this->lkin_organization = $lkin_organization;
        $this->fb_secret = $fb_secret;
        $this->fb_app = $fb_app;
        $this->fb_token = $fb_token;
        $this->fb_site = $fb_site;
        $this->em = $entityManager;
        $this->router = $router; 
    }

    public function postUpdate(Initiative $initiative)
    {

        $type = $initiative->getType();
        $category_id = $initiative->getCategory();
        $category = $this->em->getRepository('AppBundle\Entity\Category')->findOneBy(array('id' => $category_id));
        //only post for global initiatives
        if ($category->getType() === 0) {

            $title = $initiative->getTitle();
            $source = $this->router->generate('initiative_show', ['id' => $initiative->getId(),'slug' => $initiative->getSlug(),],UrlGeneratorInterface::ABSOLUTE_URL);
            if ($type === 0 ) {
                $message = "A new proposal has been published at the World Parliament Experiment. Join the discussion to make your voice as a Global Citizen heard!";
            } elseif ($type === 1 )   {
                $message = "Voting has started at the World Parliament Experiment. Make sure to exercise your voting right as a Global Citizen!";
            }
            
            $this->postLinkedInUpdate($message,$source,$title);
            $this->postFacebookUpdate($message,$source,$title);
        }

    }

    public function postLinkedInUpdate($message,$source,$title)
    {
        try {
            $response = $this->client->request('POST', "https://api.linkedin.com/rest/posts/", [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '. $this->lkin_access_token,
                    'VersionX-Restli-Protocol-Version' => '2.0.0',
                    'Linkedin-Version' => '202401'
                ],
                'json' => [
                    'author' => "urn:li:organization:". $this->lkin_organization,
                    'commentary' => $message,
                    'visibility' => "PUBLIC",
                    'distribution' => [
                        'feedDistribution'=> "MAIN_FEED",
                        'targetEntities'=> [],
                        'thirdPartyDistributionChannels'=> []
                    ],
                    'content' => [
                        'article'=> [
                            'source' => $source,
                            'title' => $title
                        ],
                    ],
                    'lifecycleState' => "PUBLISHED",
                    'isReshareDisabledByAuthor' => false
                ]
            ]);
            return $response->getBody();
        } catch(GuzzleException $e) {
            echo $e;
        } 
    }
    
    public function postFacebookUpdate($message,$source,$title)
    {
        $message = $message."\n".$title."\n".$source;
        $imageUrl = 'https://world-parliament.org/assets/img/logo.png';

        try {
            $response = $this->client->request('POST', "https://graph.facebook.com/{$this->fb_site}/photos", [  
                "form_params" => [
                    "url" => $imageUrl,
                    "published" => false,
                    "access_token" => $this->fb_token
                ]
            ]);
            $responseData = json_decode($response->getBody(), true);
            $photoId = $responseData['id']; // Assign the photo ID to a variable
        } catch(GuzzleException $e) {
            echo $e;
        } 

        try {
            $response = $this->client->request('POST', "https://graph.facebook.com/{$this->fb_site}/feed", [    
                'form_params' => [
                    'message' => $message,
                    "attached_media"=> [
                            "media_fbid" => $photoId
                    ],
                    'access_token' => $this->fb_token,
                ]
            ]);
            return $response->getBody();
        } catch(GuzzleException $e) {
            echo $e;
        } 
    }
}