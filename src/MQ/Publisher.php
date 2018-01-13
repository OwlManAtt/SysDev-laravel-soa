<?php

namespace Northwestern\SysDev\SOA\MQ;

use GuzzleHttp;

class Publisher
{
    protected $baseUrl;
    protected $endpointPath;
    protected $username;
    protected $password;
    protected $apiKey;

    protected $lastUrl;
    protected $lastError;

    public function __construct()
    {
        $this->baseUrl = config('nusoa.messageQueue.baseUrl');
        $this->endpointPath = config('nusoa.messageQueue.publishPath');
        $this->username = config('nusoa.messageQueue.username');
        $this->password = config('nusoa.messageQueue.password');
        $this->apiKey = config('nusoa.messageQueue.apiKey');
    } // end __construct

    public function queue($array, $topic)
    {
        return $this->publishJson(json_encode($array), $topic);
    } // end queueRaw

    public function queueJson($json, $topic) {
        return $this->publishJson($json, $topic);
    } // end queueJson

    public function getLastError()
    {
        return $this->lastError;
    } // end getlastError

    public function getLastUrl()
    {
        return $this->lastUrl;
    } // end getLastUrl

    protected function publishJson($message, $topic)
    {
        $url = $this->getPostUrl($topic);

        $client = new GuzzleHttp\Client();
        $request = $client->request('POST', $url, [
            'auth' => [$this->username, $this->password],
            'headers' => [
                'apikey' => $this->apiKey,
            ],
            'http_errors' => false, // don't throw exceptions
            'content-type' => 'text/plain',
            'body' => $message,
        ]);

        if ($request === null) {
            $this->lastError('Request failed. Verify connectivity to ' . $this->baseUrl . ' from the server.');
            return false;
        }

        if ($request->getStatusCode() != 200) {
            $message = 'The request got an error code. HTTP code was ' . $request->getStatusCode();
            if ($request->getBody() != null) {
                $message .= "\nBody was\n\n" . $request->getBody();
            }

            $this->lastError = $message;
            return false;
        }

        if (strpos($request->getBody(), '<ErrorMessage>') !== false) {
            $this->lastError = "The request got an HTTP 200, but the body has an error message.\n\n" . $request->getBody();
            return false;
        }

        return true;
    } // end publishJson

    private function getPostUrl($topic)
    {
        $this->lastUrl = vsprintf('%s/%s/%s', [$this->baseUrl, $this->endpointPath, $topic]);
        return $this->lastUrl;
    } // end getPostUrl
} // end Publisher
