<?php
/**
 * Created by PhpStorm.
 * User: ivanmatveev
 * Date: 14.09.15
 * Time: 14:35
 */

namespace App\Providers;


use Illuminate\Contracts\Auth\Authenticatable;

use Illuminate\Http\Request;
use Spira\Auth\Providers\JWTAuthDriverServiceProvider;
use Spira\Auth\User\SocialiteAuthenticatable;
use Spira\Auth\User\UserProvider;

class AuthDriverServiceProvider extends JWTAuthDriverServiceProvider
{
    protected function getPayloadGenerators()
    {
        /** @var Request $request */
        $request = $this->app[Request::class];
        return array_merge(parent::getPayloadGenerators(),[
            '_user' => function(Authenticatable $user){ return $user;},
            'method' => function(SocialiteAuthenticatable $user){ return $user->getCurrentAuthMethod()?:'password';},
            'iss'=> function() use ($request) { return $request->getHttpHost();},
            'aud'=> function() use ($request) { return str_replace('api.', '', $request->getHttpHost());},
            'sub' => function(Authenticatable $user){return $user->getAuthIdentifier();},
        ]);
    }

    protected function getTokenUserProvider()
    {
        return function($payload, UserProvider $provider){
            if (isset($payload['_user']) && $payload['_user']){
                $userData = $payload['_user'];
                $user = $provider->createModel();
                foreach($userData as $key => $value){
                    if (is_string($value)){
                        $user->{$key} = $value;
                    }
                }

                return $user;
            }

            if (isset($payload['sub']) && $payload['sub']){
                return $provider->retrieveById($payload['sub']);
            }

            return null;
        };
    }

    protected function getSecretPublic()
    {
        return 'file://'.storage_path('app/keys/public.pem');
    }

    protected function getSecretPrivate()
    {
        return 'file://'.storage_path('app/keys/private.pem');
    }
}