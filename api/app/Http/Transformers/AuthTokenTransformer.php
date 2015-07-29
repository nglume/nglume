<?php

namespace App\Http\Transformers;

use App;
use Tymon\JWTAuth\Token;
use App\Exceptions\NotImplementedException;
use Spira\Responder\Contract\TransformerInterface;

class AuthTokenTransformer implements TransformerInterface
{
    /**
     * Transform the token string into an response array.
     *
     * @param  string  $token
     * @return array
     */
    public function transformItem($token)
    {
        $token = new Token($token);
        $jwtAuth = App::make('Tymon\JWTAuth\JWTAuth');

        return [
            'token' => (string) $token,
            'decodedTokenBody' => $jwtAuth->decode($token)->toArray()
        ];
    }

    /**
     * Collections are not used for token transformations.
     *
     * @param  mixed  $collection
     * @throws NotImplementedException
     * @return void
     */
    public function transformCollection($collection)
    {
        throw new NotImplementedException('Collections are not used for tokens.');
    }
}
