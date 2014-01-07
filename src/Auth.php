<?php
namespace SiteMaster\Plugins\Auth_Unl;

use \SiteMaster\Config;
use SiteMaster\Exception;
use SiteMaster\Plugin\PluginManager;
use SiteMaster\Session;
use SiteMaster\User\User;
use SiteMaster\Util;
use \SiteMaster\ViewableInterface;

class Auth implements ViewableInterface
{
    /**
     * @var array
     */
    protected $options = array();
    
    public static $directory_url = 'http://directory.unl.edu/';

    /**
     * @param array $options
     */
    function __construct($options = array())
    {
        $this->authenticate();
        
        if (strpos($options['current_url'], 'logout') !== false) {
            //handle callback
            $this->logout();
        }
    }

    /**
     * Authenticate the user
     */
    public function authenticate()
    {
        $client = $this->getClient();
        $plugin = PluginManager::getManager()->getPluginInfo('auth_unl');
        
        $client->forceAuthentication();

        if (!$client->isAuthenticated()) {
            throw new Exception('Unable to authenticate', 403);
        }

        $uid = trim(strtolower($client->getUsername()));
        if (!$user = User::getByUIDAndProvider($client->getUsername(), $plugin->getProviderMachineName())) {
            $info = $this->getUserInfo($uid);
            
            $user = User::createUser($client->getUsername(), $plugin->getProviderMachineName(), $info);
        }

        \SiteMaster\User\Session::logIn($user);
    }
    
    public function logout()
    {
        $client = $this->getClient();
        $client->logout(\SiteMaster\Config::get('URL'));
    }

    /**
     * Get the opauth object for this authentication plugin
     *
     * @return \Opauth\Opauth
     */
    public function getClient()
    {
        $options = array(
            'hostname' => 'login.unl.edu',
            'port'     => 443,
            'uri'      => 'cas'
        );
        $protocol = new \SimpleCAS_Protocol_Version2($options);

        return \SimpleCAS::client($protocol);
    }


    /**
     * Get a user's information from directory.unl.edu
     * 
     * @param string $uid
     * @return array
     */
    public function getUserInfo($uid)
    {
        $info = array();
        
        if (!$json = file_get_contents(self::$directory_url . '?uid=' . $uid . '&format=json')) {
            return $info;
        }
        
        if (!$json = json_decode($json, true)) {
            return $info;
        }
        
        $map = array(
            'givenName' => 'first_name',
            'sn' => 'last_name',
            'mail' => 'email'
        );
        
        foreach ($map as $from => $to) {
            if (isset($json[$from][0])) {
                $info[$to] = $json[$from][0];
            }
        }
        
        return $info;
    }

    /**
     * The URL for this page
     *
     * @return string
     */
    public function getURL()
    {
        return Config::get('URL') . 'auth/unl/';
    }

    /**
     * The page title for this page
     *
     * @return string
     */
    public function getPageTitle()
    {
        return "UNL Auth";
    }
}