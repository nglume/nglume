<?php

/*
 * This file is part of the Spira framework.
 *
 * @link https://github.com/spira/spira
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

use App\Models\Image;
use App\Models\User;
use App\Models\Article;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Article::getTableName(), function (Blueprint $table) {
            $table->uuid('article_id')->primary();
            $table->string('title', 255);
            $table->enum('status', Article::$statuses)->default(Article::STATUS_DRAFT);
            $table->text('excerpt')->nullable();
            $table->uuid('thumbnail_image_id')->nullable();
            $table->string('permalink')->index()->nullable();
            $table->uuid('author_id')->index()->nullable();
            $table->boolean('author_display')->default(true);
            $table->boolean('show_author_promo')->default(false);
            $table->dateTime('first_published')->nullable();
            $table->json('sections_display')->nullable();

            $table->timestamps();

            $table->foreign('author_id')
                ->references('user_id')->on(User::getTableName())
                ->onDelete('set null');

            $table->foreign('thumbnail_image_id')
                ->references(Image::getPrimaryKey())->on(Image::getTableName())
                ->onDelete('set null');
        });

        Article::putMapping();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Article::getTableName());
    }
}
