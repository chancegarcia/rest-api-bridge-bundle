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

use Chance\RestApi\BridgeBundle\Exception\Handler\HandlerException;
use Chance\RestApi\BridgeBundle\Model\AppEventInterface;
use Chance\RestApi\BridgeBundle\Model\Entity\BasicEntityInterface;
use Doctrine\Common\Persistence\ObjectRepository;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

interface RestHandlerInterface extends AbstractHandlerInterface
{
    /**
     * @param FormFactoryInterface $formFactory
     */
    public function setFormFactory(FormFactoryInterface $formFactory = null);

    /**
     * @return boolean
     */
    public function isUserAware();

    /**
     * @param boolean $userAware
     */
    public function setUserAware($userAware = null);

    /**
     * @return ObjectRepository
     */
    public function getRepository();

    /**
     * @param ObjectRepository $repository
     */
    public function setRepository(ObjectRepository $repository = null);

    /**
     * @param AbstractType $formType
     */
    public function setFormType(AbstractType $formType = null);

    /**
     * @param null|string $formTypeClass
     */
    public function setFormTypeClass($formTypeClass = null);

    /**
     * @return BasicEntityInterface
     */
    public function getEntityClass();

    public function getFormTypeClass();

    /**
     * @param BasicEntityInterface|string $entityClass
     */
    public function setEntityClass($entityClass = null);

    /**
     * create new entity instance without any constructor injection. override if you need to pass data
     *
     * @param array $params
     *
     * @return BasicEntityInterface
     */
    public function createEntity($params = array());

    /**
     *
     * @param mixed $id
     *
     * @return BasicEntityInterface
     */
    public function get($id);

    /**
     * @param $id
     * @return BasicEntityInterface
     */
    public function getOr404($id);

    /**
     * @param Request $request
     * @param ParamFetcher $paramFetcher
     *
     * @return array|null
     */
    public function parseCriteria(Request $request, ParamFetcher $paramFetcher);

    public function parseLimit(Request $request, ParamFetcher $paramFetcher);

    public function parseOffset(Request $request, ParamFetcher $paramFetcher);

    public function parseOrderBy(Request $request, ParamFetcher $paramFetcher);

    /**
     * @param Request $request
     * @param ParamFetcher $paramFetcher
     *
     * @return array
     */
    public function handleGetAllRequest(Request $request, ParamFetcher $paramFetcher);

    /**
     *
     * @param array $criteria
     * @param int $limit the limit of the result
     * @param int $offset starting from the offset
     * @param null|array $orderBy
     *
     * @return array
     */
    public function all($criteria = array(), $limit = null, $offset = null, $orderBy = null);

    /**
     * @param Request|null $request
     * @param ParamFetcher|null $paramFetcher
     *
     * @return BasicEntityInterface|mixed
     */
    public function handlePostRequest(Request $request, ParamFetcher $paramFetcher);

    /**
     * @param BasicEntityInterface $entity
     * @param Request|null $request
     * @param ParamFetcher|null $paramFetcher
     *
     * @return BasicEntityInterface|mixed
     */
    public function handlePutRequest(BasicEntityInterface $entity, Request $request, ParamFetcher $paramFetcher);

    /**
     * @param BasicEntityInterface $entity
     * @param Request|null $request
     * @param ParamFetcher|null $paramFetcher
     *
     * @return BasicEntityInterface|mixed
     */
    public function handlePatchRequest(BasicEntityInterface $entity, Request $request, ParamFetcher $paramFetcher);

    /**
     * @param BasicEntityInterface $entity
     * @param Request|null $request
     * @param ParamFetcher|null $paramFetcher
     */
    public function handleDeleteRequest(BasicEntityInterface $entity, Request $request, ParamFetcher $paramFetcher);

    /**
     * @param BasicEntityInterface $entity
     * @param Request|null $request
     * @param string $method
     *
     * @return bool
     */
    public function validateFormData(BasicEntityInterface $entity, Request $request = null, $method = "PUT");

    /**
     * @param BasicEntityInterface $entity
     * @param Request|null $request
     * @param string $method
     *
     * @return FormInterface
     */
    public function getFormFromRequestData(BasicEntityInterface $entity, Request $request = null, $method = "PUT");

    /**
     * @param FormInterface $form
     *
     */
    public function handleInvalidForm($form);

    /**
     * @param BasicEntityInterface $entity
     * @param Request|null $request
     * @param string $method
     *
     * @return BasicEntityInterface|null
     */
    public function processFormRequest(BasicEntityInterface $entity, Request $request = null, $method = "PUT");

    public function parseFormDataFromRequest(Request $request, FormInterface $form);

    /**
     * @param BasicEntityInterface $entity
     *
     * @return FormInterface
     */
    public function createRemoveForm(BasicEntityInterface $entity);

    /**
     * @param BasicEntityInterface|null $entity
     * @param array $options
     *
     * @return FormInterface
     */
    public function getForm(BasicEntityInterface $entity = null, array $options = array());

    /**
     * @param Request $request
     * @param $form
     *
     * @return array|mixed|null
     */
    public function getParameters(Request $request, $form);

    /**
     * {@inheritdoc}
     */
    public function getEntityProperties($reflectionFilters = null);

    /**
     * {@inheritdoc}
     */
    public function getEntityPropertyNames(array $ignoreNames = array());

    /**
     * @param int $id
     *
     * @return bool
     * @throws HandlerException
     */
    public function validateId($id = null);

    /**
     * @return array
     */
    public function getEntityRoutes();

    /**
     * @param string $key
     * @param string $value
     *
     */
    public function setEntityRoute($key = 'get', $value);

    /**
     * @param $key
     *
     * @return string
     */
    public function getEntityRoute($key);

    /**
     * @param array $entityRoutes
     */
    public function setEntityRoutes(array $entityRoutes = array());

    /**
     * @return string|null
     */
    public function getFormTemplateVarName();

    /**
     * @param string|null $formTemplateVarName
     */
    public function setFormTemplateVarName($formTemplateVarName = null);

    /**
     * @return mixed
     */
    public function getRoutePrefix();

    /**
     * @param mixed $routePrefix
     */
    public function setRoutePrefix($routePrefix = null);
}