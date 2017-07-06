<?php

namespace Chainels\OAuth2\Client\Provider;

use Chainels\OAuth2\Client\Grant\GroupTokenGrant;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Chainels extends AbstractProvider
{

    use BearerAuthorizationTrait;

    protected $isDemo;

    public function __construct($options = [])
    {
        parent::__construct($options);
        if (isset($options['isDemo'])) {
            $this->isDemo = $options['isDemo'];
        }

        $this->getGrantFactory()->setGrant('group_token', new GroupTokenGrant());
    }

    public function getBaseAuthorizationUrl()
    {
        return $this->isDemo ? 'https://demo.chainels.com/oauth/authorize' : 'https://www.chainels.com/oauth/authorize';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->isDemo ? 'https://demo.chainels.com/oauth/access_token' : 'https://www.chainels.com/oauth/access_token';
    }

    protected function getDefaultScopes()
    {
        return ['basic'];
    }

    protected function getScopeSeparator()
    {
        return ' ';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->isDemo ? 'https://demo.chainels.com/api/v2/accounts/me' : 'https://www.chainels.com/api/v2/accounts/me';
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400) {
            throw new IdentityProviderException(
                isset($data['message']) ? $data['message'] : $response->getReasonPhrase(), $statusCode, $response
            );
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new ChainelsResourceOwner($response);
    }

}
