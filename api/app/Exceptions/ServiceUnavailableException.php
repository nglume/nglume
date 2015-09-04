<?php

/*
 * This file is part of the Spira framework.
 *
 * @link https://github.com/spira/spira
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ServiceUnavailableException extends HttpException
{
    /**
     * Create a new Service Unavailable exception instance.
     *
     * @param string     $message
     * @param int        $code
     * @param \Exception $previous
     *
     * @return void
     */
    public function __construct($message = 'Service Unavailable.', $code = 0, Exception $previous = null)
    {
        parent::__construct(Response::HTTP_SERVICE_UNAVAILABLE, $message, $previous);
    }
}
