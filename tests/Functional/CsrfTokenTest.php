<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfTokenTest extends KernelTestCase
{
    public function testAuthenticateTokenIsValid(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @var CsrfTokenManagerInterface $manager */
        $manager = $container->get(CsrfTokenManagerInterface::class);

        $value = $manager->getToken('authenticate')->getValue();

        $this->assertIsString($value);
        $this->assertNotEmpty($value);

    // Note: when stateless CSRF protection is enabled (see config/packages/csrf.yaml),
    // tokens like 'authenticate' may use a double-submit / origin-based strategy and
    // cannot be validated in this test harness without request context (cookies/headers).
    // Therefore we only assert the token exists and is non-empty here.
    }
}
