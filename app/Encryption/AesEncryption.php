<?php

namespace App\Encryption;

class AesEncryption
{
    /**
     * Encryption algorithm name
     * @var string $algorithm
     */
    protected $algorithm = MCRYPT_RIJNDAEL_256;
    /**
     *
     * Encryption algorithm directory
     * @var string $algorithmDirectory
     */
    protected $algorithmDirectory = '';

    /**
     * Encryption descriptor
     * @var resource $handler
     */
    protected $handler;

    /**
     * AES initial vector
     * @var string $initialisationVector
     */
    protected $initialisationVector;

    /**
     * AES encryption/decryption key
     * @var string $key
     */
    protected $key;

    /**
     * Encryption mode name
     * @var string $mode
     */
    protected $mode = MCRYPT_MODE_CFB;

    /**
     * Encryption mode directory
     * @var string $modeDirectory
     */
    protected $modeDirectory;

    /**
     * AesEncryption constructor.
     * @param null|string $key
     * @param null|string $initialisationVector
     */
    public function __construct(?string $key, ?string $initialisationVector)
    {
        $this->key = $key;
        $this->initialisationVector = $initialisationVector;
        $this->handler = mcrypt_module_open(
            $this->algorithm,
            $this->algorithmDirectory,
            $this->mode,
            $this->modeDirectory
        );
    }

    /**
     * AesEncryption destructor
     */
    public function __destruct()
    {
        mcrypt_module_close($this->handler);
    }

    /**
     * Decrypts some data
     * @param string $encryptedData
     * @return string
     * @throws \Exception
     */
    public function decrypt(string $encryptedData)
    {
        if (empty($this->key)) {
            throw new \Exception('Failed to decrypt data - no encryption key is set');
        }

        if (empty($this->initialisationVector)) {
            throw new \Exception('Failed to decrypt data - no initial vector');
        }

        mcrypt_generic_init($this->handler, $this->key, $this->initialisationVector);

        $decryptedData = mdecrypt_generic($this->handler, $encryptedData);

        mcrypt_generic_deinit($this->handler);

        return $decryptedData;
    }

    /**
     * Encrypts some data
     * @param string $decryptedData
     * @return string
     * @throws \Exception
     */
    public function encrypt(string $decryptedData)
    {
        if (empty($this->key)) {
            throw new \Exception('Failed to decrypt data - no encryption key is set');
        }

        if (empty($this->initialisationVector)) {
            throw new \Exception('Failed to decrypt data - no initial vector');
        }

        mcrypt_generic_init($this->handler, $this->key, $this->initialisationVector);

        $encryptedData = mcrypt_generic($this->handler, $decryptedData);

        mcrypt_generic_deinit($this->handler);

        return $encryptedData;
    }

    /**
     * Returns the initialisation vector
     * @return null|string
     */
    public function getInitialisationVector(): ?string
    {
        return $this->initialisationVector;
    }

    /**
     * Returns the encryption key
     * @return null|string
     */
    public function getKey(): ?string
    {
        return $this->key;
    }
}
