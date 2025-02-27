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

namespace Sonata\UserBundle\Tests\Action;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\SonataConfiguration;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Sonata\UserBundle\Action\RequestAction;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

class RequestActionTest extends TestCase
{
    /**
     * @var Environment|MockObject
     */
    protected $templating;

    /**
     * @var UrlGeneratorInterface|MockObject
     */
    protected $urlGenerator;

    /**
     * @var AuthorizationCheckerInterface|MockObject
     */
    protected $authorizationChecker;

    /**
     * @var Pool
     */
    protected $pool;

    /**
     * @var TemplateRegistryInterface|MockObject
     */
    protected $templateRegistry;

    /**
     * @var SonataConfiguration
     */
    private $config;

    protected function setUp(): void
    {
        $this->templating = $this->createMock(Environment::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->templateRegistry = $this->createMock(TemplateRegistryInterface::class);

        /** @var ContainerInterface|MockObject $container */
        $container = $this->createMock(ContainerInterface::class);
        $this->pool = new Pool($container);
        $this->config = new SonataConfiguration('title', 'logo', []);
    }

    public function testAuthenticated(): void
    {
        $request = new Request();

        $this->authorizationChecker->expects(static::once())
            ->method('isGranted')
            ->willReturn(true);

        $this->urlGenerator
            ->method('generate')
            ->with('sonata_admin_dashboard')
            ->willReturn('/foo');

        $action = $this->getAction();
        $result = $action($request);

        static::assertInstanceOf(RedirectResponse::class, $result);
        static::assertSame('/foo', $result->getTargetUrl());
    }

    public function testUnauthenticated(): void
    {
        $request = new Request();

        $parameters = [
            'base_template' => 'base.html.twig',
            'admin_pool' => $this->pool,
            'config' => $this->config,
        ];

        $this->authorizationChecker->expects(static::once())
            ->method('isGranted')
            ->willReturn(false);

        $this->templating->expects(static::once())
            ->method('render')
            ->with('@SonataUser/Admin/Security/Resetting/request.html.twig', $parameters)
            ->willReturn('template content');

        $this->templateRegistry
            ->method('getTemplate')
            ->with('layout')
            ->willReturn('base.html.twig');

        $action = $this->getAction();
        $result = $action($request);

        static::assertSame('template content', $result->getContent());
    }

    private function getAction(): RequestAction
    {
        return new RequestAction(
            $this->templating,
            $this->urlGenerator,
            $this->authorizationChecker,
            $this->pool,
            $this->config,
            $this->templateRegistry
        );
    }
}
