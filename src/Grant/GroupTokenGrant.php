<?php

namespace Chainels\OAuth2\Client\Grant;

use League\OAuth2\Client\Grant\AbstractGrant;

class GroupTokenGrant extends AbstractGrant {

    protected function getName() {
        return 'group_token';
    }

    protected function getRequiredRequestParameters() {
        return [
            'code',
        ];
    }

}
