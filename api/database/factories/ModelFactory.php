<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

use Illuminate\Support\Str;
use Carbon\Carbon;

$factory->define(App\Models\User::class, function ($faker) {
    return [
        'user_id'    => $faker->uuid,
        'email'      => $faker->email,
        'password'   => Hash::make('password'),
        'first_name' => $faker->firstName,
        'last_name'  => $faker->lastName,
        'phone'      => $faker->optional(0.5)->phoneNumber,
        'mobile'     => $faker->optional(0.5)->phoneNumber,
        'user_type'  => $faker->randomElement(App\Models\User::$userTypes),
    ];
});

$factory->defineAs(App\Models\User::class, 'admin', function ($faker) use ($factory) {
    $user = $factory->raw(App\Models\User::class);

    return array_merge($user, ['userType' => App\Models\User::USER_TYPE_ADMIN]);
});

$factory->define(App\Models\TestEntity::class, function ($faker) {
    return [
        'entity_id'               => $faker->uuid,
        'varchar'                 => $faker->word,
        'hash'                    => Hash::make($faker->word),
        'integer'                 => $faker->numberBetween(0, 500),
        'decimal'                 => $faker->randomFloat(2, 0, 100),
        'boolean'                 => $faker->boolean(),
        'nullable'                => null,
        'text'                    => $faker->paragraph(3),
        'date'                    => $faker->date(),
        'multi_word_column_title' => true,
        'hidden'                  => $faker->boolean(),
    ];
});

$factory->define(App\Models\SecondTestEntity::class, function ($faker) {
    return [
        'entity_id' => $faker->uuid,
        'check_entity_id' => $faker->uuid,
        'value' => $faker->word
    ];
});

$factory->defineAs(App\Models\TestEntity::class, 'custom', function ($faker) use ($factory) {
    $testEntity = $factory->raw(App\Models\TestEntity::class);

    return array_merge($testEntity, ['varchar' => 'custom']);
});

$factory->define(App\Models\AuthToken::class, function ($faker) {

    $hostname = env('APP_HOSTNAME', 'localhost');

    $user = factory(App\Models\User::class)->make();
    $now = new Carbon();

    $body = [
        'iss'  => $hostname,
        'aud'  => str_replace('.api', '', $hostname),
        'sub'  => $user->user_id,
        'nbf'  => $now->timestamp,
        'iat'  => $now->timestamp,
        'exp'  => $now->addHour(1)->timestamp,
        'jti'  => $faker->regexify('[A-Za-z0-9]{8}'),
        'user' => $user->toArray(),
    ];

    $jwtAuth = \App::make('Tymon\JWTAuth\JWTAuth');
    $token = $jwtAuth->fromUser($user);

    return compact('token') + $body;
});

$factory->define(App\Models\Article::class, function ($faker) {

    return [
        'article_id' => $faker->uuid,
        'title' => $faker->sentence,
        'content' => $content = implode("\n\n", $faker->paragraphs(3)),
        'excerpt' => Str::words($content, 30),
        'permalink' => $faker->slug,
        'first_published' => $faker->optional(0.9)->dateTimeThisDecade(),
    ];

});


