<?php

namespace App\Models;

use Spira\Model\Model\BaseModel;

/**
 * @property string $permalink
 * @property Article article
 *
 * Class ArticlePermalink
 */
class ArticlePermalink extends BaseModel
{
    public $table = 'article_permalinks';

    protected $primaryKey = 'permalink';

    protected $fillable = ['permalink'];

    protected static $validationRules = [
        'permalink' => 'string|required',
        'check_entity_id' => 'uuid',
        'value' => 'required|string',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id', 'article_id');
    }
}
