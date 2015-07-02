<?php

namespace Akamai\Client;

use Akamai\Client\Exception\HttpException;
use Akamai\Client\Exception\LogicException;
use Buzz\Browser;
use Buzz\Message\Request;
use Buzz\Message\Response;
use Buzz\Listener\BasicAuthListener;

class Client
{
    /**
     * @var Browser
     */
    protected $browser;

    /**
     * @var string
     */
    protected $clientToken;

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var string
     */
    protected $clientSecret;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var array
     */
    protected $headersToSign;

    public function __construct(Browser $browser, $clientToken, $clientSecret, $accessToken, $baseUrl)
    {
        $this->browser = $browser;
        $this->clientToken = $clientToken;
        $this->clientSecret = $clientSecret;
        $this->accessToken = $accessToken;
        $this->baseUrl = $baseUrl;

        $this->headersToSign = [];
    }

    /**
     * @param int $requestId
     * @return mixed
     */
    public function getPurgeStatus($requestId)
    {
        return $this->doHttpRequest('GET', 'ccu/v2/purges/' . $requestId);
    }

    /**
     * @param array $urls
     * @return mixed
     */
    public function purgeRequest($urls)
    {
        return $this->doHttpRequest('POST', 'ccu/v2/queues/default', $urls);
    }

    /**
     * @return mixed
     */
    public function checkQueueLength()
    {
        return $this->doHttpRequest('GET', 'ccu/v2/queues/default');
    }

    /**
     * @param $method
     * @param $relativeUrl
     * @param array|\stdClass $content
     * @return mixed
     */
    protected function doHttpRequest($method, $relativeUrl, $content = null)
    {
        $relativeUrl = ltrim($relativeUrl, '/');

        $response = new Response();

        $request = new Request($method);
        $request->fromUrl($this->baseUrl . '/' . $relativeUrl);
        $request->addHeader("Content-Type: application/json");
        $request->addHeader('Accept: application/json');
        if ($content) {
            $request->setContent(json_encode(array_filter((array) $content)));
        }

        $timezone = new \DateTimeZone("UTC");
        $datetime = new \DateTime(null, $timezone);
        $timestamp = str_replace('-', '', $datetime->format('c'));
        $nonce = uniqid();
        $authToken = $this->makeAuthHeader($request, $timestamp, $nonce);
        $request->addHeader("Authorization: " . $authToken);
        $request->addHeader("Expect:");

        // $listener = new BasicAuthListener($this->username, $this->password);
        // $listener->preSend($request);

        $this->browser->send($request, $response);

        return $this->getResult($response);
    }

    /**
     * @param \Buzz\Message\Response $response
     *
     * @throws HttpException if status not OK
     * @throws HttpException if content not json
     *
     * @return \stdClass|\stdClass[]|null
     */
    protected function getResult(Response $response)
    {
        if (false == $response->isSuccessful()) {
            throw new HttpException(
                $response->getStatusCode(),
                sprintf("The api call finished with status %s but it was expected 200. Response content:\n\n%s",
                $response->getStatusCode(),
                $response->getContent()
            )
        );
        }

        $content = $response->getContent();
        $result = null;

        if (false == empty($content)) {
            $result = json_decode($response->getContent());
            if (null === $result) {
                throw new LogicException(sprintf(
                    "The response status is successful but the content is not a valid json object:\n\n%s",
                    $response->getContent()
                ));
            }
        }

        return $result;
    }

    protected function makeAuthHeader(Request $request, $timestamp, $nonce)
    {
        $authHeader = [
            'client_token' => $this->clientToken,
            'access_token' => $this->accessToken,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
        ];

        $data = [];
        foreach ($authHeader as $key => $value) {
            $data[] = $key . "=" . $value;
        }

        $dataStr = "EG1-HMAC-SHA256 " . implode(';', $data) . ';';
        // echo 'unsigned authorization header:' . $dataStr . "\n";

        $signedAuthHeader = $dataStr . 'signature=' . $this->signRequest($request, $timestamp, $dataStr);
        // echo 'signed authorization header:' . $signedAuthHeader . "\n";

        return $signedAuthHeader;
    }

    protected function makeDataToSign(Request $request, $authHeader)
    {
        $url = parse_url($request->getHost());
        $data = [
            $request->getMethod(),
            $url['scheme'],
            $url['host'],
            $request->getResource(),
            $this->getCanonicalHeaders($request),
            $this->makeContentHash($request),
            $authHeader
        ];

        $dataToSign = implode("\t", $data);
        // echo 'data to sign:' . $dataToSign . "\n";

        return $dataToSign;
    }

    protected function getCanonicalHeaders(Request $request)
    {
        $headers = array_filter($this->headersToSign, function($header) {
            return (false !== $request->getHeader($header));
        });

        $data = [];
        foreach($headers as $header) {
            $data[strtolower($header)] = str_replace('/\s+/', ' ', $request[$header]);
        }

        return implode('\t', $data);
    }

    protected function makeContentHash(Request $request)
    {
        if ('POST' == $request->getMethod()) {
            $body = $request->getContent();

            return base64_encode(hash('sha256', $body, true));
        }

        return "";
    }

    protected function signRequest(Request $request, $timestamp, $authHeader)
    {
        $message = $this->makeDataToSign($request, $authHeader);
        $secret = $this->makeSigninKey($timestamp);

        return $this->base64HmacSha256($secret, $message);
    }

    protected function makeSigninKey($timestamp)
    {
        $key = $this->base64HmacSha256($this->clientSecret, $timestamp);
        // echo 'signing key:' . $key . "\n";

        return $key;
    }

    protected function base64HmacSha256($data, $key)
    {
        return base64_encode(hash_hmac('sha256', $key, $data, true));
    }
}
