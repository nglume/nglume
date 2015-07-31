<?php

namespace App\Extensions\Socialite\Parsers;

use Laravel\Socialite\Contracts\User;
use Illuminate\Contracts\Support\Arrayable;

abstract class AbstractParser implements Arrayable
{
    /**
     * User object to parse.
     *
     * @var User
     */
    protected $user;

    /**
     * The parsed attributes.
     *
     * @var array
     */
    protected $attributes = ['token', 'email', 'first_name', 'last_name'];

    /**
     * Initialize the parser.
     *
     * @param  User  $user
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;

        $this->parse();
    }

    /**
     * Get the user's token.
     *
     * @return string
     */
    abstract protected function getTokenAttribute();

    /**
     * Get the user's email address.
     *
     * @return string
     */
    abstract protected function getEmailAttribute();

    /**
     * Get the user's first name.
     *
     * @return string
     */
    abstract protected function getFirstNameAttribute();

    /**
     * Get the user's last name.
     *
     * @return string
     */
    abstract protected function getLastNameAttribute();

    /**
     * Parse the social user object.
     *
     * @return void
     */
    protected function parse()
    {
        $this->attributes = array_fill_keys($this->attributes, '');

        foreach (array_keys($this->attributes) as $attribute) {
            $method = camel_case('get_'.$attribute.'_attribute');
            if (method_exists($this, $method)) {
                $this->attributes[$attribute] = $this->$method();
            }
        }
    }

    /**
     * Convert the parsed user to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * Dynamically retrieve parsed user attributes.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }
    }
}
