<?php

/*
 * This file is part of the Spira framework.
 *
 * @link https://github.com/spira/spira
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

use App\Models\Tag;
use App\Models\Image;
use App\Models\Article;
use App\Models\ArticleMeta;
use App\Models\ArticleImage;
use App\Models\ArticlePermalink;

class ArticleSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $images = Image::all();

        factory(Article::class, 50)
            ->create()
            ->each(function (Article $article) use ($images) {

                //add metas
                $metas = factory(ArticleMeta::class, 2)->make()->all();
                $article->articleMeta()->saveMany($metas);

                //add permalinks
                $permalinks = factory(ArticlePermalink::class, 2)->make()->all();
                $article->articlePermalink()->saveMany($permalinks);

                //add tags
                $tags = factory(Tag::class, 2)->make()->all();
                $article->tag()->saveMany($tags);

                $this->randomElements($images)
                    ->each(function (Image $image) use ($article) {
                    factory(ArticleImage::class)->create([
                        'article_id' => $article->article_id,
                        'image_id' => $image->image_id,
                    ]);
                });

            });
    }
}
