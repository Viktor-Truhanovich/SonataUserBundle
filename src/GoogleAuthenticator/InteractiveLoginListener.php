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

namespace Sonata\UserBundle\GoogleAuthenticator;

use Sonata\UserBundle\Model\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class InteractiveLoginListener
{
    /**
     * @var Helper
     */
    protected $helper;

    public function __construct(Helper $helper)
    {
        $this->helper = $helper;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        if (!$this->helper->needToHaveGoogle2FACode($event->getRequest())) {
            return;
        }

        $token = $event->getAuthenticationToken();
        if (!$token instanceof UsernamePasswordToken) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return;
        }

        if (!$user->getTwoStepVerificationCode()) {
            return;
        }

        $event->getRequest()->getSession()->set($this->helper->getSessionKey($token), null);
    }
}
