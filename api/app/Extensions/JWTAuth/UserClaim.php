<?php

/*
 * This file is part of the Spira framework.
 *
 * @link https://github.com/spira/spira
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace App\Extensions\JWTAuth;

use App;
use App\Models\User;
use Tymon\JWTAuth\Claims\Claim;
use App\Services\TransformerService;
use Tymon\JWTAuth\Exceptions\InvalidClaimException;
use App\Http\Transformers\EloquentModelTransformer;

class UserClaim extends Claim
{
    /**
     * The claim name.
     *
     * @var string
     */
    protected $name = '_user';

    /**
     * Set the claim value, and call a validate method if available.
     *
     * @param  User  $value
     * @throws InvalidClaimException
     * @return $this
     */
    public function setValue($value)
    {
        // Transform the user before encoding
        $transformerService = App::make(TransformerService::class);
        $transformer = new EloquentModelTransformer($transformerService);
        $value = $transformer->transform($value);

        return parent::setValue($value);
    }

    /**
     * Validate the user claim.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function validate($value)
    {
        return is_array($value);
    }
}
