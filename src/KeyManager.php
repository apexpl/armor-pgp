<?php
declare(strict_types = 1);

namespace Apex\Armor\PGP;

use Apex\Armor\Armor;
use Apex\Armor\AES\EncryptAES;
use Apex\Armor\PGP\Exceptions\ArmorPgpInvalidKeyException;
use Apex\Db\Interfaces\DbInterface;
use Apex\Container\Di;


/**
 * Key manager
 */
class KeyManager
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
     * Import PGP key
     */
    public function import(string $uuid, string $pgp_key, string $password = ''):bool
    {

        // Initialize
        $pgp = gnupg_init();

        // Import key
        if (!$vars = gnupg_import($pgp, $pgp_key)) { 
            throw new ArmorPgpInvalidKeyException("Invalid PGP key, $public_key");
        }

        // Encrypt password, if needed
        $password_id = 0;
        if ($password != '') { 
            $aes = Di::make(EncryptAES::class);
            $password_id = $aes->toUuids($password, [$uuid], false);
        }

        // Check for existing key
        if ($row = $this->db->getRow("SELECT * FROM armor_keys WHERE uuid = %s AND algo = 'pgp'", $uuid)) { 

            $this->db->update('armor_keys', [
                'password_id' => $password_id, 
                'fingerprint' => $vars['fingerprint'], 
                'public_key' => $pgp_key, 
            ], "uuid = %s AND algo = 'pgp'", $uuid);

            // Return
            return true;
        }

        // Add new key
        $this->db->insert('armor_keys', [
            'uuid' => $uuid, 
            'algo' => 'pgp',
            'password_id' => $password_id,  
            'fingerprint' => $vars['fingerprint'], 
            'public_key' => $pgp_key
        ]);

        // Return
        return true;
    }

    /**
     * Get a PGP key
     */
    public function getKey(string $uuid):?string
    {

        // Get from db
        if (!$pgpkey = $this->db->getField("SELECT public_key FROM armor_keys WHERE uuid = %s AND algo = 'pgp'", $uuid)) { 
            return null;
        }

        // Return
        return $pgpkey;
    }

    /**
     * Get a PGP key
     */
    public function getFingerprint(string $uuid):?string
    {

        // Get from db
        if (!$fingerprint = $this->db->getField("SELECT fingerprint FROM armor_keys WHERE uuid = %s AND algo = 'pgp'", $uuid)) { 
            return null;
        }

        // Return
        return $fingerprint;
    }

    /**
     * Import all keys back into gnugp
     */
    public function importAll():int
    {

        // Initialize
        $pgp = gnupg_init();

        // Go through all PGP keys
        $total=0;
        $rows = $this->db->query("SELECT * FROM armor_keys WHERE algo = 'pgp'");
        foreach ($rows as $row) { 

            // import key
            if (!$vars = gnupg_import($pgp, $row['public_key'])) { 
                throw new ArmorPgpInvalidKeyException("Invalid PGP key, $public_key");
            }
            $total++;
        }

        // Return
        return $total;
    }

}


