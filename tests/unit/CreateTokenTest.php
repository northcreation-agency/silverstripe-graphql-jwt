<?php

namespace Firesphere\GraphQLJWT\Tests;

use Firesphere\GraphQLJWT\Authentication\AnonymousUserAuthenticator;
use Firesphere\GraphQLJWT\Authentication\CustomAuthenticatorRegistry;
use Firesphere\GraphQLJWT\Resolvers\Resolver;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;

class CreateTokenTest extends SapphireTest
{
    protected static $fixture_file = '../fixtures/JWTAuthenticatorTest.yml';

    protected $member;

    public function setUp()
    {
        Environment::putEnv('JWT_SIGNER_KEY=test_signer');

        parent::setUp();
        $this->member = $this->objFromFixture(Member::class, 'admin');
    }

    public function testResolveValid()
    {
        $response = Resolver::resolveCreateToken(
            null,
            ['email' => 'admin@silverstripe.com', 'password' => 'error']
        );

        $this->assertTrue($response['member'] instanceof Member);
        $this->assertNotNull($response['token']);
    }

    public function testResolveInvalidWithAllowedAnonymous()
    {
        Injector::inst()->get(CustomAuthenticatorRegistry::class)
            ->setCustomAuthenticators([
                AnonymousUserAuthenticator::singleton(),
            ]);
        $response = Resolver::resolveCreateToken(
            null,
            ['email' => 'anonymous']
        );

        /** @var Member $member */
        $member = $response['member'];
        $this->assertTrue($member instanceof Member);
        $this->assertTrue($member->exists());
        $this->assertEquals($member->Email, 'anonymous');
        $this->assertNotNull($response['token']);
    }

    public function testResolveInvalidWithoutAllowedAnonymous()
    {
        $response = Resolver::resolveCreateToken(
            null,
            ['email' => 'anonymous']
        );

        $this->assertNull($response['member']);
        $this->assertNull($response['token']);
    }
}
