<?php
declare(strict_types = 1);

namespace Apex\Armor\PGP;

use Apex\Armor\Armor;
use Apex\Armor\PGP\KeyManager;
use Apex\Armor\AES\DecryptAES;
use Apex\Armor\PGP\Exceptions\{ArmorPgpKeyNotExistsException, ArmorPgpInvalidKeyException, ArmorPgpDecryptException};
use Apex\Db\Interfaces\DbInterface;
use Apex\Container\Di;

/**
 * Decrypt PGP
 */
class DecryptPGP
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
     * Decrypt from uuid
     */
    public function fromUuid(string $data, string $uuid, string $password, bool $is_ascii = true):?string
    {

        // Initialize
        $pgp = gnupg_init();

        // Get key
        if (!$row = $this->db->getRow("SELECT * FROM armor_keys WHERE uuid = %s AND algo = 'pgp'", $uuid)) { 
            throw new ArmorPgpKeyNotExistsException("PGP key does not exist for uuid, $uuid");
        }

        // Get password
        $pgp_password = '';
        if ($row['password_id'] > 0) { 
            $aes = Di::make(DecryptAES::class);
            $pgp_password = $aes->fromUuid((Int) $row['password_id'], $uuid, $password, $is_ascii);
        }

        // Load private key
        if (!gnupg_adddecryptkey($pgp, $row['fingerprint'], $pgp_password)) { 
            throw new ArmorPgpInvalidKeyException("Invalid private PGP key or password for uuid, $uuid");
        }

        // Decrypt
        if (!$text = gnupg_decrypt($pgp, $data)) { 
            throw new ArmorPgpDecryptException("Unable to decrypt PGP message.  Either invalid private PGP key or password specified.");
        }

        // return
        return $text;
    }

    /**
     * From session
     */
    public function fromSession(string $data, AuthSession $session):?string
    {
        return $this->fromUuid($data, $session->getUuid(), $session->getEnchash(), false);
    }

}


