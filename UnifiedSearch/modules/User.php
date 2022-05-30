<?php

namespace UnifiedSearch\modules;

use Exception;
use GuayaquilLib\ServiceOem;
use Legeartis\UnifiedSearch\Config;
use Legeartis\UnifiedSearch\UnifiedSearchService;

class User
{
    /** @var User */
    static $user = null;

    /** @var string */
    protected $login = '';

    protected $password = '';

    protected $services = [];

    protected $token = '';

    public function __construct($storedData = '')
    {
        if ($storedData) {
            $data = json_decode($storedData, true);
            $this->login = $data['login'];
            $this->password = $data['password'];
            $this->services = $data['services'];
            $this->token = $data['token'];
        }
    }

    /**
     * @return User
     */
    public static function getUser(): User
    {
        return self::$user;
    }

    /**
     * @param User $user
     */
    public static function setUser(User $user)
    {
        self::$user = $user;
    }

    public static function loginToServices(string $user, string $pass): ?User
    {
        $services = [];

        try {
            $oem = new ServiceOem($user, $pass);
            $oem->listCatalogs();
            $services['oem'] = 'oem';
        } catch (Exception $e) {
        }

        $configData = [
            'login' => $user,
            'password' => $pass,
        ];
        $us = new UnifiedSearchService(new Config($configData));
        try {
            $info = $us->getUserInfo();
            $services['us'] = 'us';
            if ($info->isOffersUploadAllowed()) {
                $services['us_upload'] = 'us_upload';
            }
        } catch (Exception $e) {
        }

        if (array_key_exists('us', $services)) {
            $user = new User(json_encode([
                'login' => $user,
                'password' => $pass,
                'services' => $services,
                'token' => bin2hex(random_bytes(64)),
            ]));
            User::setUser($user);
            $_SESSION['userData'] = $user->toString();
            return $user;
        } else {
            User::logout();
        }
        return null;
    }

    public function toString()
    {
        return json_encode([
            'login' => $this->login,
            'password' => $this->password,
            'services' => $this->services,
            'token' => $this->token,
        ]);
    }

    public static function logout()
    {
        unset($_SESSION['userData']);
        User::setUser(new User());
    }

    /**
     * @return string
     */
    public function getLogin(): string
    {
        return $this->login;
    }

    /**
     * @return array
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return array_key_exists('us', $this->services);
    }

    /**
     * @return mixed|string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return bool
     */
    public function isServiceAvailable(string $service): bool
    {
        return array_key_exists($service, $this->services);
    }
}

User::setUser(new User(@$_SESSION['userData']));
