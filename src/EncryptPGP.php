<?php
declare(strict_types = 1);

namespace Apex\Armor\PGP;

use Apex\Armor\Armor;
use Apex\Armor\PGP\KeyManager;
use Apex\Armor\PGP\Exceptions\ArmorPgpKeyNotExistsException;
use Apex\Db\Interfaces\DbInterface;
use Apex\Container\Di;


/**
 * Encrypt to PGP
 */
class EncryptPGP
{

    /**
     * Constructor
     */
    public function __construct(
        private Armor $armor
    ) { 
        $this->db = Di::get(DbInterface::class);
    }

    /**
     * Encrypt to users
     */
    public function toUuids(string $data, array $uuids):?string
    {

        // Initialize
        $pgp = gnupg_init();
        $manager = Di::make(KeyManager::class);

        // GO through uuids
        foreach ($uuids as $uuid) {

            // Get fingerprint
            if (!$fingerprint = $manager->getFingerprint($uuid)) { 
                throw new ArmorPgpKeyNotExistsException("PGP key does not exist for uuid, $uuid");
            }

            // Add to recipients
            gnupg_addencryptkey($pgp, $fingerprint);
        }

        // Encrypt and return
        $encdata = gnupg_encrypt($pgp, $data);
        return $encdata;
    }

}


