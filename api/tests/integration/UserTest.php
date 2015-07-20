<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();
    }

    protected function createUser($type = 'admin')
    {
        $user = factory(App\Models\User::class)->create(['user_type' => $type]);
        return $user;
    }

    public function testGetAllByAdminUser()
    {
        $user = $this->createUser();
        $token = $this->tokenFromUser($user);

        $this->get('/users', [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token
        ]);

        $this->assertResponseOk();
        $this->shouldReturnJson();
        $this->assertJsonArray();
        $this->assertJsonMultipleEntries();
    }

    public function testGetAllByGuestUser()
    {
        $user = $this->createUser('guest');
        $token = $this->tokenFromUser($user);

        $this->get('/users', [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token
        ]);

        $this->assertException('Denied', 403, 'ForbiddenException');
    }

    public function testGetOneByAdminUser()
    {
        $user = $this->createUser();
        $userToGet = $this->createUser('guest');
        $token = $this->tokenFromUser($user);

        $this->get('/users/'.$userToGet->user_id, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token
        ]);

        $this->assertResponseOk();
        $this->shouldReturnJson();
        $this->assertJsonArray();
    }

    public function testGetOneByGuestUser()
    {
        $user = $this->createUser('guest');
        $userToGet = $this->createUser('guest');
        $token = $this->tokenFromUser($user);

        $this->get('/users/'.$userToGet->user_id, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token
        ]);

        $this->assertException('Denied', 403, 'ForbiddenException');
    }

    public function testGetOneBySelfUser()
    {
        $user = $this->createUser('guest');
        $userToGet = $user;
        $token = $this->tokenFromUser($user);

        $this->get('/users/'.$userToGet->user_id, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token
        ]);

        $this->assertResponseOk();
        $this->shouldReturnJson();
        $this->assertJsonArray();
    }

    public function testPutOne()
    {
        $factory = $this->app->make('App\Services\ModelFactory');
        $user = $factory->get(\App\Models\User::class)
            ->showOnly(['user_id', 'email', 'first_name', 'last_name'])
            ->append('#userCredential',
                $factory->get(\App\Models\UserCredential::class)
                    ->hide(['self'])
                    ->makeVisible(['password'])
                    ->customize(['password' => 'password'])
                    ->toArray()
                );

        $transformer = $this->app->make('App\Http\Transformers\BaseTransformer');
        $user = $transformer->transform($user);

        $this->put('/users/'.$user['userId'], $user);

        $createdUser = User::find($user['userId']);
        $this->assertResponseStatus(201);
        $this->assertResponseHasNoContent();
        $this->assertEquals($user['firstName'], $createdUser->first_name);
    }

    public function testPutOneAlreadyExisting()
    {
        $user = factory(App\Models\User::class)->create();
        $transformer = $this->app->make('App\Http\Transformers\BaseTransformer');
        $user = array_except($transformer->transform($user), ['_self', 'userType', 'emailConfirmed']);
        $user['#userCredential'] = ['password' => 'password'];

        $this->put('/users/'.$user['userId'], $user);

        $this->shouldReturnJson();
        $this->assertResponseStatus(422);
    }

    public function testPatchOneByAdminUser()
    {
        $user = $this->createUser('admin');
        $userToUpdate = $this->createUser('guest');
        $token = $this->tokenFromUser($user);

        $update = [
            'userId' => $userToUpdate->user_id,
            'email' => 'foo@bar.com',
            'firstName' => 'foobar'
        ];

        $this->patch('/users/'.$userToUpdate->user_id, $update, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token
        ]);

        $updatedUser = User::find($userToUpdate->user_id);

        $this->assertResponseStatus(204);
        $this->assertResponseHasNoContent();
        $this->assertEquals('foobar', $updatedUser->first_name);
        $this->assertEquals('foo@bar.com', $updatedUser->email);
    }

    public function testPatchOneByGuestUser()
    {
        $user = $this->createUser('guest');
        $userToUpdate = $this->createUser('guest');
        $token = $this->tokenFromUser($user);

        $this->patch('/users/'.$userToUpdate->user_id, [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token
        ]);

        $this->assertException('Denied', 403, 'ForbiddenException');
    }

    public function testPatchOneBySelfUser()
    {
        $user = $this->createUser('guest');
        $userToUpdate = $user;
        $token = $this->tokenFromUser($user);

        $update = [
            'userId' => $userToUpdate->user_id,
            'email' => 'foo@bar.com',
            'firstName' => 'foobar'
        ];

        $this->patch('/users/'.$userToUpdate->user_id, $update, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token
        ]);

        $updatedUser = User::find($userToUpdate->user_id);

        $this->assertResponseStatus(204);
        $this->assertResponseHasNoContent();
        $this->assertEquals('foobar', $updatedUser->first_name);
        $this->assertEquals('foo@bar.com', $updatedUser->email);
    }

    public function testDeleteOneByAdminUser()
    {
        $user = $this->createUser('admin');
        $userToDelete = $this->createUser('guest');
        $token = $this->tokenFromUser($user);
        $rowCount = User::count();

        $this->delete('/users/'.$userToDelete->user_id, [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token
        ]);

        $this->assertResponseStatus(204);
        $this->assertResponseHasNoContent();
        $this->assertEquals($rowCount - 1, User::count());
    }

    public function testDeleteOneByGuestUser()
    {
        $user = $this->createUser('guest');
        $userToDelete = $this->createUser('guest');
        $token = $this->tokenFromUser($user);
        $rowCount = User::count();

        $this->delete('/users/'.$userToDelete->user_id, [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token
        ]);

        $this->assertResponseStatus(403);
        $this->assertEquals($rowCount, User::count());
    }

    public function testChangeEmail()
    {
        $user = $this->createUser('guest');

        // Ensure that the current email is considered confirmed.
        $user->email_confirmed = date('Y-m-d H:i:s');
        $user->save();

        $token = $this->tokenFromUser($user);
        $update = ['email' => 'foo@bar.com'];

        $this->patch('/users/'.$user->user_id, $update, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token
        ]);

        $updatedUser = User::find($user->user_id);

        $this->assertResponseStatus(204);
        $this->assertResponseHasNoContent();
        $this->assertEquals('foo@bar.com', $updatedUser->email);
        $this->assertNull($updatedUser->email_confirmed);
    }

    public function testUpdateEmailConfirmed()
    {
        $user = $this->createUser('guest');
        $token = $this->tokenFromUser($user);
        $datetime = date('Y-m-d H:i:s');
        $update = ['emailConfirmed' => $datetime];

        $repo = $this->app->make('App\Repositories\UserRepository');
        $emailToken = $repo->makeConfirmationToken($user->email);

        $this->patch('/users/'.$user->user_id, $update, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'Email-Confirm-Token' => $emailToken
        ]);

        $updatedUser = User::find($user->user_id);

        $this->assertResponseStatus(204);
        $this->assertResponseHasNoContent();
        $this->assertEquals($datetime, $updatedUser->email_confirmed);
    }

    public function testUpdateEmailConfirmedInvalidToken()
    {
        $user = $this->createUser('guest');
        $token = $this->tokenFromUser($user);
        $datetime = date('Y-m-d H:i:s');
        $update = ['emailConfirmed' => $datetime];

        $repo = $this->app->make('App\Repositories\UserRepository');
        $emailToken = 'foobar';

        $this->patch('/users/'.$user->user_id, $update, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'Email-Confirm-Token' => $emailToken
        ]);

        $this->assertResponseStatus(422);
    }
}
