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

namespace Chance\RestApi\BridgeBundle\Model\Handler;

use Chance\RestApi\BridgeBundle\Model\AppEventInterface;
use Chance\RestApi\BridgeBundle\Model\Entity\BasicEntityInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

interface AbstractHandlerInterface
{
    public function error($message, $context = array());

    public function debug($message, $context = array());

    public function info($message, $context = array());

    /**
     * @param mixed $number
     * @return bool
     */
    public function isInt($number = null);

    /**
     * @return bool|mixed|UserInterface
     */
    public function getCurrentUser();

    /**
     * @return ObjectManager|EntityManagerInterface
     */
    public function getObjectManager();

    /**
     * @param BasicEntityInterface $entity
     * @param $method
     */
    public function audit(BasicEntityInterface $entity, $method);

    /**
     * @param BasicEntityInterface $entity
     */
    public function auditPersist(BasicEntityInterface $entity);

    /**
     * @param BasicEntityInterface $entity
     * @param bool $andFlush
     *
     * @return BasicEntityInterface
     */
    public function persistEntity(BasicEntityInterface $entity, $andFlush = true);

    public function getContext();

    public function fireEvent(BasicEntityInterface $entity, $eventKey, $context = array());

    /**
     * @param BasicEntityInterface $entity
     * @param null|string $eventKey
     * @param array $context
     */
    public function add(BasicEntityInterface $entity, $eventKey = null, array $context = array());

    /**
     * @param BasicEntityInterface $entity
     * @param null|string $eventKey
     * @param array $context
     */
    public function edit(BasicEntityInterface $entity, $eventKey = null, array $context = array());

    /**
     * @param BasicEntityInterface $entity
     * @param null|string $eventKey
     * @param array $context
     */
    public function remove(BasicEntityInterface $entity, $eventKey = null, array $context = array());

    /**
     * return event name or if empty, use event class to get the name
     * @param $key
     * @param AppEventInterface|null $event
     *
     * @return string
     */
    public function getEventName($key, AppEventInterface $event = null);

    /**
     * @return array
     */
    public function getEventNames();

    /**
     * @param string $key
     * @param string $value
     *
     * @throws HandlerException
     */
    public function setEventName($key, $value);

    /**
     * @param array $eventNames
     */
    public function setEventNames($eventNames = array());

    /**
     * @param $key
     *
     * @return string
     */
    public function getEventClassName($key);


    /**
     * @return array
     */
    public function getEventClassNames();

    /**
     * @param string $key
     * @param string $value
     *
     */
    public function setEventClassName($key, $value);

    /**
     * @param array $eventClassNames
     */
    public function setEventClassNames($eventClassNames = array());

    /**
     * @param $key
     * @param array $context
     *
     * @return AppEventInterface|null
     */
    public function getEvent($key, BasicEntityInterface $entity, $context = array());
}