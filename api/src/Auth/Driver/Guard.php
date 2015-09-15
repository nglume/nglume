<?php
/**
 * Created by PhpStorm.
 * User: ivanmatveev
 * Date: 11.09.15
 * Time: 13:55
 */

namespace Spira\Auth\Driver;


use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Spira\Auth\Payload\PayloadFactory;
use Spira\Auth\Payload\PayloadValidationFactory;
use Spira\Auth\Token\JWTInterface;
use Spira\Auth\Token\RequestParser;


class Guard implements \Illuminate\Contracts\Auth\Guard
{
    /**
     * @var UserProvider
     */
    protected $provider;

    /**
     * The currently authenticated user.
     *
     * @var Authenticatable
     */
    protected $user;

    /**
     * @var bool
     */
    protected $viaToken = false;

    /**
     * @var PayloadFactory
     */
    protected $payloadFactory;

    /**
     * @var JWTInterface
     */
    protected $tokenizer;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var PayloadValidationFactory
     */
    protected $validationFactory;
    /**
     * @var RequestParser
     */
    protected $requestParser;

    /**
     * @param JWTInterface $tokenizer
     * @param PayloadFactory $payloadFactory
     * @param PayloadValidationFactory $validationFactory
     * @param UserProvider $provider
     * @param RequestParser $requestParser
     */
    public function __construct(
        JWTInterface $tokenizer,
        PayloadFactory $payloadFactory,
        PayloadValidationFactory $validationFactory,
        UserProvider $provider,
        RequestParser $requestParser
    )
    {

        $this->payloadFactory = $payloadFactory;
        $this->provider = $provider;
        $this->tokenizer = $tokenizer;
        $this->validationFactory = $validationFactory;
        $this->requestParser = $requestParser;
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        return (bool)$this->user;
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        return !$this->check();
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if ($this->user === false){
            return null;
        }

        if ($this->user){
            return $this->user;
        }

        $token = $this->getRequestParser()->getToken($this->getRequest());
        $payload = $this->getTokenizer()->decode($token);
        $this->getValidationFactory()->validatePayload($payload);
        $user = $this->getProvider()->retrieveByToken(null, $payload);

        if ($user){
            $this->user = $user;
            $this->viaToken = true;
        }

        return $user;
    }

    /**
     * Log a user into the application without token.
     *
     * @param  array $credentials
     * @return bool
     */
    public function once(array $credentials = [])
    {
        return $this->attempt($credentials, false, true);
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param  array $credentials
     * @param  bool $remember
     * @param  bool $login
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = true, $login = true)
    {
        $user = $this->getProvider()->retrieveByCredentials($credentials);
        if ($user  && $this->getProvider()->validateCredentials($user, $credentials)){
            if ($login) {
                $this->login($user, $remember);
            }

            return true;
        }

        return false;
    }

    /**
     * Attempt to authenticate using HTTP Basic Auth.
     *
     * @param  string $field
     * @return null|\Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function basic($field = 'email')
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Perform a stateless HTTP Basic login attempt.
     *
     * @param  string $field
     * @return null|\Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function onceBasic($field = 'email')
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array $credentials
     * @return bool
     * @throws \Exception
     */
    public function validate(array $credentials = [])
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Log a user into the application.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  bool $remember set or update token
     * @return void
     */
    public function login(Authenticatable $user, $remember = false)
    {
        $this->user = $user;
        if ($remember){
            $user->setRememberToken($this->generateToken($user));
        }
    }

    /**
     * Log the given user ID into the application.
     *
     * @param  mixed $id auth token
     * @param  bool $remember
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public function loginUsingId($id, $remember = false)
    {
        $user = $this->provider->retrieveById($id);
        if ($user){
            $this->login($user,$remember);
        }

        return $user;
    }

    /**
     * Determine if the user was authenticated via token.
     *
     * @return bool
     */
    public function viaRemember()
    {
        return $this->viaToken;
    }

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout()
    {
        $this->user = false;
    }

    /**
     * @param Authenticatable $user
     * @return string
     */
    protected function generateToken(Authenticatable $user)
    {
        return $this->getTokenizer()->encode($this->getPayloadFactory()->createFromUser($user));
    }

    /**
     * Get the user provider used by the guard.
     *
     * @return UserProvider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Set the user provider used by the guard.
     *
     * @param  UserProvider  $provider
     * @return void
     */
    public function setProvider(UserProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return JWTInterface
     */
    public function getTokenizer()
    {
        return $this->tokenizer;
    }

    /**
     * @return PayloadFactory
     */
    public function getPayloadFactory()
    {
        return $this->payloadFactory;
    }

    /**
     * @return RequestParser
     */
    public function getRequestParser()
    {
        return $this->requestParser;
    }

    /**
     * Get the current request instance.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the current request instance.
     *
     * @param  Request  $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @return PayloadValidationFactory
     */
    public function getValidationFactory()
    {
        return $this->validationFactory;
    }

}