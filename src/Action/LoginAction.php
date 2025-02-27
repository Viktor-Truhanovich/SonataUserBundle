<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Action;

use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\SonataConfiguration;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Sonata\UserBundle\Model\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class LoginAction
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var Pool
     */
    private $adminPool;

    /**
     * @var SonataConfiguration
     */
    private $config;

    /**
     * @var TemplateRegistryInterface
     */
    private $templateRegistry;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    public function __construct(
        Environment $twig,
        UrlGeneratorInterface $urlGenerator,
        AuthorizationCheckerInterface $authorizationChecker,
        Pool $adminPool,
        SonataConfiguration $config,
        TemplateRegistryInterface $templateRegistry,
        TokenStorageInterface $tokenStorage,
        Session $session,
        TranslatorInterface $translator = null
    ) {
        $this->twig = $twig;
        $this->urlGenerator = $urlGenerator;
        $this->authorizationChecker = $authorizationChecker;
        $this->adminPool = $adminPool;
        $this->config = $config;
        $this->templateRegistry = $templateRegistry;
        $this->tokenStorage = $tokenStorage;
        $this->session = $session;
        $this->translator = $translator;
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(Request $request): Response
    {
        if ($this->isAuthenticated()) {
            $this->session->getFlashBag()->add(
                'sonata_user_error',
                $this->translator->trans('sonata_user_already_authenticated', [], 'SonataUserBundle')
            );

            return new RedirectResponse($this->urlGenerator->generate('sonata_admin_dashboard'));
        }

        $session = $request->getSession();

        $authErrorKey = Security::AUTHENTICATION_ERROR;

        // get the error if any (works with forward and redirect -- see below)
        if ($request->attributes->has($authErrorKey)) {
            $error = $request->attributes->get($authErrorKey);
        } elseif (null !== $session && $session->has($authErrorKey)) {
            $error = $session->get($authErrorKey);
            $session->remove($authErrorKey);
        } else {
            $error = null;
        }

        if (!$error instanceof AuthenticationException) {
            $error = null; // The value does not come from the security component.
        }

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $refererUri = $request->server->get('HTTP_REFERER');
            $url = $refererUri && $refererUri !== $request->getUri() ? $refererUri : $this->urlGenerator->generate('sonata_admin_dashboard');

            return new RedirectResponse($url);
        }

        $csrfToken = null;
        if ($this->csrfTokenManager) {
            $csrfToken = $this->csrfTokenManager->getToken('authenticate')->getValue();
        }

        return new Response($this->twig->render('@SonataUser/Admin/Security/login.html.twig', [
            'admin_pool' => $this->adminPool,
            'config' => $this->config,
            'base_template' => $this->templateRegistry->getTemplate('layout'),
            'csrf_token' => $csrfToken,
            'error' => $error,
            'last_username' => (null === $session) ? '' : $session->get(Security::LAST_USERNAME),
            'reset_route' => $this->urlGenerator->generate('sonata_user_admin_resetting_request'),
        ]));
    }

    public function setCsrfTokenManager(CsrfTokenManagerInterface $csrfTokenManager): void
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    private function isAuthenticated(): bool
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return false;
        }

        $user = $token->getUser();

        return $user instanceof UserInterface;
    }
}
