<?php
declare(strict_types=1);

namespace ICANID\SDK\Helpers\Tokens;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256 as HsSigner;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

/**
 * Class SymmetricVerifier
 *
 * @package ICANID\SDK\Helpers
 */
final class SymmetricVerifier extends SignatureVerifier
{

    /**
     * Client secret for the application.
     *
     * @var string
     */
    private $clientSecret;

    /**
     * SymmetricVerifier constructor.
     *
     * @param string $clientSecret Client secret for the application.
     */
    public function __construct(string $clientSecret)
    {
        $this->clientSecret = $clientSecret;
        parent::__construct('HS256');
        
        $this->config = Configuration::forSymmetricSigner(
            new HsSigner(),
            InMemory::plainText($clientSecret)
        );
    }

    /**
     * Check the token signature.
     *
     * @param Token $token Parsed token to check.
     *
     * @return boolean
     */
    protected function checkSignature(Token $token) : bool
    {
        $constraint = new SignedWith(new HsSigner(), InMemory::plainText($this->clientSecret));
        return $this->config->validator()->validate($token, $constraint);
    }

    /**
     * Algorithm for signature check.
     *
     * @return string
     */
    protected function getAlgorithm() : string
    {
        return 'HS256';
    }
}
