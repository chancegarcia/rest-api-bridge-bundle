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

namespace Chance\RestApi\BridgeBundle\Event;

use Chance\RestApi\BridgeBundle\Exception\AppEventException;
use Chance\RestApi\BridgeBundle\Model\AppEventInterface;
use Chance\RestApi\BridgeBundle\Model\Entity\BasicEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;

abstract class AbstractAppEvent extends Event implements AppEventInterface
{
    const NAME = self::class;

    protected $entity;

    protected $container;

    protected $context;

    public function __construct(BasicEntityInterface $entity = null, ContainerInterface $container = null, array $context = null)
    {
        $this->entity = $entity;
        $this->container = $container;
        $this->context = $context;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return BasicEntityInterface
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param BasicEntityInterface $entity
     */
    public function setEntity(BasicEntityInterface $entity = null)
    {
        $this->entity = $entity;
    }

    /**
     * @return array|null
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param array $context
     */
    public function setContext(array $context = null)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     * @throws AppEventException
     */
    public function getContextItem($key)
    {
        if (!is_string($key)) {
            $keyType = gettype($key);
            throw new AppEventException("key must be a string. got type (" . $keyType . ")",
                AppEventException::INVALID_FORMAT);
        }

        if (is_array($this->context) && array_key_exists($key, $this->context)) {
            return $this->context[$key];
        } else {
            throw new AppEventException("unable to find key (" . $key . ")", AppEventException::MISSING_KEY);
        }
    }

    public function getEventName()
    {
        return static::NAME;
    }
}