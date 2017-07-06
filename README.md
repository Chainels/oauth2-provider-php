# Chainels Provider for OAuth 2.0 Client
![](http://i.imgur.com/1inwYrO.png)

[![Latest Stable Version](https://poser.pugx.org/chainels/oauth2-chainels/v/stable)](https://packagist.org/packages/chainels/oauth2-chainels) [![Total Downloads](https://poser.pugx.org/chainels/oauth2-chainels/downloads)](https://packagist.org/packages/chainels/oauth2-chainels) [![Latest Unstable Version](https://poser.pugx.org/chainels/oauth2-chainels/v/unstable)](https://packagist.org/packages/chainels/oauth2-chainels) [![License](https://poser.pugx.org/chainels/oauth2-chainels/license)](https://packagist.org/packages/chainels/oauth2-chainels) [![Build Status](https://travis-ci.org/Chainels/oauth2-provider-php.svg?branch=master)](https://travis-ci.org/Chainels/oauth2-provider-php) [![Coverage Status](https://coveralls.io/repos/github/Chainels/oauth2-provider-php/badge.svg?branch=master)](https://coveralls.io/github/Chainels/oauth2-provider-php?branch=master)

This package provides Chainels OAuth 2.0 support for the [PHP League OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Install
Via Composer:
```
$ composer require chainels/oauth2-chainels
```
Version 2.* of this library requires PHP 5.6 or up. If you need support for PHP 5.5, use version 1.*, though we recommend upgrading your PHP version.

## Register Oauth Client
In order to use this library, you must create an OAuth client on Chainels.com, to do this, you must have an account and company on Chainels. For more details, check our [developer page](https://www.chainels.com/developer).

## Usage

Usage is the same as The League's OAuth client, using `\Chainels\OAuth2\Client\Provider\Chainels` as the provider.

### Requesting an access token

Chainels supports the authorization code grant, the client credentials grant, and our own custom group token grant, for requesting an access token.

#### Authorization Code Grant

```php
$provider = new Chainels\OAuth2\Client\Provider\Chainels([
    'clientId'          => '{chainels-client-id}',
    'clientSecret'      => '{chainels-client-secret}',
    'redirectUri'       => 'https://example.com/callback-url',
]);

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl(['grant_type' => 'authorization_code']);
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    /* Optional: Now you have a token you can look up a users profile data. You can also use this token for other HTTP calls to the REST API */
    try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        printf('Hello %s!', $user->getName());

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');
    }

    // Use this to interact with the Chainels API on the users behalf
    echo $token->getToken();
}
```

#### Client Credential Grant

```php
$provider = new Chainels\OAuth2\Client\Provider\Chainels([
    'clientId'          => '{chainels-client-id}',
    'clientSecret'      => '{chainels-client-secret}',
]);

// Try to get an access token (using the client credential code grant)
$token = $provider->getAccessToken('client_credentials', [
    'code' => $_GET['code']
]);

// Use this to interact with the Chainels API
echo $token->getToken();
}
```

#### Group Token Grant

This is the same as the authorization code grant, except make sure to pass a `group` parameter to the `getAuthorizationUrl()` method.

```php
$provider = new Chainels\OAuth2\Client\Provider\Chainels([
    'clientId'          => '{chainels-client-id}',
    'clientSecret'      => '{chainels-client-secret}',
    'redirectUri'       => 'https://example.com/callback-url',
]);

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one, here we specify the group
    $authUrl = $provider->getAuthorizationUrl(['grant_type' => 'group_token', 'group' => '1234']);
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('group_token', [
        'code' => $_GET['code']
    ]);

    /* Optional: Now you have a token you can look up a users profile data. You can also use this token for other HTTP calls to the REST API */
    try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        printf('Hello %s!', $user->getName());

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');
    }

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```

###Refreshing a Token

Once your application is authorized, you can refresh an expired token using a refresh token rather than going through the entire process of obtaining a brand new token. To do so, simply reuse this refresh token from your data store to request a refresh.

```php
$provider = new Chainels\OAuth2\Client\Provider\Chainels([
    'clientId'          => '{chainels-client-id}',
    'clientSecret'      => '{chainels-client-secret}',
    'redirectUri'       => 'https://example.com/callback-url',
]);

$existingAccessToken = getAccessTokenFromYourDataStore();

if ($existingAccessToken->hasExpired()) {
    $newAccessToken = $provider->getAccessToken('refresh_token', [
        'refresh_token' => $existingAccessToken->getRefreshToken()
    ]);

    // Purge old access token and store new access token to your data store.
}
```

### Making authenticated API requests
Once you have an access token, this library provides a simple mechanism to make authenticated calls to our api.

```php
$provider = new Chainels\OAuth2\Client\Provider\Chainels([
    'clientId'          => '{chainels-client-id}',
    'clientSecret'      => '{chainels-client-secret}',
    'redirectUri'       => 'https://example.com/callback-url',
]);

$accessToken = getAccessTokenFromYourDataStore();

// getAuthenticatedRequest returns a Psr\Http\Message\RequestInterface object
$request = $provider->getAuthenticatedRequest('GET', 'https://www.chainels.com/api/v2/companies/{id}', $accessToken);

//this RequestInterface can then be passed to the getParsedResponse() method to execute the request:
$responseJSON = $provider->getParsedResponse($request);
```

`$responseJSON` now contains the response data as a key->value array. If you want the full `Psr\Http\Message\ResponseInterface` instead of a parsed result, simply call `getResponse()` instead of `getParsedResponse()` 