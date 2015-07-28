<?php namespace App\Models;

use Spira\Repository\Collection\Collection;

/**
 *
 * @property ArticlePermalink[]|Collection $permalinks
 * @property string $permalink
 *
 * Class Article
 * @package App\Models
 *
 */
class Article extends BaseModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'articles';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['article_id', 'title', 'content', 'permalink', 'first_published'];

    protected $hidden = ['permalinks'];

    protected $primaryKey = 'article_id';

    protected $casts = [
        'first_published' => 'datetime',
    ];

    public function getValidationRules()
    {
        $permalinkRule = 'string|unique:article_permalinks,permalink';
        if (!is_null($this->permalink)){
            $permalinkRule.=','.$this->permalink.',permalink';
        }
        return [
            'article_id' => 'uuid',
            'title' => 'required|string',
            'content' => 'required|string',
            'permalink' => $permalinkRule
        ];
    }

    /**
     * Listen for save event
     *
     * Saving permalink to history
     */
    protected static function boot()
    {
        parent::boot();
        static::saved(function (Article $model) {
            if ($model->getOriginal('permalink') !== $model->permalink && !is_null($model->permalink)) {
                $articlePermalink = new ArticlePermalink([
                    'article_id' => $model->article_id,
                    'permalink' => $model->permalink
                ]);
                return $articlePermalink->save();
            }
            return true;
        });
    }

    public function setPermalinkAttribute($permalink)
    {
        if ($permalink){
            $this->attributes['permalink'] = $permalink;
        }else{
            $this->attributes['permalink'] = null;
        }
    }

    public function permalinks()
    {
        $relation = $this->hasMany(ArticlePermalink::class, 'article_id', 'article_id');
        return $relation;
    }
}
