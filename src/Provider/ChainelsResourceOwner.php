<?php

namespace Chainels\OAuth2\Client\Provider;

class ChainelsResourceOwner implements \League\OAuth2\Client\Provider\ResourceOwnerInterface {

    /**
     * @var array
     */
    protected $response;

    /**
     * @param array $response
     */
    public function __construct(array $response) {
        $this->response = $response;
    }

    public function getId() {
        return $this->response['id'];
    }

    /**
     * Get the user name.
     *
     * @return string
     */
    public function getName() {
        return $this->response['name'];
    }

    /**
     * Get the language key
     *
     * @return string
     */
    public function getLanguage() {
        return $this->response['language_key'];
    }

    /**
     * Get the email of the user
     *
     * @return string
     */
    public function getEmail() {
        return $this->response['email'];
    }

    /**
     * Get the id of the current active company of the user
     * @return string
     */
    public function getActiveCompany() {
        return $this->response['active_company'];
    }

    /**
     * Get the url to the image (photo) of this user
     *
     * @return string
     */
    public function getImage() {
        if (isset($this->response['image'])) {
            return $this->response['image']['url'];
        }
    }

    /**
     * Get user data as an array.
     *
     * @return array
     */
    public function toArray() {
        return $this->response;
    }

}
