<?php namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Spira\Model\Model\BaseModel;
use Spira\Model\Model\IndexedModel;

/**
 * Class TestEntity
 *
 * @property Collection $testMany
 */
class TestEntity extends IndexedModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'test_entities';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['entity_id', 'varchar', 'hash', 'integer', 'decimal', 'boolean', 'nullable', 'text', 'date', 'multi_word_column_title', 'hidden'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['hidden'];

    protected $primaryKey = 'entity_id';

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'decimal'    => 'float',
        'date'       => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function getValidationRules()
    {
        return [
            'entity_id' => 'required|uuid',
            'varchar' => 'required|string',
            'hash'    => 'required|string',
            'integer' => 'required|integer',
            'decimal' => 'required|float',
            'boolean' => 'required|boolean',
            'text'    => 'required|string',
            'date'    => 'required|date',
            'multi_word_column_title' => 'required|boolean',
            'hidden'  => 'required|boolean'
        ];
    }


    public function testOne()
    {
        return $this->hasOne(SecondTestEntity::class, 'check_entity_id', 'entity_id');
    }

    public function testMany()
    {
        return $this->hasMany(SecondTestEntity::class, 'check_entity_id', 'entity_id');
    }
}
