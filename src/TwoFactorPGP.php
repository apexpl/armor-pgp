<?php
declare(strict_types = 1);

namespace Apex\Armor\PGP;

use Apex\Armor\Armor;
use Apex\Armor\Auth\AuthSession;
use Apex\Armor\Auth\TwoFactor\TwoFactor;
use Apex\Armor\PGP\EncryptPGP;
use Apex\Armor\Auth\Codes\StringCode;
use Apex\Armor\Interfaces\{AdapterInterface, ArmorUserInterface};
use Apex\Container\Di;
use redis;


/**
 * Two factor - GP
 */
class TwoFactorPGP extends TwoFactor
{

    /**
     * Constructor
     */
    public function __construct(
        private Armor $armor
    ) { 

    }

    /**
     * Initialize
     */
    public function init(AuthSession $session, bool $is_login = false):void
    {

        // Create session
        list($code, $redis_key) = StringCode::get('2fa', 24);
        $this->createRequest($session, $redis_key, $is_login);

        // Encrypt message
        $pgp = Di::make(EncryptPGP::class);
        $message = $pgp->toUuids($code, [$session->getUuid()]); 

        // Save to redis
        $redis = Di::get(redis::class);
        $redis->set('armor:pgp:' . $session->getUuid(), $message);
        $redis->expire('armor:pgp:' . $session->getUuid(), 600); 
    }

    /**
     * Verify
     */
    public function verify(ArmorUserInterface $user, string $code):bool
    {

        // Get request
        $redis_key = 'armor:2fa:' . hash('sha512', $code);
        if (!list($session, $server_request, $is_login) = $this->getRequest($redis_key)) { 
            return false;
        }

        // Handle request
        $adapter = Di::get(AdapterInterface::class);
        $adapter->handleTwoFactorAuthorized($session, $server_request, $is_login);

        // Return
        return true;
    }

}


