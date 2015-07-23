<?php namespace App\Services;

use Illuminate\Validation\Validator;

class SpiraValidator extends Validator
{
    public function validateFloat($attribute, $value, $parameters)
    {
        return is_float($value + 0);
    }

    public function validateUuid($attribute, $value, $parameters)
    {
        return preg_match('/^\{?[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}\}?$/', $value);
    }

    /**
     * Register custom validation rule for countries.
     *
     * @param  string  $attribute
     * @param  string  $value
     * @param  array   $parameters
     * @return void
     */
    protected function validateCountry($attribute, $value, $parameters)
    {
        $countries = \App::make('App\Services\Datasets\Countries')
            ->all()
            ->toArray();

        return in_array($value, array_fetch($countries, 'country_code'));
    }

    /**
     * Register custom validation rule for email confirmation token.
     *
     * @return void
     */
    protected function registerEmailConfirmationToken()
    {
        $this->validator->extend('email_confirmation_token', function ($attribute, $value, $parameters) {

            $token = $this->request->headers->get('email-confirm-token');

            if ($email = $this->cache->pull('email_confirmation_'.$token)) {
                return true;
            }
            return false;
        });
    }
}
