<?php

namespace Rundeck;

use Rundeck\Exceptions\ResourceNotFoundException;
use Rundeck\Exceptions\ResoureAlreadyExistsException;
use Rundeck\Exceptions\InvallidRequestException;

/**
 * Class HttpClient
 * @package Rundeck
 */
class HttpClient
{
    /**
     * @var \GuzzleHttp\Client
     */
    private $client;
    /**
     * @var string
     */
    private $endpoint;
    /**
     * @var string
     */
    private $authToken;
    /**
     * @var int
     */
    private $version;

    /**
     * @param $endpoint
     * @param $authToken
     * @param $version
     */
    public function setAuth($endpoint, $authToken, $version)
    {
        $this->endpoint = trim($endpoint, "/");
        $this->authToken = $authToken;
        $this->version = $version;
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        if (isset($this->client)) {
            return $this->client;
        } else {
            $this->client = new \GuzzleHttp\Client();
            return $this->client;
        }
    }

    /**
     * @param mixed $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @param $uri
     * @param $alt
     * @return array
     */
    public function get($uri, $alt)
    {
        $options = ['headers'=> ['Accept' => 'application/xml']];

        if ($alt == "json") {
            $options = ['headers'=> ['Accept' => 'application/json']];
        }
        $uri = $this->endpoint. "/api/". $this->version .$uri."?authtoken=".$this->authToken;

        $response = $this->getClient()->request('GET', $uri, $options);
        $xml = simplexml_load_string($response->getBody());
        $json = json_encode($xml);
        $data = json_decode($json, true);

        return $data;
    }

    public function post($uri,$body,$alt)
    {
        try {
            $options = [
                'headers' => ['Content-Type' => 'application/xml'],
                'body' => $body
            ];
            if ($alt == "json") {
                $options['headers'] = ['Content-Type' => 'application/json'];
            }
            $uri = $this->endpoint . "/api/" . $this->version . $uri . "?authtoken=" . $this->authToken;

            $response = $this->getClient()->request('POST', $uri, $options);
            $xml = simplexml_load_string($response->getBody());
            $json = json_encode($xml);
            $data = json_decode($json, true);
            return $data;
        } catch (\GuzzleHttp\Exception\ClientException $ex){
            $translated= $this->translateException($ex,$alt);
            throw $translated;
        }
    }


    protected function translateException(\GuzzleHttp\Exception\ClientException $exception,$alt){
        $message = $exception->getMessage();
        if ($exception->hasResponse()) {
            $responceBody = $exception->getResponse()->getBody() . '';
            if ($alt == "json") {
                $body = json_decode($exception->getResponse()->getBody(), true);
            } else {
                $xml = simplexml_load_string($exception->getResponse()->getBody());
                $json = json_encode($xml);
                $body = json_decode($json, true);
            }
            $message = $body['error']['message'];
        }
        switch ($exception->getCode()){
            case 400 : return new InvallidRequestException($message);
            case 404 : return new ResourceNotFoundException($message);
            case 409 : return new ResoureAlreadyExistsException($message);
        }
    }
}
