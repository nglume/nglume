<?php namespace App\Services;

class Validator
{
    /**
     * Validator.
     *
     * @var \Illuminate\Validation\Factory
     */
    protected $validator;

    /**
     * Data to validate.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Validation errors.
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Assign dependencies.
     *
     * @return  void
     */
    public function __construct()
    {
        $this->validator = \App::make('validator');

        $this->registerValidateFloat();
    }

    /**
     * Validation rules.
     *
     * @return void
     */
    public function rules()
    {
        return [];
    }

    /**
     * Set custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'float' => 'The :attribute must be a float.'
        ];
    }

    /**
     * Add data to validate against.
     *
     * @param  array  $data
     * @return $this
     */
    public function with(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Test if validation passes.
     *
     * @return bool
     */
    public function passes()
    {
        $validator = $this->validator->make(
            $this->data,
            $this->rules(),
            $this->messages()
        );

        if ($validator->fails()) {
            $this->errors = $validator->messages();

            return false;
        }

        return true;
    }

    /**
     * Retrieve validation errors.
     *
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Register custom validation rule for float.
     *
     * @return void
     */
    protected function registerValidateFloat()
    {
        $this->validator->extend('float', function($attribute, $value, $parameters)
        {
            return is_float($value + 0);
        });
    }
}
