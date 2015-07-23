<?php namespace App\Repositories;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use App\Exceptions\ValidationException;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\ConnectionResolverInterface as Connection;

class UserRepository extends BaseRepository
{
    /**
     * Login token time to live in minutes.
     *
     * @var int
     */
    protected $login_token_ttl = 1440;

    /**
     * Confirmation token time to live in minutes.
     *
     * @var int
     */
    protected $confirmation_token_ttl = 1440;

    /**
     * Cache repository.
     *
     * @var Cache
     */
    protected $cache;

    /**
     * Assign dependencies.
     *
     * @param  Connection  $connection
     * @param  Cache       $cache
     * @return void
     */
    public function __construct(Connection $connection, Cache $cache)
    {
        parent::__construct($connection);

        $this->cache = $cache;
    }

    /**
     * Model name.
     *
     * @return User
     */
    protected function model()
    {
        return new User;
    }

    /**
     * Get a user by single use login token.
     *
     * @param string $token
     *
     * @return mixed
     */
    public function findByLoginToken($token)
    {
        if ($id = $this->cache->pull('login_token_'.$token)) {
            $user = $this->find($id);

            return $user;
        }
    }

    /**
     * Make a single use login token for a user.
     *
     * @param string $id
     *
     * @return string
     */
    public function makeLoginToken($id)
    {
        $user = $this->find($id);

        $token = hash_hmac('sha256', str_random(40), str_random(40));
        $this->cache->put('login_token_'.$token, $user->user_id, $this->login_token_ttl);

        return $token;
    }

    /**
     * Make an email confirmation token for a user.
     *
     * @param string $email
     *
     * @return string
     */
    public function makeConfirmationToken($email)
    {
        $token = hash_hmac('sha256', str_random(40), str_random(40));
        $this->cache->put('email_confirmation_'.$token, $email, $this->confirmation_token_ttl);
        return $token;
    }

    /**
     * If the email_confirmation field is set, make sure we've a valid token.
     *
     * @param  Request  $request
     * @return void
     */
    public function validateEmailConfirmationToken(Request $request)
    {
        if ($request->get('email_confirmed')) {
            $token = $request->headers->get('email-confirm-token');
            if (!$email = $this->cache->pull('email_confirmation_'.$token)) {

                throw new ValidationException(
                    new MessageBag(['email_confirmed' => 'The email confirmation token is not valid.'])
                );
            }
        }
    }
}
