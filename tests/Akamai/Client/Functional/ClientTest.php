<?php
namespace Akamai\Client\Tests\Functional;

use Akamai\Client\Client;
use Buzz\Client\Curl;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    protected static $client;

    public static function setUpBeforeClass()
    {
        if (false == isset($GLOBALS['AKAMAI_CLIENT_TOKEN'])) {
            throw new \PHPUnit_Framework_SkippedTestError('These tests require AKAMAI_CLIENT_TOKEN to be set in phpunit.xml');
        }

        if (false == isset($GLOBALS['AKAMAI_ACCESS_TOKEN'])) {
            throw new \PHPUnit_Framework_SkippedTestError('These tests require AKAMAI_ACCESS_TOKEN to be set in phpunit.xml');
        }

        if (false == isset($GLOBALS['AKAMAI_CLIENT_SECRET'])) {
            throw new \PHPUnit_Framework_SkippedTestError('These tests require AKAMAI_CLIENT_SECRET to be set in phpunit.xml');
        }

        if (false == isset($GLOBALS['AKAMAI_BASEURL'])) {
            throw new \PHPUnit_Framework_SkippedTestError('These tests require AKAMAI_BASEURL to be set in phpunit.xml');
        }

        $curl = new Curl;
        $curl->setTimeout(10);

        self::$client = new Client($curl, $GLOBALS['AKAMAI_CLIENT_TOKEN'],
            $GLOBALS['AKAMAI_CLIENT_SECRET'], $GLOBALS['AKAMAI_ACCESS_TOKEN'], 
            $GLOBALS['AKAMAI_BASEURL']);
    }

    public function testCheckQueueLength()
    {
        $response = self::$client->checkQueueLength();
        var_dump($response);

        $this->assertEquals($response->httpStatus, 200);
        $this->assertEquals($response->detail, 'The queue may take a minute to reflect new or removed requests.');
    }

    public function testGetPurgeStatus()
    {
        $id = 1;
        $response = self::$client->getPurgeStatus($id);

        $this->assertEquals($response->httpStatus, 200);
        $this->assertEquals($response->purgeId, $id);
        $this->assertEquals($response->detail, 'Please note that it can take up to a minute for the status of a recently submitted request to become visible.');
    }

    public function testPostPurgeRequest()
    {
        $urls = ['objects' => ['/qa/path/1', '/qa/path/2']];
        $response = self::$client->purgeRequest($urls);
        var_dump($response);

        $this->assertEquals($response->httpStatus, 200);
    }
}
