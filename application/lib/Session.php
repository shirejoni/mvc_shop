<?php

namespace App\Lib;
class Session implements \SessionHandlerInterface {
    private $db;
    private $maxLifeSession;
    private $gcAction = false;

    /**
     * SessionManager constructor.
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->maxLifeSession = ini_get('session.gc_maxlifetime');
    }

    /**
     * Close the session
     * @link https://php.net/manual/en/sessionhandlerinterface.close.php
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function close()
    {
        if($this->gcAction) {
            try {
                $stmt = $this->db->query("DELETE FROM `session` WHERE `expiry` < :time", array(
                    'time' => time()
                ));
            }catch (\PDOException $e) {
                throw $e;
            }
        }
        return true;
    }

    /**
     * Destroy a session
     * @link https://php.net/manual/en/sessionhandlerinterface.destroy.php
     * @param string $session_id The session ID being destroyed.
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function destroy($session_id)
    {
        try {
            $this->db->query("DELETE FROM `session` WHERE `session_id` = :id", array(
                'id' => $session_id
            ));
            if($this->db->numRows()) {
                return true;
            }
        }catch (\PDOException $e) {
            throw $e;
        }
        return false;
    }

    /**
     * Cleanup old sessions
     * @link https://php.net/manual/en/sessionhandlerinterface.gc.php
     * @param int $maxlifetime <p>
     * Sessions that have not updated for
     * the last maxlifetime seconds will be removed.
     * </p>
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function gc($maxlifetime)
    {
        $this->gcAction = true;
        return true;
    }

    /**
     * Initialize session
     * @link https://php.net/manual/en/sessionhandlerinterface.open.php
     * @param string $save_path The path where to store/retrieve the session.
     * @param string $name The session name.
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function open($save_path, $name)
    {
        return true;
    }

    /**
     * Read session data
     * @link https://php.net/manual/en/sessionhandlerinterface.read.php
     * @param string $session_id The session id to read data for.
     * @return string <p>
     * Returns an encoded string of the read data.
     * If nothing was read, it must return an empty string.
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function read($session_id)
    {
        try {
            $stmt = $this->db->query("SELECT * FROM `session` WHERE `session_id` = :id", array(
                'id' => $session_id
            ));
            $result = $this->db->getRow();
            if($result) {
                if($result['expiry'] < time()) {
                    return '';
                }
                return json_decode($result['data']);
            }
            return '';
        }catch (\PDOException $e) {
            throw $e;
        }
    }

    /**
     * Write session data
     * @link https://php.net/manual/en/sessionhandlerinterface.write.php
     * @param string $session_id The session id.
     * @param string $session_data <p>
     * The encoded session data. This data is the
     * result of the PHP internally encoding
     * the $_SESSION superglobal to a serialized
     * string and passing it as this parameter.
     * Please note sessions use an alternative serialization method.
     * </p>
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function write($session_id, $session_data)
    {
        try {
            $this->db->query("REPLACE INTO `session` (`session_id`, `data`, `expiry`, `modify`) VALUES (:sid, :sdata, :sexp, :smd)", array(
                'sid' => $session_id,
                'sdata' => json_encode($session_data),
                'sexp' => time() + $this->maxLifeSession,
                'smd' => time()
            ));
            if($this->db->numRows()) {
                return true;
            }
        }catch (\PDOException $e) {
            throw $e;
        }
        return false;
    }
}