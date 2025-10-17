<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AppCustomAuthAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator, private LoggerInterface $logger)
    {
    }

    public function authenticate(Request $request): Passport
    {
        // Support both normal form submissions (POST params) and payload-based submissions
        // (e.g. Turbo/JSON that uses Request::getPayload()). Read from POST first, then
        // fallback to the payload object when available.
        $payload = null;
        if (method_exists($request, 'getPayload')) {
            try {
                $payload = $request->getPayload();
            } catch (\Throwable $e) {
                $payload = null;
            }
        }
        $email = $request->request->get('email') ?? ($payload ? $payload->getString('email') : null);
        $password = $request->request->get('password') ?? ($payload ? $payload->getString('password') : null);
        $csrfToken = $request->request->get('_csrf_token') ?? ($payload ? $payload->getString('_csrf_token') : null);

        // Remember the last username entered (for the login form)
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        // Debug logging to help diagnose CSRF token source and presence
        try {
            $sessionId = $request->getSession()?->getId();
        } catch (\Throwable $e) {
            $sessionId = null;
        }

        $tokenSource = 'missing';
        if ($request->request->has('_csrf_token')) {
            $tokenSource = 'post';
        } elseif ($payload && $payload->has(' _csrf_token')) {
            // payload object may provide has()/getString() depending on Request internals
            $tokenSource = 'payload';
        } elseif ($payload && $payload->getString('_csrf_token')) {
            $tokenSource = 'payload';
        }

        $this->logger->debug('Login attempt', ['email' => $email, 'csrf_token_source' => $tokenSource, 'csrf_token_length' => $csrfToken ? strlen($csrfToken) : 0, 'session_id' => $sessionId]);

        return new Passport(
            new UserBadge((string) $email),
            new PasswordCredentials((string) $password),
            [
                new CsrfTokenBadge('authenticate', (string) $csrfToken),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Rediriger vers la page d'accueil après connexion réussie
        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
