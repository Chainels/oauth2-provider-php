<?php

namespace Chainels\OAuth2\Client\Test\Provider;

use Chainels\OAuth2\Client\Provider\Chainels;
use Chainels\OAuth2\Client\Provider\ChainelsResourceOwner;
use GuzzleHttp\ClientInterface;
use League\OAuth2\Client\Provider\AbstractProvider;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ResponseInterface;

class ChainelsTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var AbstractProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new Chainels([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();

        $uri = parse_url($url);
        parse_str($uri['query'], $query);
        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testScopes()
    {
        $options = ['scope' => [uniqid(), uniqid()]];
        $url = $this->provider->getAuthorizationUrl($options);
        $this->assertContains(urlencode(implode(' ', $options['scope'])), $url);
    }

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        $this->assertEquals('/oauth/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];
        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);
        $this->assertEquals('/oauth/access_token', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $response = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $response->method('getBody')->willReturn('{"access_token":"mock_access_token","refresh_token": "mock_refresh_token", "token_type": "Bearer", "expires_in": 5}');
        $response->method('getHeader')->willReturn(['content-type' => 'application/json']);

        $client = $this->getMockBuilder(ClientInterface::class)->getMock();
        $client->expects($this->once())->method('send')->willReturn($response);

        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertEquals('mock_refresh_token', $token->getRefreshToken());
        $this->assertNotNull($token->getExpires());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testUserData()
    {
        $userJSON = file_get_contents(__DIR__ . '/mockuser.json');
        $userArray = json_decode($userJSON, true);

        $postResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $postResponse->method('getBody')->willReturn('{"access_token":"mock_access_token","refresh_token": "mock_refresh_token", "token_type": "Bearer", "expires_in": 5}');
        $postResponse->method('getHeader')->willReturn(['content-type' => 'application/json']);

        $userResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $userResponse->method('getBody')->willReturn($userJSON);
        $userResponse->method('getHeader')->willReturn(['content-type' => 'application/json']);

        $client = $this->getMockBuilder(ClientInterface::class)->getMock();
        $client->expects($this->exactly(2))->method('send')->will($this->onConsecutiveCalls($postResponse,
            $userResponse));

        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);
        /* @var $user ChainelsResourceOwner */

        $this->assertEquals($userArray['id'], $user->getId());
        $this->assertEquals($userArray['id'], $user->toArray()['id']);
        $this->assertEquals($userArray['name'], $user->getName());
        $this->assertEquals($userArray['name'], $user->toArray()['name']);
        $this->assertEquals($userArray['image']['url'], $user->getImage());
        $this->assertEquals($userArray['image']['url'], $user->toArray()['image']['url']);
        $this->assertEquals($userArray['email'], $user->getEmail());
        $this->assertEquals($userArray['email'], $user->toArray()['email']);
        $this->assertEquals($userArray['language_key'], $user->getLanguage());
        $this->assertEquals($userArray['language_key'], $user->toArray()['language_key']);
        $this->assertEquals($userArray['active_company'], $user->getActiveCompany());
        $this->assertEquals($userArray['active_company'], $user->toArray()['active_company']);
    }

    public function testUserDataNoImage()
    {
        $userJSON = file_get_contents(__DIR__ . '/mockuser.json');
        $userArray = json_decode($userJSON, true);
        unset($userArray['image']);
        $userJSON = json_encode($userArray);

        $postResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $postResponse->method('getBody')->willReturn('{"access_token":"mock_access_token","refresh_token": "mock_refresh_token", "token_type": "Bearer", "expires_in": 5}');
        $postResponse->method('getHeader')->willReturn(['content-type' => 'application/json']);

        $userResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $userResponse->method('getBody')->willReturn($userJSON);
        $userResponse->method('getHeader')->willReturn(['content-type' => 'application/json']);

        $client = $this->getMockBuilder(ClientInterface::class)->getMock();
        $client->expects($this->exactly(2))->method('send')->will($this->onConsecutiveCalls($postResponse,
            $userResponse));

        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);

        $this->assertNull($user->getImage());
    }

    /**
     * @expectedException \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     * */
    public function testExceptionThrownWhenAuthErrorObjectReceived()
    {
        $response = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $response->method('getBody')->willReturn('{"error": "invalid_request","message": "The request is missing a required parameter, includes an invalid parameter value, includes a parameter more than once, or is otherwise malformed."}');
        $response->method('getHeader')->willReturn(['content-type' => 'application/json']);
        $response->method('getStatusCode')->willReturn(400);

        $client = $this->getMockBuilder(ClientInterface::class)->getMock();
        $client->expects($this->once())->method('send')->willReturn($response);

        $this->provider->setHttpClient($client);

        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    public function testDemoUrl()
    {
        $this->provider = new Chainels([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
            'isDemo' => true
        ]);

        $this->assertEquals('https://demo.chainels.com/oauth/authorize', $this->provider->getBaseAuthorizationUrl());
    }

    public function testGetAccessTokenViaGroupGrant()
    {
        $response = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $response->method('getBody')->willReturn('{"access_token":"mock_access_token","refresh_token": "mock_refresh_token", "token_type": "Bearer", "expires_in": 5}');
        $response->method('getHeader')->willReturn(['content-type' => 'application/json']);

        $client = $this->getMockBuilder(ClientInterface::class)->getMock();
        $client->expects($this->once())->method('send')->willReturn($response);

        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('group_token', ['code' => 'mock_authorization_code']);
        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertEquals('mock_refresh_token', $token->getRefreshToken());
        $this->assertNotNull($token->getExpires());
        $this->assertNull($token->getResourceOwnerId());
    }

}
