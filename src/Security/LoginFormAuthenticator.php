<?php

namespace App\Security;

use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

use App\Entity\User;
use App\Repository\UserRepository;

class LoginFormAuthenticator extends AbstractFormLoginAuthenticator implements PasswordAuthenticatedInterface
{
    /*
    ----------------------------------------------------------------------------
        Traits
    ----------------------------------------------------------------------------
     */

    use TargetPathTrait;



    /*
    ----------------------------------------------------------------------------
        Constants & enumerators
    ----------------------------------------------------------------------------
     */

    public const LOGIN_ROUTE        = "user_login";
    public const LOGGED_IN_ROUTE    = "user_account";
    public const LOGOUT_ROUTE       = "user_logout";
    public const LOGGED_OUT_ROUTE   = "home_index";



    /*
    ----------------------------------------------------------------------------
        Variables
    ----------------------------------------------------------------------------
     */

    /**
     * @var EntityManagerInterface $em Entity manager
     */
    private $em;

    /**
     * @var UrlGeneratorInterface $urlGenerator URL generator service
     */
    private $urlGenerator;

    /**
     * @var CsrfTokenManagerInterface $csrfTokenManager CSRF token manager service
     */
    private $csrfTokenManager;

    /**
     * @var UserPasswordEncoderInterface $passwordEncoder Password encoder service
     */
    private $passwordEncoder;

    /**
     * @var TranslatorInterface $translator Translator service
     */
    private $translator;

    /**
     * @var UserRepository $userRepository User entity repository
     */
    private $userRepository;



    /*
    ----------------------------------------------------------------------------
        Life cycle functions
    ----------------------------------------------------------------------------
     */

    /**
     * Constructor.
     * @param  EntityManagerInterface       $em               Entity manager
     * @param  UrlGeneratorInterface        $urlGenerator     URL generator service
     * @param  CsrfTokenManagerInterface    $csrfTokenManager CSRF token manager service
     * @param  UserPasswordEncoderInterface $passwordEncoder  Password encoder service
     * @param  TranslatorInterface          $translator       Translator service
     * @param  UserRepository               $userRepository   User entity repository
     */
    public function __construct(EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager, UserPasswordEncoderInterface $passwordEncoder, TranslatorInterface $translator, UserRepository $userRepository)
    {
        $this->em               = $em;
        $this->urlGenerator     = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder  = $passwordEncoder;
        $this->translator       = $translator;
        $this->userRepository   = $userRepository;
    }



    /*
    ----------------------------------------------------------------------------
        Service functions
    ----------------------------------------------------------------------------
     */

    /**
     * Check if this authenticator is supported for the current request.
     * @param  Request $request Request instance
     * @return bool
     */
    public function supports(Request $request): bool
    {
        return self::LOGIN_ROUTE === $request->attributes->get("_route") && $request->isMethod(Request::METHOD_POST);
    }

    /**
     * Get credentials from a request.
     * @param  Request $request Request instance
     * @return array            Credentials
     */
    public function getCredentials(Request $request)
    {
        if (!$this->supports($request)) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, "Request unsupported.");
        }

        $credentials = [
            "username"      => $request->request->get("username"),
            "password"      => $request->request->get("password"),
            "csrf_token"    => $request->request->get("_csrf_token"),
        ];

        $request->getSession()->set(Security::LAST_USERNAME, $credentials["username"]);

        return $credentials;
    }

    /**
     * Get the user for the credentials offered.
     * @param  array                 $credentials  Credentials from request
     * @param  UserProviderInterface $userProvider User provider
     * @return User|null                           User entity, or null if not found
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?User
    {
        $token = new CsrfToken("authenticate", $credentials["csrf_token"]);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        $user = $this->userRepository->loadUserByUsername($credentials["username"]);
        if (!($user instanceof User)) {
            throw new CustomUserMessageAuthenticationException($this->translator->trans("Bad credentials.", [], "security"));
        }

        return $user;
    }

    /**
     * Check user credentials.
     * @param  array         $credentials Credentials from request
     * @param  UserInterface $user        User entity
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return $this->passwordEncoder->isPasswordValid($user, $credentials["password"]);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     * @param  array  $credentials Credentials from request
     * @return string              Upgraded password
     */
    public function getPassword($credentials): ?string
    {
        return $credentials["password"];
    }

    /**
     * Handle a successful authentication.
     * @param  Request        $request     Request instance
     * @param  TokenInterface $token       Authentication token
     * @param  string         $providerKey Provider key
     * @return Response                    Response instance
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->getLoggedInUrl());
    }

    /**
     * Get the URL to login.
     * @return string
     */
    protected function getLoginUrl(): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    /**
     * Get the default URL after logging in.
     * @return string
     */
    protected function getLoggedInUrl(): string
    {
        return $this->urlGenerator->generate(self::LOGGED_IN_ROUTE);
    }

    /**
     * Get the URL to logout.
     * @return string
     */
    protected function getLogoutUrl(): string
    {
        return $this->urlGenerator->generate(self::LOGOUT_ROUTE);
    }

    /**
     * Get the default URL after logging out.
     * @return string
     */
    protected function getLoggedOutUrl(): string
    {
        return $this->urlGenerator->generate(self::LOGGED_OUT_ROUTE);
    }
}
