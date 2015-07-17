<?php
/**
 * Created by PhpStorm.
 * User: redjik
 * Date: 13.07.15
 * Time: 19:24
 */

namespace Spira\Repository\Model;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use LogicException;
use Spira\Repository\Collection\Collection;

/**
 * Class BaseModel
 * @package Spira\Repository\Model
 *
 * @method count
 * @method get
 * @method findOrFail
 * @method findMany
 *
 */
class BaseModel extends Model
{

    /**
     * @var Relation[]
     */
    protected static $relationsCache = [];

    /**
     * @var BaseModel[]
     */
    protected $deleteStack = [];

    protected $isDeleted = false;

    /**
     * @param string $key
     * @param mixed $value
     * @throws SetRelationException
     */
    public function __set($key, $value)
    {
        if (method_exists($this, $key)){
            try{
                $value = $this->prepareValue($value);
            }catch (\InvalidArgumentException $e){
                $value = false;
            }

            if ($value !== false){
                $models = $this->getRelationValue($key);
                $this->addPreviousValueToDeleteStack($models);
                $this->isValueCompatibleWithRelation($key,$value);
                $this->relations[$key] = $value;
            }

        }else{
            parent::__set($key, $value);
        }

    }

    /**
     * Prepare value for proper assignment
     * @param array|Collection|false|BaseModel $value
     * Can be array, empty array, null, false, Collection or Model
     * @return null|Collection|BaseModel
     */
    protected function prepareValue($value)
    {
        if (empty($value)){
            return null;
        }

        if ($this->isModel($value) || $this->isCollection($value)){
            return $value;
        }

        if (is_array($value)){
            $firstModel = current($value);
            if ($firstModel instanceof BaseModel){
                return $firstModel->newCollection($value);
            }
            throw new \InvalidArgumentException('Array must consist of '.BaseModel::class.' instances');
        }

        throw new \InvalidArgumentException('Value can not be assigned to relation');
    }

    /**
     * @param $models
     */
    protected function addPreviousValueToDeleteStack($models)
    {
        /** @var Collection|array $models */
        $models = $this->isCollection($models)?$models->all(true):[$models];
        $this->deleteStack = array_merge($this->deleteStack, array_filter($models));
    }


    /**
     * Save the model and all of its relationships.
     *
     * @return bool
     */
    public function push()
    {
        if (!$this->save()) {
            return false;
        }

        foreach ($this->deleteStack as $modelToDelete)
        {
            if (!$modelToDelete->delete()) {
                return false;
            }
            $this->deleteStack = [];
        }

        // To sync all of the relationships to the database, we will simply spin through
        // the relationships and save each model via this "push" method, which allows
        // us to recurse into all of these nested relations for the model instance.
        foreach ($this->relations as $key => $models) {
            /** @var Collection|array $models */
            $models = $this->isCollection($models)? $models->all(true) : [$models];
            $relation = static::$relationsCache[$this->getRelationCacheKey($key)];
            foreach (array_filter($models) as $model) {
                /** @var BaseModel $model */
                $model->preserveKeys($relation);
                if (!$model->push()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Checks relation against value
     * @param $method
     * @param $value
     * @return bool
     * @throws SetRelationException
     */
    protected function isValueCompatibleWithRelation($method, $value)
    {
        if (is_null($value)){
            return true;
        }

        $relation = static::$relationsCache[$this->getRelationCacheKey($method)];

        if ($relation instanceof HasOne || $relation instanceof BelongsTo){
            if ($this->isCollection($value)){
                throw new SetRelationException('Can not set collection, model expected');
            }
        }else{
            if ($this->isModel($value)){
                throw new SetRelationException('Can not set model, collection expected');
            }
        }

        return true;
    }

    /**
     * @param $value
     * @return bool
     */
    protected function isModel($value)
    {
        return $value instanceof BaseModel;
    }

    /**
     * @param $value
     * @return bool
     */
    protected function isCollection($value)
    {
        return $value instanceof Collection;
    }

    /**
     * @param Relation $relation
     */
    protected function preserveKeys(Relation $relation)
    {
        if ($relation instanceof HasOneOrMany){
            $fk = str_replace($this->getTable().'.','',$relation->getForeignKey());
            $this->{$fk} = $relation->getParentKey();
        }

//        if ($relation instanceof BelongsTo){
//            $fk = str_replace($this->getTable().'.','',$relation->getForeignKey());
//            $relation->getParent()->$fk = $this->{$relation->getOtherKey()};
//        }
    }

    /**
     * @param array $options
     * @return bool|null
     * @throws \Exception
     */
    public function save(array $options = [])
    {
        if ($this->isDeleted()){
            return $this->delete();
        }

        return parent::save($options);

    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models, static::class);
    }

    /**
     * @return boolean
     */
    public function isDeleted()
    {
        return $this->isDeleted;
    }

    /**
     *
     */
    public function markAsDeleted()
    {
        $this->isDeleted = true;
    }

    /**
     * Get a relationship value from a method.
     * Relation cache added
     *
     * @param  string  $method
     * @return mixed
     *
     * @throws \LogicException
     */
    protected function getRelationshipFromMethod($method)
    {
        $relations = $this->$method();

        if (!$relations instanceof Relation) {
            throw new LogicException('Relationship method must return an object of type '
                .'Illuminate\Database\Eloquent\Relations\Relation');
        }else{
            static::$relationsCache[$this->getRelationCacheKey($method)] = $relations;
        }

        return $this->relations[$method] = $relations->getResults();
    }

    /**
     * @param $method
     * @return string
     */
    protected function getRelationCacheKey($method)
    {
        return static::class.'_'.$method;
    }

}