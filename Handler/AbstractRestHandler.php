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

use Chance\RestApi\BridgeBundle\Event\AbstractAppEvent;
use Chance\RestApi\BridgeBundle\Exception\Handler\HandlerException;
use Chance\RestApi\BridgeBundle\Exception\InvalidFormException;
use Chance\RestApi\BridgeBundle\Exception\UuidException;
use Chance\RestApi\BridgeBundle\Model\AppEventInterface;
use Chance\RestApi\BridgeBundle\Model\Entity\BasicEntityInterface;
use Chance\RestApi\BridgeBundle\Model\Handler\RestHandlerInterface;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Validator\Constraints\Regex;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// todo move add/edit/delete up to AbstractHandler and have default event fire names as add/edit/delete; have this send the rest name keys to override those other eventt names
abstract class AbstractRestHandler extends AbstractHandler implements RestHandlerInterface
{
    const BASIC_ENTITY_INTERFACE = 'Chance\RestApi\BridgeBundle\Model\Entity\BasicEntityInterface';

    const USER_AWARE_ID_PROPERTY = 'userId';

    protected $userAware = false;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var ObjectRepository
     */
    protected $repository;

    /**
     * @var AbstractType
     */
    protected $formType;

    /**
     * fully qualified class name of the form type class associated with the entity class to fallback to if formType is
     * not set
     *
     * @var null|string
     */
    protected $formTypeClass = null;

    /**
     * @var BasicEntityInterface
     */
    protected $entityClass;

    /**
     * @var array used in hack solution for converting boolean properties correctly
     * @todo do this properly with form transformer. this is a hack
     */
    protected $booleanProperties = array();

    protected $formTemplateVarName;

    protected $entityRoutes = array(
        'get' => null,
        'post' => null,
        'put' => null,
        'patch' => null,
        'delete' => null,
    );

    protected $routePrefix;

    protected $newEntityFormTemplate = 'ChanceRestApiBridgeBundle:Generic:entityFullForm.html.twig';
    protected $editEntityFormTemplate = 'ChanceRestApiBridgeBundle:Generic:entityFullForm.html.twig';
    protected $deleteEntityFormTemplate = 'ChanceResumeResumeBundle:Generic:deleteEntity.html.twig';

    /**
     * @param FormFactoryInterface $formFactory
     */
    public function setFormFactory(FormFactoryInterface $formFactory = null)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * @return boolean
     */
    public function isUserAware()
    {
        return $this->userAware;
    }

