<?php
declare(strict_types = 1);

use Apex\Armor\Armor;
use Apex\Armor\Policy\ArmorPolicy;
use Apex\Armor\PGP\{KeyManager, EncryptPGP, DecryptPGP, TwoFactorPGP};
use Apex\Armor\User\ArmorUser;
use Apex\Armor\Auth\{Login, AuthSession};
use Apex\Armor\Adapters\TestAdapter;
use Apex\Armor\Interfaces\AdapterInterface;
use Apex\Container\Di;
use Apex\Db\Interfaces\DbInterface;
use PHPUnit\Framework\TestCase;

/**
 * PGP Keys
 */
class keys_test extends TestCase
{

    /**
     * Test create
     */
    public function test_create()
    {

        // Init
        $armor = new Armor(
            container_file: $_SERVER['test_container_file']
        );
        $armor->purge();
        $user = $armor->createUser('u:test', 'password12345', 'test', 'test@apexpl.io', '14165551234');
        $this->assertEquals(ArmorUser::class, $user::class);

        // Import key
        $pgpkey = file_get_contents(__DIR__ . '/pgpkey.asc');
        $manager = new KeyManager($armor);
        $ok = $manager->import('u:test', $pgpkey, 'test12345');
        $this->assertTrue(true);

        // Check database
        $db = Di::get(DbInterface::class);
        $row = $db->getRow("SELECT * FROM armor_keys WHERE uuid = 'u:test' AND algo = 'pgp'");
        $this->assertIsArray($row);
        $this->assertNotEquals(0, $row['password_id']);
    }

    /**
     * Test encrypt
     */
    public function test_encrypt()
    {

        // Init
        $armor = new Armor(
            container_file: $_SERVER['test_container_file']
        );

        // Encrypt
        $enc = Di::make(EncryptPGP::class);
        $encdata = $enc->toUuids('unit test message', ['u:test']);
        $this->assertIsString($encdata);

        // Decrypt
        $dec = Di::make(DecryptPGP::class);
        $text = $dec->fromUuid($encdata, 'u:test', 'password12345');
        $this->assertEquals('unit test message', $text);
    }

    /**
     * 2FA
     */
    public function test_2fa()
    {

        // Get policy
        $policy = new ArmorPolicy(
            username_column: 'username', 
            require_password: 'require', 
            require_email: 'require', 
            two_factor_type: 'optional', 
            require_phone: 'optional', 
            two_factor_frequency: 'optional'
        );

        // Initialize
        $armor = new Armor(
            container_file: $_SERVER['test_container_file'], 
            policy: $policy
        );

        // Load adapter
        require_once(__DIR__ . '/files/TestAdapter.php');
        Di::set(AdapterInterface::class, Di::make(TestAdapter::class));

        // Get user
        $user = $armor->getUuid('u:test');
        $this->assertEquals(ArmorUser::class, $user::class);
        $user->updateTwoFactor('pgp', 'none');

        // Login
        $login = new Login($armor);
        $session = $login->withPassword('test', 'password12345');
        $this->assertEquals(AuthSession::class, $session::class);

        // Require 2FA
        $_POST['test_name'] = 'Matt Dizak';
        $session->requireTwoFactor();

        // Check handle status
        $redis = Di::get(redis::class);
        $vars = $redis->hgetall('armor:test:status');
        $this->assertIsArray($vars);
        $this->assertEquals('u:test', $vars['uuid']);
        $this->assertEquals('pgp', $vars['status']);

        // Check redis
        $msg = $redis->get('armor:pgp:u:test');
        $this->assertIsString($msg);

        // Decrypt
        $dec = Di::make(DecryptPGP::class);
        $hash = $dec->fromUuid($msg, 'u:test', 'password12345');
        $this->assertIsString($hash);

        // Verify
        $ver = Di::make(TwoFactorPGP::class);
        $ok = $ver->verify($session->getUser(), $hash);
        $this->assertTrue($ok);

        // Check redis
        $vars = $redis->hgetall('armor:test:2fa_auth');
        $this->assertIsArray($vars);
        $this->assertEquals('u:test', $vars['uuid']);
    }



}


