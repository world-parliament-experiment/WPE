<?php

namespace AppBundle\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class FacebookPoster
{
    private $fb_app;
    private $fb_secret;
    private $fb_token;
    private $fb_site;
    private $client;

    public function __construct(string $fb_app, string $fb_secret, string $fb_token, string $fb_site)
    {
        $this->client = new Client();
        $this->fb_secret = $fb_secret;
        $this->fb_app = $fb_app;
        $this->fb_token = $fb_token;
        $this->fb_site = $fb_site;
    }

    public function postUpdate($message)
    {
        $client = new Client();
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