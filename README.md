#Chainels Provider for OAuth 2.0 Client

This package provides Chainels OAuth 2.0 support for the [PHP League OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

##Usage

Usage is the same as The League's OAuth client, using `\Chainels\OAuth2\Client\Provider\Chainels` as the provider.

##Register Oauth Client
In order to use this library, you must create an OAuth client on Chainels.com, to do this, you must have an account and company on Chainels. For more details, check our [developer page](https://www.chainels.com/developer).

##Authorization Code Grant

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

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```

##Client Credential Grant

```php
$provider = new Chainels\OAuth2\Client\Provider\Chainels([
    'clientId'          => '{chainels-client-id}',
    'clientSecret'      => '{chainels-client-secret}',
]);

// Try to get an access token (using the client credential code grant)
$token = $provider->getAccessToken('client_credentials', [
    'code' => $_GET['code']
]);

// Use this to interact with an API on the users behalf
echo $token->getToken();
}
```

##Group Token Grant

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