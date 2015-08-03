<?php namespace App\Http\Controllers;

use App\Http\Transformers\EloquentModelTransformer;
use App\Services\Datasets\Countries;
use Symfony\Component\HttpFoundation\Response;

class CountriesController extends ApiController
{
    /**
     * Assign dependencies.
     *
     * @param  Countries $countries
     * @param  EloquentModelTransformer $transformer
     */
    public function __construct(Countries $countries, EloquentModelTransformer $transformer)
    {
        $this->countries = $countries;
        $this->transformer = $transformer;
    }

    /**
     * Get all entities.
     *
     * @return Response
     */
    public function getAll()
    {
        return $this->getResponse()->collection($this->countries->all(), $this->transformer);
    }
}
