<?php
/**
 * @package
 * @subpackage
 * @author      Chance Garcia <chance@garcia.codes>
 * @copyright   (C)Copyright 2013-2017 Chance Garcia, chancegarcia.com
 *
 *    The MIT License (MIT)
 *
 * Copyright (c) 2013-2017 Chance Garcia
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

namespace Chance\RestApi\BridgeBundle\Handler;

use Chance\RestApi\BridgeBundle\Exception\Handler\HandlerException;
use Chance\RestApi\BridgeBundle\Model\Entity\AppUserInterface;
use Chance\RestApi\BridgeBundle\Model\Handler\AbstractHandlerInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractHandler implements AbstractHandlerInterface, ContainerAwareInterface
{
    /**
     * @var ObjectManager|EntityManagerInterface
     */
    protected $om;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var TokenStorage|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var UrlGeneratorInterface
     */
    protected $router;

    /**
     * @var TwigEngine|EngineInterface
     */
    protected $templating;

    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param UrlGeneratorInterface $router
     */
    public function setRouter(UrlGeneratorInterface $router = null)
    {
        $this->router = $router;
    }

    /**
     * @param TwigEngine|EngineInterface $templating
     */
    public function setTemplating(EngineInterface $templating = null)
    {
        $this->templating = $templating;
    }

    /**
     * @param \Swift_Mailer $mailer
     */
    public function setMailer(\Swift_Mailer $mailer = null)
    {
        $this->mailer = $mailer;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher = null)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ObjectManager|EntityManagerInterface $om
     */
    public function setObjectManager(ObjectManager $om = null)
    {
        $this->om = $om;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @param TokenStorage $tokenStorage
     */
    public function setTokenStorage(TokenStorage $tokenStorage = null)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param ValidatorInterface $validator
     */
    public function setValidator(ValidatorInterface $validator = null)
    {
        $this->validator = $validator;
    }

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker = null)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param AppUserInterface $user
     * @return bool
     */
    public function validateUser(AppUserInterface $user)
    {
        return ($user instanceof AppUserInterface);
    }

    /**
     * @return AppUserInterface|bool
     */
    public function getCurrentUser()
    {
        $user = $this->tokenStorage->getToken()->getUser();

        // @todo validate user abstract method?
        if (!$this->validateUser($user)) {
            $user = false;
        }

        return $user;
    }

    public function isInt($number = null)
    {
        if (!is_numeric($number)) {
            return false;
        } else {
            if (is_string($number) && false !== strpos($number, ".")) {
                return false;
            } else {
                if (!is_int($number) && !is_string($number)) {
                    return false;
                } else {
                    if (is_string($number) && !is_int((int)$number)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function error($message, $context = array())
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->error($message, $context);
        }

        return $this;
    }

    public function debug($message, $context = array())
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->debug($message, $context);
        }

        return $this;
    }

    public function info($message, $context = array())
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info($message, $context);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getCurrentUserRoles()
    {
        $user = $this->getCurrentUser();
        $userRoles = $user->getRoles();
        $oUserRoles = array();

        foreach ($userRoles as $userRole) {
            $oUserRoles[] = new Role($userRole);
        }

        $assignedRoles = $this->getAssignedRoles($oUserRoles);

        $merged = array_merge($userRoles, $assignedRoles);

        $final = array_unique($merged);

        return $final;
    }

    /**
     * @param array|RoleInterface[] $assignedRoles array of RoleInterface
     * @return array
     * @throws HandlerException
     */
    public function getAssignedRoles(array $assignedRoles = null)
    {
        if (!$this->container instanceof ContainerInterface) {
            throw new HandlerException("container not set", HandlerException::MISSING_DEPENDENCY);
        }

        if (!is_array($assignedRoles)) {
            $tokenStorage = $this->container->get('security.token_storage');
            $assignedRoles = $tokenStorage->getToken()->getRoles();
        }

        $roleHierarchy = $this->container->get('security.role_hierarchy');
        $allRoles = $roleHierarchy->getReachableRoles($assignedRoles);
        $userRoles = array();
        foreach ($allRoles as $role) {
            /**
             * @var Role $role
             */
            if (!in_array($role, $assignedRoles, true)) {
                $userRoles[] = $role->getRole();
            }
        }

        return $userRoles;
    }
}