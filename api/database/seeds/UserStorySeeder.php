<?php

use App\Models\User;
use App\Models\UserCredential;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class UserStorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::indexExists() && User::deleteIndex(); //remove all entries from index

        $faker = Faker::create('au_AU');

        $this->createUser([
            'first_name' => 'John',
            'last_name' => 'Smith',
            'email' => 'john.smith@example.com',
            'user_type'=>'admin',
            'avatar_img_url' => $faker->imageUrl(100, 100, 'people'),
        ]);

        for ($i=0; $i < 99; $i++) {
            $this->createUser();
        }

        User::addAllToIndex(); //push all users to elastic search
    }

    /**
     * Create a new user with credentials.
     *
     * @param   array   $attributes
     * @return  void
     */
    protected function createUser(array $attributes = [])
    {
        $user = factory(User::class)->create($attributes);
        $user->setCredential(factory(UserCredential::class)->make());
        $user->setProfile(factory(UserProfile::class)->make());
    }
}
