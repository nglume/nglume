<?php

use App\Models\User;
use App\Models\UserCredential;
use Rhumsaa\Uuid\Uuid;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UserTest extends TestCase
{
    use DatabaseTransactions, MailcatcherTrait;

    public function setUp()
    {
        parent::setUp();

        // Workaround for model event firing.
        // The package Bosnadev\Database used for automatic UUID creation relies
        // on model events (creating) to generate the UUID.
        //
        // Laravel/Lumen currently doesn't fire repeated model events during
        // unit testing, see: https://github.com/laravel/framework/issues/1181
        User::flushEventListeners();
        User::boot();
        UserCredential::flushEventListeners();
        UserCredential::boot();
    }

    /**
     * @param string $type
     * @return User
     */
    protected function createUser($type = 'admin')
    {
        $user = factory(User::class)->create(['user_type' => $type]);
        return $user;
    }

    public function testGetAllByAdminUser()
    {
        factory(User::class, 10)->create();
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
        $user = $factory->get(User::class)
            ->showOnly(['user_id', 'email', 'first_name', 'last_name'])
            ->append(
                '_userCredential',
                $factory->get(UserCredential::class)
                    ->hide(['self'])
                    ->makeVisible(['password'])
                    ->customize(['password' => 'password'])
                    ->toArray()
            );

        $transformerService = $this->app->make(App\Services\TransformerService::class);
        $transformer = new App\Http\Transformers\IlluminateModelTransformer($transformerService);
        $user = $transformer->transform($user);

        $this->put('/users/'.$user['userId'], $user);

        $response = json_decode($this->response->getContent());

        $createdUser = User::find($user['userId']);
        $this->assertResponseStatus(201);
        $this->assertEquals($user['firstName'], $createdUser->first_name);
        $this->assertObjectNotHasAttribute('_userCredential', $response);
    }

    public function testPutOneNoCredentials()
    {
        $factory = $this->app->make('App\Services\ModelFactory');
        $user = $factory->get(User::class)
            ->showOnly(['user_id', 'email', 'first_name', 'last_name']);

        $transformerService = $this->app->make(App\Services\TransformerService::class);
        $transformer = new App\Http\Transformers\IlluminateModelTransformer($transformerService);
        $user = $transformer->transform($user);

        $this->put('/users/'.$user['userId'], $user);

        $this->shouldReturnJson();
        $this->assertResponseStatus(422);
    }

    public function testPutOneAlreadyExisting()
    {
        $user = factory(User::class)->create();
        $user['_userCredential'] = ['password' => 'password'];

        $transformerService = $this->app->make(App\Services\TransformerService::class);
        $transformer = new App\Http\Transformers\IlluminateModelTransformer($transformerService);
        $user = array_except($transformer->transform($user), ['_self', 'userType']);

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
            'firstName' => 'foobar'
        ];

        $this->patch('/users/'.$userToUpdate->user_id, $update, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token
        ]);

        $updatedUser = User::find($userToUpdate->user_id);

        $this->assertResponseStatus(204);
        $this->assertResponseHasNoContent();
        $this->assertEquals('foobar', $updatedUser->first_name);
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
            'firstName' => 'foobar'
        ];

        $this->patch('/users/'.$userToUpdate->user_id, $update, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token
        ]);

        $updatedUser = User::find($userToUpdate->user_id);

        $this->assertResponseStatus(204);
        $this->assertResponseHasNoContent();
        $this->assertEquals('foobar', $updatedUser->first_name);
    }

    public function testPatchOneBySelfUserUUID()
    {
        $user = $this->createUser('guest');
        $userToUpdate = $user;
        $token = $this->tokenFromUser($user);

        $update = [
            'userId' => '1234',
            'firstName' => 'foobar'
        ];

        $this->patch('/users/'.$userToUpdate->user_id, $update, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token
        ]);

        $this->assertResponseStatus(422);
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

    public function testResetPasswordMail()
    {
        $this->clearMessages();
        $user = $this->createUser('guest');
        $token = $this->tokenFromUser($user);

        $this->delete('/users/'.$user->email.'/password', [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token
        ]);

        $mail = $this->getLastMessage();

        $this->assertResponseStatus(202);
        $this->assertResponseHasNoContent();
        $this->assertContains('Password', $mail->subject);

        // Additional testing, to ensure that the token sent, can only be used
        // one time.

        // Extract the token from the message source
        $msg = $this->getLastMessage();
        $source = $this->getMessageSource($msg->id);
        preg_match_all('/https?:\/\/\S(?:(?![\'"]).)*/', $source, $matches);
        $tokenUrl = trim($matches[0][0]);
        $parsed = parse_url($tokenUrl);
        $token = str_replace('passwordResetToken=', '', $parsed['query']);

        // Use it the first time
        $this->get('/auth/jwt/token', [
            'HTTP_AUTHORIZATION' => 'Token '.$token,
        ]);

        $this->assertResponseOk();

        // Use it the second time
        $this->get('/auth/jwt/token', [
            'HTTP_AUTHORIZATION' => 'Token '.$token,
        ]);

        $this->assertException('invalid', 401, 'UnauthorizedException');
    }

    public function testChangeEmail()
    {
        $this->clearMessages();
        $user = $this->createUser('guest');
        // Ensure that the current email is considered confirmed.
        $user->email_confirmed = date('Y-m-d H:i:s');
        $user->save();
        $token = $this->tokenFromUser($user);
        // Make a request to change email
        $update = ['email' => 'foo@bar.com'];
        $this->patch('/users/'.$user->user_id, $update, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token
        ]);
        // Ensure that we get the right response
        $this->assertResponseStatus(204);
        $this->assertResponseHasNoContent();
        // Check the sent email and ensure the user's email address hasn't changed yet
        $updatedUser = User::find($user->user_id);
        $mail = $this->getLastMessage();
        $this->assertContains('<foo@bar.com>', $mail->recipients);
        $this->assertNull($updatedUser->email_confirmed);
        $this->assertContains('Confirm', $mail->subject);
        // Get the token in the URL link
        $source = $this->getMessageSource($mail->id);
        preg_match_all('/https?:\/\/\S(?:(?![\'"]).)*/', $source, $matches);
        $tokenUrl = $matches[0][0];
        $parsed = parse_url($tokenUrl);
        $emailToken = str_replace('emailConfirmationToken=', '', $parsed['query']);
        // Confirm the email change
        $datetime = date(\DateTime::ISO8601);
        $update = ['emailConfirmed' => $datetime];
        $this->patch('/users/'.$user->user_id, $update, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'email-confirm-token' => $emailToken
        ]);
        // Ensure we get the right response
        $this->assertResponseStatus(204);
        $this->assertResponseHasNoContent();
        // Check to see if the email has changed correctly
        $updatedUser = User::find($user->user_id);
        $this->assertEquals($datetime, date(\DateTime::ISO8601, strtotime($updatedUser->email_confirmed)));
        $this->assertEquals('foo@bar.com', $updatedUser->email);
    }

    public function testUpdateEmailConfirmed()
    {
        $user = $this->createUser('guest');
        $token = $this->tokenFromUser($user);
        $datetime = date('Y-m-d H:i:s');
        $update = ['emailConfirmed' => $datetime];
        $cache = $this->app->make('Illuminate\Contracts\Cache\Repository');
        $emailToken = $user->makeConfirmationToken($user->email, $cache);
        $this->patch('/users/'.$user->user_id, $update, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'Email-Confirm-Token' => $emailToken
        ]);
        $updatedUser = User::find($user->user_id);
        $this->assertResponseStatus(204);
        $this->assertResponseHasNoContent();
        $this->assertEquals($datetime, date('Y-m-d H:i:s', strtotime($updatedUser->email_confirmed)));
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
