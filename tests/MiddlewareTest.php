<?php

/*
 * This file is part of the Spira framework.
 *
 * @link https://github.com/spira/spira
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
namespace Spira\Core\tests;

use Illuminate\Http\Request;
use Spira\Core\Middleware\TransformInputDataMiddleware;

class MiddlewareTest extends TestCase
{
    /**
     * Test TransformInputData middleware.
     *
     * @return void
     */
    public function testTransformInputData()
    {
        $mw = new TransformInputDataMiddleware();

        // Create a request object to test
        $request = new Request();
        $request->offsetSet('firstName', 'foo');
        $request->offsetSet('lastname', 'bar');

        // And a next closure
        $next = function ($request) { return $request; };

        // Execute
        $request = $mw->handle($request, $next);

        // Assert
        $this->assertArrayHasKey('first_name', $request->all());
        $this->assertArrayNotHasKey('firstName', $request->all());
        $this->assertArrayHasKey('lastname', $request->all());
    }

    public function testTransformInputDataNested()
    {
        $mw = new TransformInputDataMiddleware();

        // Create a request object to test
        $request = new Request();
        $request->offsetSet('firstName', 'foo');
        $request->offsetSet('lastname', 'bar');
        $request->offsetSet('nestedArray', ['fooBar' => 'bar', 'foo' => 'bar', 'oneMore' => ['andThis' => true]]);

        // And a next closure
        $next = function ($request) { return $request; };

        // Execute
        $request = $mw->handle($request, $next);

        // Assert
        $this->assertArrayHasKey('first_name', $request->all());
        $this->assertArrayNotHasKey('firstName', $request->all());
        $this->assertArrayHasKey('lastname', $request->all());
        $this->assertArrayHasKey('nested_array', $request->all());
        $this->assertArrayHasKey('foo_bar', $request->nested_array);
        $this->assertArrayHasKey('and_this', $request->nested_array['one_more']);
    }
}
