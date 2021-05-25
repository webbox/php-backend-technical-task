<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use App\Security\LoginFormAuthenticator;

use App\Entity\User;
use App\Form\Type\UserType;
use Symfony\Component\Form\FormError;

/**
 * @Route("/", requirements={"_locale": "([a-z]{2})?"}, name="user_")
 */
class UserController extends AbstractController
{
    /**
     * View account.
     * @Route("{_locale}/account", name="account")
     * @Route("account", name="account-locale")
     * @param  Request             $request    Request instance
     * @param  LoggerInterface     $logger     Logger service
     * @param  TranslatorInterface $translator Translator service
     * @return Response                        Response instance
     */
    public function index(Request $request, LoggerInterface $logger, TranslatorInterface $translator): Response
    {
        if (!($user = $this->getUser())) {
            throw $this->createAccessDeniedException($translator->trans("Please login to continue.", [], "security"));
        }

        return $this->render("user/index.html.twig");
    }

    /**
     * Register account.
     * @Route("{_locale}/account/register", name="account_register")
     * @Route("account/register", name="account_register-locale")
     * @param  Request                      $request           Request instance
     * @param  LoggerInterface              $logger            Logger service
     * @param  TranslatorInterface          $translator        Translator service
     * @param  UserPasswordEncoderInterface $passwordEncoder   Password encoder service
     * @param  GuardAuthenticatorHandler    $guardHandler      Guard authenticator handler
     * @param  LoginFormAuthenticator       $formAuthenticator Form authenticator
     * @return Response                                        Response instance
     */
    public function register(Request $request, LoggerInterface $logger, TranslatorInterface $translator, UserPasswordEncoderInterface $passwordEncoder, GuardAuthenticatorHandler $guardHandler, LoginFormAuthenticator $formAuthenticator): Response
    {
        if ($this->getUser()) {
            $this->addFlash("info", $translator->trans("You are already logged in.", [], "security"));
            return $this->redirectToRoute("user_account");
        }

        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            try {
                $user->setPassword($passwordEncoder->encodePassword($user, $form->get("password")->getData()));
            } catch (\Exception $e) {
                $form->get("password")->addError(new FormError($translator->trans("sentence.password_encoding_failed")));
                $logger->error("Account creation failed.", [
                    "ipAddress" => $request->getClientIp(),
                    "user"      => $user->getUsername(),
                    "field"     => "password",
                    "reason"    => $e->getMessage(),
                ]);
            }

            if ($form->isValid()) {
                $logger->notice("Account created.", [
                    "ipAddress" => $request->getClientIp(),
                    "user"      => $user->getUsername(),
                ]);

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                $this->addFlash("success", $translator->trans("sentence.account_register_success"));
                return $guardHandler->authenticateUserAndHandleSuccess($user, $request, $formAuthenticator, "main");
            }
        }

        return $this->render("user/register.html.twig", [
            "user"  => $user,
            "form"  => $form->createView(),
        ]);
    }

    /**
     * Edit account.
     * @Route("{_locale}/account/edit", name="account_edit")
     * @Route("account/edit", name="account_edit-locale")
     * @param  Request                      $request         Request instance
     * @param  LoggerInterface              $logger          Logger service
     * @param  TranslatorInterface          $translator      Translator service
     * @param  UserPasswordEncoderInterface $passwordEncoder Password encoder service
     * @return Response                                      Response instance
     */
    public function edit(Request $request, LoggerInterface $logger, TranslatorInterface $translator, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        if (!($user = $this->getUser())) {
            throw $this->createAccessDeniedException($translator->trans("Please login to continue.", [], "security"));
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            try {
                $user->setPassword($passwordEncoder->encodePassword($user, $form->get("password")->getData()));
            } catch (\Exception $e) {
                $form->get("password")->addError(new FormError($translator->trans("sentence.password_encoding_failed")));
                $logger->error("Account update failed.", [
                    "ipAddress" => $request->getClientIp(),
                    "user"      => $user->getUsername(),
                    "field"     => "password",
                    "reason"    => $e->getMessage(),
                ]);
            }

            if ($form->isValid()) {
                $logger->notice("Account updated.", [
                    "ipAddress" => $request->getClientIp(),
                    "user"      => $user->getUsername(),
                ]);

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                $this->addFlash("success", $translator->trans("sentence.account_edit_success"));
                return $this->redirectToRoute(LoginFormAuthenticator::LOGGED_IN_ROUTE);
            }
        }

        return $this->render("user/edit.html.twig", [
            "form"  => $form->createView(),
            "user"  => $user,
        ]);
    }

    /**
     * Login.
     * @Route("{_locale}/account/login", name="login")
     * @Route("account/login", name="login-locale")
     * @param  Request             $request             Request instance
     * @param  LoggerInterface     $logger              Logger service
     * @param  TranslatorInterface $translator          Translator service
     * @param  AuthenticationUtils $authenticationUtils Authentication utility service
     * @return Response                                 Response instance
     */
    public function login(Request $request, LoggerInterface $logger, TranslatorInterface $translator, AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            $this->addFlash("info", $translator->trans("You are already logged in.", [], "security"));
            return $this->redirectToRoute(LoginFormAuthenticator::LOGGED_IN_ROUTE);
        }

        return $this->render("user/login.html.twig", [
            "lastUsername"  => $authenticationUtils->getLastUsername(),
            "error"         => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    /**
     * Logout.
     * @Route("{_locale}/account/logout", name="logout")
     * @Route("account/logout", name="logout-locale")
     * @param  Request             $request    Request instance
     * @param  LoggerInterface     $logger     Logger service
     * @param  TranslatorInterface $translator Translator service
     * @return Response                        Response instance
     */
    public function logout(Request $request, LoggerInterface $logger, TranslatorInterface $translator): Response
    {
        if (!$this->getUser()) {
            $this->addFlash("info", $translator->trans("You are not logged in.", [], "security"));
            return $this->redirectToRoute(LoginFormAuthenticator::LOGIN_ROUTE);
        }

        return $this->redirectToRoute(LoginFormAuthenticator::LOGGED_OUT_ROUTE);
    }
}
