<?php
namespace SiteMaster\Plugins\Auth_unl;

use SiteMaster\Core\Config;
use SiteMaster\Core\Events\GetAuthenticationPlugins;
use SiteMaster\Core\Events\Navigation\MainCompile;
use SiteMaster\Core\Events\RoutesCompile;
use SiteMaster\Core\Events\User\Search;
use SiteMaster\Core\Plugin\AuthenticationInterface;
use SiteMaster\Core\Plugin\PluginInterface;

class Plugin extends PluginInterface implements AuthenticationInterface
{
    protected $options = array(
        'CERT_PATH' => '/etc/pki/tls/cert.pem'
    );
    
    /**
     * @return bool|mixed
     */
    public function onInstall()
    {
        return true;
    }

    /**
     * @return bool|mixed
     */
    public function onUninstall()
    {
        return true;
    }

    /**
     * @return mixed|string
     */
    public function getName()
    {
        return 'UNL Auth Plugin';
    }

    /**
     * @return mixed|string
     */
    public function getDescription()
    {
        return 'UNL auth plugin using CAS';
    }

    /**
     * Called when the plugin is updated (a newer version exists).
     *
     * @param $previousVersion int The previous installed version
     * @return mixed
     */
    public function onUpdate($previousVersion)
    {
        return true;
    }

    /**
     * Returns the version of this plugin
     * Follow a mmddyyyyxx syntax.
     *
     * for example 1118201301
     * would be 11/18/2013 - increment 1
     *
     * @return mixed
     */
    public function getVersion()
    {
        return true;
    }

    /**
     * Get an array of event listeners
     *
     * @return array
     */
    function getEventListeners()
    {
        $listeners = array();

        $listener = new Listener($this);

        $listeners[] = array(
            'event'    => RoutesCompile::EVENT_NAME,
            'listener' => array($listener, 'onRoutesCompile')
        );

        $listeners[] = array(
            'event'    => GetAuthenticationPlugins::EVENT_NAME,
            'listener' => array($listener, 'onGetAuthenticationPlugins')
        );

        $listeners[] = array(
            'event'    => Search::EVENT_NAME,
            'listener' => array($listener, 'onUserSearch')
        );

        return $listeners;
    }

    /**
     * Get the URL to log in using this authentication method
     *
     * @return string
     */
    public function getLoginURL()
    {
        return Config::get('URL') . 'auth/unl/';
    }

    /**
     * Get the URL to log out of this authentication method
     *
     * @return mixed
     */
    public function getLogoutURL()
    {
        return Config::get('URL') . 'auth/unl/logout/';
    }

    /**
     * Get the name of the provider that this authentication method provides
     * This is what is stored in the users.provider table
     *
     * @return string
     */
    public function getProviderMachineName()
    {
        return 'UNL';
    }

    /**
     * Get the name of the authentication provider that this plugin provides, as
     * readable by humans
     *
     * @return string
     */
    public function getProviderHumanName()
    {
        return 'unl.edu';
    }
    
    public function initialize()
    {
        //phpCAS always starts a session, so start one early (otherwise our session handler complains).
        \SiteMaster\Core\User\Session::start();

        //Attempt to auto-login
        $auth = new Auth;
        $auth->singleLogOut();
        $auth->autoLogin();
    }
}