<?php
/**
 * @project     SocialLogin
 * @package     LitExtension_SocialLogin
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_SocialLogin_Model_Amazon_Client
{
    const REDIRECT_URI_ROUTE = 'le_sociallogin/amazon/connect';
    const REDIRECT_URI_REQUEST = 'le_sociallogin/amazon/request';

    const XML_PATH_ENABLED = 'le_sociallogin/amazon/enabled';
    const XML_PATH_CLIENT_ID = 'le_sociallogin/amazon/api_key';
    const XML_PATH_CLIENT_SECRET = 'le_sociallogin/amazon/secret';

    const OAUTH2_SERVICE_URI = 'https://api.amazon.com/user/profile';
    const OAUTH2_AUTH_URI = 'https://amazon.com/ap/oa';
    const OAUTH2_TOKEN_URI = ' https://api.amazon.com/auth/O2/tokeninfo';

    protected $clientId = null;
    protected $clientSecret = null;
    protected $redirectUri = null;
    protected $state = '';
    protected $scope = array('profile');

    protected $token = null;
    protected $protocol = "http";

    public function __construct($params = array())
    {
        if(($this->isEnabled = $this->_isEnabled())) {
            $this->clientId = $this->_getClientId();
            $this->clientSecret = $this->_getClientSecret();

            $isSecure = Mage::app()->getStore()->isCurrentlySecure();
            if($isSecure){
                $this->protocol = "https";
            }

            $this->redirectUri = Mage::getModel('core/url')->sessionUrlVar(
                Mage::getUrl(self::REDIRECT_URI_ROUTE, array('_secure'=>true))
            );

            if(!empty($params['scope'])) {
                $this->scope = $params['scope'];
            }

            if(!empty($params['state'])) {
                $this->state = $params['state'];
            }
        }
    }

    public function isEnabled()
    {
        return (bool) $this->isEnabled;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function setAccessToken($token)
    {
        $this->token = json_decode($token);
    }

    public function getAccessToken()
    {
        if(empty($this->token)) {
            $this->fetchAccessToken();
        }

        return json_encode($this->token);
    }

    public function createRequestUrl(){
        $url =
            self::OAUTH2_AUTH_URI.'?'.
            http_build_query(
                array(
                    'client_id' => $this->clientId,
                    'redirect_uri' => $this->redirectUri,
                    'state' => $this->state,
                    'response_type' => 'token',
                    'scope' => implode(',', $this->scope),
                    'display' => 'popup'
                )
            );
        return $url;
    }

    public function createAuthUrl()
    {
        return Mage::getUrl('le_sociallogin/amazon/request', array("mainw_protocol" => $this->protocol));
    }

    public function api($endpoint, $method = 'GET', $params = array())
    {
        $c = curl_init(self::OAUTH2_SERVICE_URI.'?access_token=' . urlencode($_REQUEST['access_token']));
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($c);
        curl_close($c);
        $userdata = json_decode($response);

        return $userdata;
    }

    protected function fetchAccessToken()
    {
        if(empty($_REQUEST['access_token'])) {
            throw new Exception(
                Mage::helper('le_sociallogin')
                    ->__('Unable to retrieve access code.')
            );
        }

        $response = $this->_httpRequest(
            self::OAUTH2_TOKEN_URI,
            'POST',
            array(
                'code' => $_REQUEST['access_token'],
                'redirect_uri' => $this->redirectUri,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'authorization_code'
            )
        );

        $this->token = $response;
    }

    protected function _httpRequest($url, $method = 'GET', $params = array())
    {
        $client = new Zend_Http_Client($url, array('timeout' => 60));
        $streamOpts = array(
            'ssl' => array(
                'verify_peer' => false,
                'allow_self_signed' => true
            )
        );

        switch ($method) {
            case 'GET':
                $client->setParameterGet($params);
                break;
            case 'POST':
                $client->setParameterPost($params);
                break;
            case 'DELETE':
                $client->setParameterGet($params);
                break;
            default:
                throw new Exception(
                    Mage::helper('le_sociallogin')
                        ->__('Required HTTP method is not supported.')
                );
        }

        $response = $client->request($method);

        Mage::log($response->getStatus().' - '. $response->getBody());

        $decoded_response = json_decode($response->getBody());

        /*
         * Per http://tools.ietf.org/html/draft-ietf-oauth-v2-27#section-5.1
         * amazon should return data using the "application/json" media type.
         * amazon violates OAuth2 specification and returns string. If this
         * ever gets fixed, following condition will not be used anymore.
         */
        if(empty($decoded_response)) {
            $parsed_response = array();
            parse_str($response->getBody(), $parsed_response);

            $decoded_response = json_decode(json_encode($parsed_response));
        }

        if($response->isError()) {
            $status = $response->getStatus();
            if(($status == 400 || $status == 401)) {
                if(isset($decoded_response->error->message)) {
                    $message = $decoded_response->error->message;
                } else {
                    $message = Mage::helper('le_sociallogin')
                        ->__('Unspecified OAuth error occurred.');
                }

                throw new LitExtension_SocialLogin_AmazonOAuthException($message);
            } else {
                $message = sprintf(
                    Mage::helper('le_sociallogin')
                        ->__('HTTP error %d occurred while issuing request.'),
                    $status
                );

                throw new Exception($message);
            }
        }

        return $decoded_response;
    }

    protected function _isEnabled()
    {
        return $this->_getStoreConfig(self::XML_PATH_ENABLED);
    }

    protected function _getClientId()
    {
        return $this->_getStoreConfig(self::XML_PATH_CLIENT_ID);
    }

    protected function _getClientSecret()
    {
        return $this->_getStoreConfig(self::XML_PATH_CLIENT_SECRET);
    }

    protected function _getStoreConfig($xmlPath)
    {
        return Mage::getStoreConfig($xmlPath, Mage::app()->getStore()->getId());
    }

}

class LitExtension_SocialLogin_AmazonOAuthException extends Exception
{}