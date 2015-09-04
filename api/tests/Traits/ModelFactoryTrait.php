<?php

/*
 * This file is part of the Spira framework.
 *
 * @link https://github.com/spira/spira
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

use App\Services\ModelFactory;

trait ModelFactoryTrait
{
    /**
     * Making it static not to reinit for each TestCase.
     * @var ModelFactory
     */
    protected static $modelFactory;

    public function bootModelFactoryTrait()
    {
        if (is_null(static::$modelFactory)) {
            static::$modelFactory = \App::make(ModelFactory::class);
        }
    }

    /**
     * @return ModelFactory
     */
    public function getFactory()
    {
        return static::$modelFactory;
    }
}
