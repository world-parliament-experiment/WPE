<?php

namespace AppBundle\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class SocialmediaPoster
{

    private $fb_app;
    private $fb_secret;
    private $fb_token;
    private $fb_site;
    private $client;
    private $lkin_access_token;
    private $lkin_organization;

    public function __construct(string $lkin_access_token, string $lkin_organization, string $fb_app, string $fb_secret, string $fb_token, string $fb_site)
    {
        $this->client = new Client();
        $this->lkin_access_token = $lkin_access_token;
        $this->lkin_organization = $lkin_organization;
        $this->fb_secret = $fb_secret;
        $this->fb_app = $fb_app;
        $this->fb_token = $fb_token;
        $this->fb_site = $fb_site;
    }

    public function postUpdate($message,$source,$title)
    {
        $this->postLinkedInUpdate($message,$source,$title);
        $this->postFacebookUpdate($message,$source,$title);

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

        try {
            $response = $this->client->request('POST', "https://graph.facebook.com/{$this->fb_site}/feed", [
                'form_params' => [
                    'message' => $message,
                    'access_token' => $this->fb_token,
                ]
            ]);
            return $response->getBody();
        } catch(GuzzleException $e) {
            echo $e;
        } 
    }
}