    /**
     * @param boolean $userAware
     */
    public function setUserAware($userAware = null)
    {
        $this->userAware = filter_var($userAware, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return ObjectRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param ObjectRepository $repository
     */
    public function setRepository(ObjectRepository $repository = null)
    {
        $this->repository = $repository;
    }

    /**
     * @param AbstractType $formType
     */
    public function setFormType(AbstractType $formType = null)
    {
        $this->formType = $formType;
    }

    /**
     * @param null|string $formTypeClass
     */
    public function setFormTypeClass($formTypeClass = null)
    {
        $filteredFormTypeClass = null;
        if (is_string($formTypeClass)) {
            $filteredFormTypeClass = trim($formTypeClass);
        }
        $this->formTypeClass = $filteredFormTypeClass;
    }

    /**
     * @return BasicEntityInterface
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    public function getFormTypeClass()
    {
        if (!is_string($this->formTypeClass)) {
            throw new HandlerException("form type not set", HandlerException::MISSING_FORM_TYPE);
        }

        return $this->formTypeClass;
    }

    /**
     * @param BasicEntityInterface|string $entityClass
     *
     * @throws UuidException
     */
    public function setEntityClass($entityClass = null)
    {
        // trim whitespace or is_subclass_of will give a false negative
        if (is_string($entityClass)) {
            $entityClass = trim($entityClass);
        }

        if (!is_string($entityClass) && $entityClass instanceof BasicEntityInterface) {
            $objectName = get_class($entityClass);
        } else {
            if (is_string($entityClass) && is_subclass_of($entityClass, static::BASIC_ENTITY_INTERFACE)) {
                $objectName = $entityClass;
            } else {
                throw new UuidException("unable to set entity class properly");
            }
        }

        // only deal with name so we don't run into class constructor errors
        $this->entityClass = $objectName;
        $this->repository = $this->om->getRepository($objectName);

        if (!is_string($this->formTemplateVarName)) {
            $entityReflect = new \ReflectionClass($this->entityClass);
            $this->formTemplateVarName = strtolower($entityReflect->getShortName());
        }
    }

    /**
     * create new entity instance without any constructor injection. override if you need to pass data
     *
     * @todo use an entity factory?
     *
     * @param array $params
     *
     * @return BasicEntityInterface
     * @throws HandlerException
     */
    public function createEntity($params = array())
    {
        if (is_string($this->entityClass)) {
            return new $this->entityClass(...$params);
        } else {
            throw new HandlerException("Entity Class has not been set.");
        }
    }

    /**
     *
     * @param mixed $id
     *
     * @return BasicEntityInterface|object
     */
    public function get($id)
    {
        return $this->repository->find($id);
    }

    public function getOr404($id)
    {
        if (!$entity = $this->get($id)) {
            throw new NotFoundHttpException(sprintf('The resource \'%s\' was not found.', $id));
        }

        return $entity;
    }

    /**
     * @param Request $request
     * @param ParamFetcher $paramFetcher
     *
     * @return array|null
     */
    public function parseCriteria(Request $request, ParamFetcher $paramFetcher)
    {
        return null;
    }

    public function parseLimit(Request $request, ParamFetcher $paramFetcher)
    {
        try {
            $limit = $paramFetcher->get('limit');
            $limit = (null == $limit) ? null : $limit;
        } catch (\InvalidArgumentException $iae) {
            $limit = null;
        } catch (\Exception $e) {
            // need to make log methods
            $limit = null;
        }

        return $limit;
    }

    public function parseOffset(Request $request, ParamFetcher $paramFetcher)
    {
        try {
            $offset = $paramFetcher->get('offset');
            $offset = (null == $offset) ? null : $offset;
        } catch (\InvalidArgumentException $iae) {
            $offset = null;
        } catch (\Exception $e) {
            // need to make log methods
            $offset = null;
        }

        return $offset;
    }

    public function parseOrderBy(Request $request, ParamFetcher $paramFetcher)
    {
        // maybe have orderBy param as a json encoded param?
        return null;
    }

    /**
     * @param Request $request
     * @param ParamFetcher $paramFetcher
     *
     * @return array
     */
    public function handleGetAllRequest(Request $request, ParamFetcher $paramFetcher)
    {
        $criteria = $this->parseCriteria($request, $paramFetcher);
        $limit = $this->parseLimit($request, $paramFetcher);
        $offset = $this->parseOffset($request, $paramFetcher);
        $orderBy = $this->parseOrderBy($request, $paramFetcher);

        $data = $this->all($criteria, $limit, $offset, $orderBy);

        // maybe add some sort of filter call to allow custom filtering (such as using array_values() to ensure a JSON array)
        return $data;
    }

    /**
     *
     * @param array $criteria
     * @param int $limit the limit of the result
     * @param int $offset starting from the offset
     * @param null|array $orderBy
     *
     * @return array
     */
    public function all($criteria = array(), $limit = null, $offset = null, $orderBy = null)
    {
        $criteria = (array)$criteria;

        if ($this->userAware) {
            $criteria[static::USER_AWARE_ID_PROPERTY] = $this->getCurrentUser()->getId();
        }

        return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
    }

    // todo add searchable list using orX
    // https://stackoverflow.com/questions/9815047/chaining-orx-in-doctrine2-query-builder
    /*
     * $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->orX(
            Criteria::expr()->contains('name', 'test'),
            Criteria::expr()->contains('originalText', 'test'),
            Criteria::expr()->contains('plainText', 'test'),
            Criteria::expr()->contains('fileSize', 'test'),
            Criteria::expr()->contains('type', 'test'),
            Criteria::expr()->contains('createdOn', 'test'),
            Criteria::expr()->contains('createdBy', 'test'),
            Criteria::expr()->contains('modifiedOn', 'test'),
            Criteria::expr()->contains('modifiedBy', 'test')
            )
        );
        $criteriaResults = $repo->matching($criteria);
        $total = $criteriaResults->count()
        $entities = $criteriaResults->toArray()
     */

    /**
     * @param Request|null $request
     * @param ParamFetcher|null $paramFetcher
     *
     * @return BasicEntityInterface|mixed
     */
    public function handlePostRequest(Request $request, ParamFetcher $paramFetcher)
    {
        $entity = $this->createEntity();

        return $this->processFormRequest($entity, $request, 'POST');
    }

    /**
     * @param BasicEntityInterface $entity
     * @param Request|null $request
     * @param ParamFetcher|null $paramFetcher
     *
     * @return BasicEntityInterface|mixed
     */
    public function handlePutRequest(BasicEntityInterface $entity, Request $request, ParamFetcher $paramFetcher)
    {
        return $this->processFormRequest($entity, $request, 'PUT');
    }

    /**
     * @param BasicEntityInterface $entity
     * @param Request|null $request
     * @param ParamFetcher|null $paramFetcher
     *
     * @return BasicEntityInterface|mixed
     */
    public function handlePatchRequest(BasicEntityInterface $entity, Request $request, ParamFetcher $paramFetcher)
    {
        return $this->processFormRequest($entity, $request, 'PATCH');
    }

    /**
     * @param BasicEntityInterface $entity
     * @param Request|null $request
     * @param ParamFetcher|null $paramFetcher
     */
    public function handleDeleteRequest(BasicEntityInterface $entity, Request $request, ParamFetcher $paramFetcher)
    {
        return $this->remove($entity);
    }

    /**
     * @param BasicEntityInterface $entity
     * @param Request|null $request
     * @param string $method
     *
     * @return bool
     */
    public function validateFormData(BasicEntityInterface $entity, Request $request = null, $method = "PUT")
    {
        $form = $this->getFormFromRequestData($entity, $request, $method);

        $isValid = $form->isValid();

        return $isValid;
    }

    /**
     * @param BasicEntityInterface $entity
     * @param Request|null $request
     * @param string $method
     *
     * @return FormInterface
     */
    public function getFormFromRequestData(BasicEntityInterface $entity, Request $request = null, $method = "PUT")
    {
        $formOptions = array('method' => $method);
        $form = $this->getForm($entity, $formOptions);

        // @todo make this event driven
        $parameters = $this->getParameters($request, $form);

        // if we are patching or "deleting" (archiving...true deletion is covered by the {@link AbstractRestHandler::delete()})
        // method. we archive by saying this is a patch
        $form->submit($parameters, ('PATCH' !== $method));

        return $form;
    }

    /**
     * @param FormInterface $form
     *
     * @throws InvalidFormException
     */
    public function handleInvalidForm($form)
    {
        // this is where entity constraint violations happen. we could probably clean this up more in the future
        if (false === strpos((string)$form->getErrors(true), 'already in use with')) {
            $exception = new InvalidFormException('Invalid submitted data', $form);
        } else {
            $exception = new InvalidFormException('Invalid submitted data', $form, Response::HTTP_CONFLICT);
        }

        throw $exception;
    }

    /**
     * @param BasicEntityInterface $entity
     * @param Request|null $request
     * @param string $method
     *
     * @return BasicEntityInterface|null
     * @throws NotNullConstraintViolationException
     * @throws \Exception
     */
    public function processFormRequest(BasicEntityInterface $entity, Request $request = null, $method = "PUT")
    {
        $form = $this->getFormFromRequestData($entity, $request, $method);

        if ($this->validateFormData($entity, $request, $method)) {
            $entity = $form->getData();
            $this->audit($entity, $method);

            switch (strtolower($method)) {
                case "put":
                    if (is_int(filter_var($entity->getId(), FILTER_VALIDATE_INT))) {
                        $this->edit($entity, $method);
                    } else {
                        $this->add($entity, $method);
                    }
                    break;
                case  "patch":
                    $this->edit($entity, $method);
                    break;
                case "delete":
                    //$this->remove($entity);
                    break;
                case "post":
                    // no break
                default:
                    $this->add($entity, $method);
                    break;
            }

            return $entity;
        }

        $this->handleInvalidForm($form);

        return null;
    }

    public function parseFormDataFromRequest(Request $request, FormInterface $form)
    {
        $name = $form->getName();
        //$a = $form->all();
        //foreach ($a as $child) {
        //    $c = $child->getName();
        //}
        $params = null;
        $files = null;

        if ($request->request->has($name) || $request->files->has($name)) {
            $default = $form->getConfig()->getCompound() ? array() : null;
            $params = $request->request->get($name, $default);
            $files = $request->files->get($name, $default);
        }

        if (is_array($params) && is_array($files)) {
            $data = array_replace_recursive($params, $files);
        } else {
            $data = $params ?: $files;
        }

        if (is_array($data)) {
            if (array_key_exists('submit', $data)) {
                unset($data['submit']);
            }

            if (array_key_exists('_format', $data)) {
                unset($data['_format']);
            }
        }

        return $data;
    }

    /**
     * @param BasicEntityInterface $entity
     *
     * @return FormInterface
     */
    public function createRemoveForm(BasicEntityInterface $entity)
    {

        $deleteEntityRoute = $this->getEntityRoute('delete');
        $uri = $this->router->generate($deleteEntityRoute, array('id' => $entity->getId()));
        $form = $this->formFactory->createBuilder()
            ->setAction($uri)
            ->setMethod('DELETE')
            ->getForm();

        return $form;
    }

    /**
     * @param BasicEntityInterface|null $entity
     * @param array $options
     *
     * @return FormInterface
     */
    public function getForm(BasicEntityInterface $entity = null, array $options = array())
    {
        return $this->formFactory->create($this->getFormTypeClass(), $entity, $options);
    }

    /**
     * @param Request $request
     * @param $form
     *
     * @return array|mixed|null
     */
    public function getParameters(Request $request, $form)
    {
        $parameters = $this->parseFormDataFromRequest($request, $form);

        foreach ($parameters as $key => $value) {
            if (in_array($key, $this->booleanProperties)) {
                // re-nice it to actual boolean since the form transformer is being weird
                // @todo do this properly with form transformer. this is a hack
                $parameters[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityProperties($reflectionFilters = null)
    {
        $entity = $this->createEntity();
        $reflection = new \ReflectionClass($entity);
        // can't pass null into get properties
        $properties =
            (null === $reflectionFilters) ? $reflection->getProperties()
                : $reflection->getProperties($reflectionFilters);

        return $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityPropertyNames(array $ignoreNames = array())
    {
        $entityProperties = $this->getEntityProperties();
        $filtered = array_filter(
            $entityProperties,
            function ($val) use ($ignoreNames) {
                /**
                 * @var \ReflectionProperty $val
                 */
                $name = $val->getName();

                return !in_array($name, $ignoreNames);
            }
        );

        $names = array_map(
            function ($val) {
                /**
                 * @var \ReflectionProperty $val
                 */
                $name = $val->getName();

                return $name;
            },
            $filtered
        );

        return $names;
    }

    /**
     * @param int $id
     *
     * @return bool
     * @throws HandlerException
     */
    public function validateId($id = null)
    {
        $validator = new Regex(array('pattern' => '/\d+/', 'message' => ' id must be a valid integer value'));
        $errors = $this->validator->validate($id, $validator);

        if (0 !== count($errors)) {
            throw new HandlerException((string)$errors, HandlerException::INVALID_ID);
        }

        return true;
    }

    /**
     * @return array
     */
    public function getEntityRoutes()
    {
        return $this->entityRoutes;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @throws HandlerException
     */
    public function setEntityRoute($key = 'get', $value)
    {
        if (!array_key_exists($key, $this->entityRoutes)) {
            throw new HandlerException(
                'unable to find entity route (' . $key . ')',
                HandlerException::INVALID_ENTITY_ROUTE_KEY
            );
        }

        $this->entityRoutes[$key] = $value;
    }

    /**
     * @param $key
     *
     * @return string
     * @throws HandlerException
     */
    public function getEntityRoute($key)
    {
        if (array_key_exists($key, $this->entityRoutes)) {
            $route = $this->entityRoutes[$key];
            if (empty($route)) {
                // intelligently (?) set route based on key request and form template var name
                $route = $this->routePrefix . $key . "_" . $this->formTemplateVarName;
                $this->setEntityRoute($key, $route);
            }

            return $route;
        }

        throw new HandlerException(
            'unable to find entity route (' . $key . ')',
            HandlerException::INVALID_ENTITY_ROUTE_KEY
        );
    }

    /**
     * @param array $entityRoutes
     */
    public function setEntityRoutes(array $entityRoutes = array())
    {
        foreach ($entityRoutes as $key => $val) {
            $this->setEntityRoute($key, $val);
        }
    }

    /**
     * @return string|null
     */
    public function getFormTemplateVarName()
    {
        return $this->formTemplateVarName;
    }

    /**
     * @param string|null $formTemplateVarName
     */
    public function setFormTemplateVarName($formTemplateVarName = null)
    {
        if (is_string($formTemplateVarName)) {
            $this->formTemplateVarName = $formTemplateVarName;
        }
    }

    /**
     * @return mixed
     */
    public function getRoutePrefix()
    {
        return $this->routePrefix;
    }

    /**
     * @param mixed $routePrefix
     */
    public function setRoutePrefix($routePrefix = null)
    {
        if (is_string($routePrefix)) {
            $this->routePrefix = $routePrefix;
        }
    }

}