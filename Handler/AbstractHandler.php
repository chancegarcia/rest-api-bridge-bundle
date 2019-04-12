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
use Chance\RestApi\BridgeBundle\Exception\RestApiBridgeBubbleUpException;
use Chance\RestApi\BridgeBundle\Model\AppEventInterface;
use Chance\RestApi\BridgeBundle\Model\Entity\AppUserInterface;
use Chance\RestApi\BridgeBundle\Model\Entity\BasicEntityInterface;
use Chance\RestApi\BridgeBundle\Model\Handler\AbstractHandlerInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractHandler implements AbstractHandlerInterface, ContainerAwareInterface
{
    const EVENT_INTERFACE = 'Chance\RestApi\BridgeBundle\Model\AppEventInterface';

    protected $eventNames = array(
        'get' => null,
        'post' => null,
        'put' => null,
        'patch' => null,
        'delete' => null,
        'add' => null,
        'edit' => null,
        'remove' => null,
    );

    protected $eventClassNames = array(
        'get' => null,
        'post' => null,
        'put' => null,
        'patch' => null,
        'delete' => null,
        'add' => null,
        'edit' => null,
        'remove' => null,
    );

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
     * @return ObjectManager|EntityManagerInterface
     */
    public function getObjectManager()
    {
        return $this->om;
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
        $token = $this->tokenStorage->getToken();
        if (!$token instanceof TokenInterface) {
            return false;
        }

        $user = $token->getUser();

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

    /**
     * @param BasicEntityInterface $entity
     * @param $method
     */
    abstract public function audit(BasicEntityInterface $entity, $method);

    /**
     * @param BasicEntityInterface $entity
     */
    abstract public function auditPersist(BasicEntityInterface $entity);

    /**
     * @param BasicEntityInterface $entity
     * @param bool $andFlush
     *
     * @return BasicEntityInterface
     * @throws NotNullConstraintViolationException
     * @throws \Exception
     */
    public function persistEntity(BasicEntityInterface $entity, $andFlush = true)
    {
        try {
            $this->om->persist($entity);
            $this->auditPersist($entity);
            if ($andFlush) {
                $this->om->flush();
            }
        } catch (NotNullConstraintViolationException $nncve) {
            $this->error($nncve->getMessage(), array(__METHOD__, __LINE__));
            $this->error($nncve->getTraceAsString(), array(__METHOD__, __LINE__));
            throw $nncve;
        } catch (RestApiBridgeBubbleUpException $rabbe) {
                throw $rabbe;
        } catch (\Exception $e) {
            $this->error(get_class($e), array(__METHOD__, __LINE__));
            $this->error($e->getMessage(), array(__METHOD__, __LINE__));
            $this->error($e->getTraceAsString(), array(__METHOD__, __LINE__));
            // re-throw since controllers should handle exceptions for feedback
            throw $e;
        }

        return $entity;
    }

    public function getContext()
    {
        return array();
    }

    public function fireEvent(BasicEntityInterface $entity, $eventKey, $context = array())
    {
        $normalizedEventKey = strtolower($eventKey);
        $event = $this->getEvent($normalizedEventKey, $entity, $context);
        if ($event instanceof AppEventInterface) {
            $eventName = $this->getEventName($normalizedEventKey, $event);
            $this->eventDispatcher->dispatch($eventName, $event);
        }
    }

    /**
     * @param BasicEntityInterface $entity
     * @param null|string $eventKey
     * @param array $context
     */
    public function add(BasicEntityInterface $entity, $eventKey = null, array $context = array())
    {
        $this->persistEntity($entity);
        if (!is_string($eventKey)) {
            $eventKey = 'add';
        }
        $this->fireEvent($entity, $eventKey, $context);
    }

    /**
     * @param BasicEntityInterface $entity
     * @param null|string $eventKey
     * @param array $context
     */
    public function edit(BasicEntityInterface $entity, $eventKey = null, array $context = array())
    {
        $this->persistEntity($entity);
        if (!is_string($eventKey)) {
            $eventKey = 'edit';
        }
        $this->fireEvent($entity, $eventKey, $context);
    }

    /**
     * @param BasicEntityInterface $entity
     * @param null|string $eventKey
     * @param array $context
     */
    public function remove(BasicEntityInterface $entity, $eventKey = null, array $context = array())
    {
        $this->om->remove($entity);
        $this->om->flush();

        if (!is_string($eventKey)) {
            $eventKey = 'remove';
        }
        $this->fireEvent($entity, $eventKey, $context);
    }

    /**
     * @param $key
     * @param AppEventInterface|null $event
     *
     * @return string
     * @throws HandlerException
     */
    public function getEventName($key, AppEventInterface $event = null)
    {
        $eventName = null;
        $setEventName = false; // flag to set name from derived value
        // look in given currently defined event names
        if (array_key_exists($key, $this->eventNames)) {
            $eventName = $this->eventNames[$key];
        }

        // if the name is not a string and we have an event interface. try to discern the name from the class
        if (!is_string($eventName) && $event instanceof AppEventInterface) {
            $setEventName = true;
            // try get event name from event object
            /**
             * declaring to read NAME constant in IDE
             *
             * @var AbstractAppEvent $event
             */
            $eventName = $event::NAME;
        }

        // add the derived name to the current state
        if (true === $setEventName) {
            $this->setEventName($key, $eventName);
        }

        return $eventName;
    }

    /**
     * @return array
     */
    public function getEventNames()
    {
        return $this->eventNames;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @throws HandlerException
     */
    public function setEventName($key, $value)
    {
        $this->eventNames[$key] = $value;
    }

    /**
     * @param array $eventNames
     */
    public function setEventNames($eventNames = array())
    {
        foreach ($eventNames as $k => $v) {
            $this->setEventName($k, $v);
        }
    }

    /**
     * @param $key
     *
     * @return string
     * @throws HandlerException
     */
    public function getEventClassName($key)
    {
        if (array_key_exists($key, $this->eventClassNames)) {
            return $this->eventClassNames[$key];
        }

        throw new HandlerException(
            'unable to find event class (' . $key . ')',
            HandlerException::INVALID_KEY
        );
    }

    /**
     * @return array
     */
    public function getEventClassNames()
    {
        return $this->eventClassNames;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @throws HandlerException
     */
    public function setEventClassName($key, $value)
    {
        if (class_exists($value)) {
            if (is_subclass_of($value, static::EVENT_INTERFACE)) {
                $this->eventClassNames[$key] = $value;
            } else {
                throw new HandlerException(
                    $value . ' is not an instance of ' . static::EVENT_INTERFACE,
                    HandlerException::INVALID_TYPE
                );
            }
        } else {
            throw new HandlerException($value . ' is not a valid class', HandlerException::INVALID_TYPE);
        }
    }

    /**
     * @param array $eventClassNames
     */
    public function setEventClassNames($eventClassNames = array())
    {
        foreach ($eventClassNames as $k => $v) {
            $this->setEventClassName($k, $v);
        }
    }

    /**
     * @param $key
     * @param BasicEntityInterface $entity
     * @param array $context
     * @return AppEventInterface|null
     */
    public function getEvent($key, BasicEntityInterface $entity, $context = array())
    {
        // todo factory the event object? need event factory property setter then, if so.
        $event = null;
        try {
            $eventClassName = $this->getEventClassName($key);
            if (class_exists($eventClassName)) {
                /**
                 * @var AppEventInterface $event
                 */
                $event = new $eventClassName;
                $event->setContext($context);
                $event->setContainer($this->container);
                $event->setEntity($entity);
            }
        } catch (HandlerException $e) {
            // probably should do something like log but catching to avoid invalid key (blank) exception
        } catch (RestApiBridgeBubbleUpException $rabbe) {
            throw $rabbe;
        }

        return $event;
    }
}