<?php

use Illuminate\Support\Str;
use Laravel\Socialite\Two\User;
use App\Exceptions\NotImplementedException;
use App\Extensions\Socialite\SocialiteManager;
use App\Extensions\Socialite\One\AbstractProvider;
use App\Extensions\Socialite\Two\FacebookProvider;
use App\Extensions\Socialite\Parsers\ParserFactory;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Credentials\TemporaryCredentials;

class SocialiteTest extends TestCase
{
    public function testParserFactoryUnknownParser()
    {
        $this->setExpectedExceptionRegExp(
            NotImplementedException::class,
            '/parser.*/',
            0
        );

        $user = new User;
        $socialUser = ParserFactory::parse($user, 'foobar');
    }

    public function testCreateFacebookDriver()
    {
        $manager = new SocialiteManager($this->app);
        $driver = $manager->with('facebook');

        $this->assertInstanceOf(FacebookProvider::class, $driver);
    }

    public function testCreateTwitterDriver()
    {
        // If no twitter credentials exists in the env, add mock credentials
        if (!$this->app->config->get('services.twitter.client_id')) {
            $this->app->config->set('services.twitter.client_id', 'foo');
            $this->app->config->set('services.twitter.client_secret', 'bar');
        }

        $manager = new SocialiteManager($this->app);
        $driver = $manager->with('twitter');

        $this->assertInstanceOf('App\Extensions\Socialite\One\TwitterProvider', $driver);
    }

    public function testCreateGoogleDriver()
    {
        $manager = new SocialiteManager($this->app);
        $driver = $manager->with('google');

        $this->assertInstanceOf('App\Extensions\Socialite\Two\GoogleProvider', $driver);
    }

    public function testOAuthOneProviderRedirect()
    {
        $identifier = Str::random(40);
        $credentials = new TemporaryCredentials;
        $credentials->setIdentifier($identifier);

        $request = Mockery::mock('Illuminate\Http\Request');
        $server = Mockery::mock('League\OAuth1\Client\Server\Server');
        $cache = $this->app->cache;

        $server->shouldReceive('getTemporaryCredentials')
            ->once()
            ->andReturn($credentials)
            ->shouldReceive('getAuthorizationUrl')
            ->once()
            ->andReturn('http://foo.bar.baz');
        $request->shouldReceive('get')->once()->andReturn('http://foo.bar');

        $mock = Mockery::mock(AbstractProvider::class, [$request, $server, $cache])->makePartial();
        $mock->shouldReceive('temp->getIdentifier')->andReturn($identifier);

        $response = $mock->redirect();

        $this->assertEquals('http://foo.bar.baz', $response->headers->get('location'));
        $this->assertEquals('http://foo.bar', Cache::get('oauth_return_url_'.$identifier));
        $this->assertInstanceOf(TemporaryCredentials::class, Cache::get('oauth_temp_'.$identifier));
    }

    public function testOAuthOneGetCachedReturnUrlCache()
    {
        $request = Mockery::mock('Illuminate\Http\Request');
        $server = Mockery::mock('League\OAuth1\Client\Server\Server');
        $cache = $this->app->cache;
        $request->shouldReceive('get')->once();

        $mock = Mockery::mock(AbstractProvider::class, [$request, $server, $cache])->makePartial();

        $url = $mock->getCachedReturnUrl();

        $this->assertEquals(config('hosts.app'), $url);
    }

    public function testOAuthOneGetCachedReturnUrlNoCache()
    {
        $identifier = Str::random(40);
        $returnUrl = 'http://foo.bar';
        Cache::put('oauth_return_url_'.$identifier, $returnUrl, 1);

        $request = Mockery::mock('Illuminate\Http\Request');
        $server = Mockery::mock('League\OAuth1\Client\Server\Server');
        $cache = $this->app->cache;

        $request->shouldReceive('get')->once()->andReturn($identifier);

        $mock = Mockery::mock(AbstractProvider::class, [$request, $server, $cache])->makePartial();

        $url = $mock->getCachedReturnUrl();

        $this->assertEquals($url, $returnUrl);
    }

    public function testOAuthOneGetToken()
    {
        $request = Mockery::mock('Illuminate\Http\Request');
        $server = Mockery::mock('League\OAuth1\Client\Server\Server');
        $cache = Mockery::mock('Illuminate\Cache\CacheManager')->shouldAllowMockingProtectedMethods();
        $request->shouldReceive('get')->andReturn(Str::random(40));
        $cache->shouldReceive('get')->andReturn(new TemporaryCredentials);
        $server->shouldReceive('getTokenCredentials')->andReturn(new TokenCredentials);

        $mock = Mockery::mock(AbstractProvider::class, [$request, $server, $cache])->makePartial();

        $token = $mock->getToken();

        $this->assertInstanceOf(TokenCredentials::class, $token);
    }

    public function testProviderTraitUser()
    {
        $mock = Mockery::mock(FacebookProvider::class, [$this->app->request, null, null, null])->makePartial()->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('getAccessToken')
            ->once()
            ->andReturn([]);
        $mock->shouldReceive('getUserByToken')
            ->once()
            ->andReturn(['id' => 'foo']);

        $user = $mock->user();

        $this->assertInstanceOf(User::class, $user);
    }

    public function testProviderTraitgetCachedReturnUrlCache()
    {
        $returnUrl = 'http://foo.bar';
        Cache::put('oauth_return_url_foo', $returnUrl, 1);
        $request = Mockery::mock('Illuminate\Http\Request');
        $request->shouldReceive('get')
            ->once()
            ->andReturn('foo');

        $mock = Mockery::mock(FacebookProvider::class, [$request, null, null, null])->makePartial();

        $url = $mock->getCachedReturnUrl();

        $this->assertEquals($url, $returnUrl);
    }

    public function testProviderTraitgetCachedReturnUrlNoCache()
    {
        $request = Mockery::mock('Illuminate\Http\Request');
        $request->shouldReceive('get')->once()->andReturn(null);
        $mock = Mockery::mock(FacebookProvider::class, [$request, null, null, null])->makePartial();

        $url = $mock->getCachedReturnUrl();

        $this->assertEquals(config('hosts.app'), $url);
    }

    public function testAbstractParserMagicMethods()
    {
        $mock = Mockery::mock('App\Extensions\Socialite\Parsers\AbstractParser')->makePartial();
        $mock->token = 'foobar';

        $this->assertNull($mock->foobar);
        $this->assertEquals('foobar', $mock->token);
    }
}
