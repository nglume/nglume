<?php

class TestCase extends Laravel\Lumen\Testing\TestCase
{
    use AssertionsTrait;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        $this->bootTraits();

        parent::setUp();
    }

    /**
     * Allow traits to have custom initialization built in.
     *
     * @return void
     */
    protected function bootTraits()
    {
        foreach (class_uses($this) as $trait) {
            if (method_exists($this, 'boot'.$trait)) {
                $this->{'boot'.$trait}();
            }
        }
    }

    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }
}
