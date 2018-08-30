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

namespace Chance\RestApi\BridgeBundle\Controller\RestApi;

use Chance\RestApi\BridgeBundle\Exception\Handler\HandlerException;
use Chance\RestApi\BridgeBundle\Handler\AbstractRestHandler;
use Chance\RestApi\BridgeBundle\Model\Entity\BasicEntityInterface;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class AbstractRestController
 * @package Chance\RestApi\BridgeBundle\Controller
 *
 * @Annotations\RouteResource("Entity")
 *
 * @todo factor this out as a rest/crud so we can have make some generic view functions for re-use. and have something to exted that won't generate the of the doAction methods in it.
 */
abstract class AbstractRestViewController extends FOSRestController
{
    /**
     * @var Context
     */
    private $context;

    protected $newFormTemplate = 'ChanceRestApiBridgeBundle:Generic:entityFullForm.html.twig';

    protected $editFormTemplate = 'ChanceRestApiBridgeBundle:Generic:entityFullForm.html.twig';

    protected $deleteFormTemplate = 'ChanceRestApiBridgeBundle:Generic:deleteEntity.html.twig';

    protected $apiFormTemplateName;

    protected $entityRoutes = array(
        'get' => null,
        'post' => null,
        'put' => null,
        'patch' => null,
        'delete' => null,
    );

    protected $routePrefix;

    protected $routePrefixParameterName = 'chance.rest_api_bridge.route_prefix';

    public function __construct()
    {
        $this->context = new Context();
        $this->context->enableMaxDepth();
    }

    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);

        // this is obviously a hack. there's probably a way to do this in the bundle build but I don't know how/where yet
        // can not do in construct because the handler service hasn't been defined/set yet
        $handler = $this->getHandler();
        $reflect = new \ReflectionClass($handler->getEntityClass());

        $shortName = $reflect->getShortName();
        $this->apiFormTemplateName = strtolower($shortName);
        if (!is_string($this->routePrefix) && $container instanceof ContainerInterface) {
            $routePrefix = $container->getParameter($this->routePrefixParameterName);
            $this->routePrefix = $routePrefix;
        }

        foreach ($this->entityRoutes as $httpMethod => $route) {
            $this->entityRoutes[$httpMethod] = $this->routePrefix . $httpMethod . "_" . $this->apiFormTemplateName;
        }

        $this->getHandler()->setEntityRoutes($this->entityRoutes);
    }

    abstract public function getHandlerName();

    abstract public function getFormTypeClassName();

    /*
     * @todo abstract the rendering up to handler. then we can have values set in service config?
     */
    public function getGetEntityRoute()
    {
        return $this->entityRoutes['get'];
    }

    public function getPostEntityRoute()
    {
        return $this->entityRoutes['post'];
    }

    public function getPutEntityRoute()
    {
        return $this->entityRoutes['put'];
    }

    public function getPatchEntityRoute()
    {
        return $this->entityRoutes['patch'];
    }

    public function getDeleteEntityRoute()
    {
        return $this->entityRoutes['delete'];
    }

    public function getViewTemplateVar()
    {
        return $this->apiFormTemplateName;
    }

    public function getNewFormTemplate()
    {
        return $this->newFormTemplate;
    }

    public function getEditFormTemplate()
    {
        return $this->editFormTemplate;
    }

    public function getDeleteFormTemplate()
    {
        return $this->deleteFormTemplate;
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param Context $context
     */
    public function setContext(Context $context = null)
    {
        $this->context = $context;
    }

    /**
     * @ApiDoc(
     *     resource=true,
     *     statusCodes={
     *          200="Returned when successful"
     *     }
     * )
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing entities.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", nullable=true, description="How many entities to return.")
     *
     * @Annotations\View(serializerEnableMaxDepthChecks=true)
     *
     * @param Request $request
     * @param ParamFetcher $paramFetcher
     * @return View
     */
    public function cgetAction(Request $request, ParamFetcher $paramFetcher)
    {
        // @todo use events for additional manipulation
        try {
            // @todo add filter function (in handler?) and use array_values() to make sure it returns as json array (otherwise filters can return
            $data = $this->getHandler()->handleGetAllRequest($request, $paramFetcher);

            $code = Response::HTTP_OK;

            // @todo create config to allow bubbling up execptions or use events
        } catch (HandlerException $he) {
            $data = $he->getMessage();
            switch ($he->getUuid()) {
                case HandlerException::ACCESS_DENIED :
                    $code = Response::HTTP_FORBIDDEN;
                    break;
                case HandlerException::BAD_REQUEST :
                    $code = Response::HTTP_BAD_REQUEST;
                    break;
                default:
                    $code = Response::HTTP_INTERNAL_SERVER_ERROR;
                    break;
            }
        } catch (\Exception $e) {
            $data = $e->getMessage();
            $code = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        $view = View::create($data, $code);

        $context = $this->context;
        $view->setContext($context);

        $format = $request->get('_format');
        if (!empty($format)) {
            $view->setFormat($format);
        }

        return $view;
    }

    // @todo: refactor to catch exceptions and return codes more correctly
    /**
     * Get a single entity
     *
     * apidoc and view annotations set in child
     *
     * @param $id
     * @param Request $request
     * @param ParamFetcher $paramFetcher
     * @return View
     */
    public function getAction($id, Request $request, ParamFetcher $paramFetcher)
    {
        $entity = $this->getHandler()->getOr404($id);
        $view = new View(array($this->getViewTemplateVar() => $entity), Response::HTTP_OK);
        $context = $this->context;
        $view->setContext($context);

        $format = $request->get('_format');
        if (!empty($format)) {
            $view->setFormat($format);
        }

        return $view;
    }

    /**
     * Presents the form to use to create a new entity.
     *
     * @Annotations\QueryParam(name="includeSubmitButton", requirements="\w+", nullable=false, description="filter by company type; defaults to 'true'. valid values: 'true', 'false'", default="true")
     *
     * @ApiDoc(
     *     resource=true,
     *     statusCodes = {
     *          200 = "Returned when successful"
     *      }
     * )
     *
     * @Annotations\View(
     *  templateVar = "form"
     * )
     *
     * @param Request $request
     * @param ParamFetcher $paramFetcher
     * @return View
     */
    public function newAction(Request $request, ParamFetcher $paramFetcher)
    {
        $withSubmitButton = filter_var($paramFetcher->get('includeSubmitButton'), FILTER_VALIDATE_BOOLEAN);
        $uri = $this->generateUrl($this->getPostEntityRoute());
        $options = array('action' => $uri);
        $form = $this->getHandler()->getForm(null, $options);
        $html = $this->renderView($this->getNewFormTemplate(),
            array(
                'form' => $form->createView(),
                'withSubmitButton' => $withSubmitButton,
                'submitButtonText' => 'Create',
            ));

        return View::create(array('html' => $html));
    }

    /**
     *
     * Presents the form to use to edit an entity.
     *
     * @Annotations\QueryParam(name="includeSubmitButton", requirements="\w+", nullable=false, description="filter by company type; defaults to 'true'. valid values: 'true', 'false'", default="true")
     *
     * @ApiDoc(
     *     resource=true,
     *     statusCodes = {
     *          200 = "Returned when successful"
     *      }
     * )
     *
     * @Annotations\View(
     *  templateVar = "form"
     * )
     *
     * @param Request $request
     * @param ParamFetcher $paramFetcher
     * @param $id
     * @return View
     */
    public function editAction(Request $request, ParamFetcher $paramFetcher, $id)
    {
        $withSubmitButton = filter_var($paramFetcher->get('includeSubmitButton'), FILTER_VALIDATE_BOOLEAN);
        $entity = $this->getHandler()->getOr404($id);
        $uri = $this->generateUrl($this->getPutEntityRoute(), array('id' => $entity->getId()));
        $options = array('action' => $uri, 'method' => 'PUT');
        $form = $this->getHandler()->getForm($entity, $options);
        $html = $this->renderView($this->getEditFormTemplate(), array(
            'form' => $form->createView(),
            'withSubmitButton' => $withSubmitButton,
            'submitButtonText' => 'Edit',
        ));

        return View::create(array('html' => $html));
    }

    /**
     *
     * Presents the form to use to delete an entity.
     *
     * @Annotations\QueryParam(name="includeSubmitButton", requirements="\w+", nullable=false, description="filter by company type; defaults to 'true'. valid values: 'true', 'false'", default="true")
     *
     * @ApiDoc(
     *     resource=true,
     *     statusCodes = {
     *          200 = "Returned when successful"
     *      }
     * )
     *
     * @Annotations\View(
     *  templateVar = "form"
     * )
     *
     * @param Request $request
     * @param ParamFetcher $paramFetcher
     * @param $id
     * @return View
     */
    public function removeAction(Request $request, ParamFetcher $paramFetcher, $id)
    {
        $withSubmitButton = filter_var($paramFetcher->get('includeSubmitButton'), FILTER_VALIDATE_BOOLEAN);
        $form = $this->createRemoveForm($this->getHandler()->getOr404($id));

        $html = $this->renderView($this->getDeleteFormTemplate(), array(
            'form' => $form->createView(),
            'withSubmitButton' => $withSubmitButton,
            'submitButtonText' => 'Delete',
        ));

        return View::create(array('html' => $html));
    }

    /**
     * @param BasicEntityInterface $entity
     * @return FormInterface
     */
    public function createRemoveForm(BasicEntityInterface $entity)
    {
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl($this->getDeleteEntityRoute(), array('id' => $entity->getId())))
            ->setMethod('DELETE')
            ->getForm();

        return $form;
    }

    /**
     * Create an Entity from the submitted data.
     *
     * apidoc and view annotations set in child
     *
     * @param Request $request the request object
     *
     * @param ParamFetcher $paramFetcher
     * @return View
     * @throws HandlerException
     */
    public function postAction(Request $request, ParamFetcher $paramFetcher)
    {
        try {
            $newEntity = $this->getHandler()->handlePostRequest(
                $request,
                $paramFetcher
            );

            $routeOptions = array(
                'id' => $newEntity->getId(),
                '_format' => $request->get('_format')
            );

            $view = new View(array($this->getViewTemplateVar() => $newEntity), Response::HTTP_CREATED);
            $view->setRoute($this->getGetEntityRoute());
            $view->setRouteParameters($routeOptions);
            $context = $this->context;
            $view->setContext($context);
        } catch (NotNullConstraintViolationException $nncve) {
            // @todo refactor handlers to be transactional
            // preg_match /Integrity constraint violation: 1048 Column '(\w+)' cannot be null/
            $message = $nncve->getMessage();
            // lazy transform the column name for referencing in returned error message
            preg_match('/Integrity constraint violation: 1048 Column \'(\w+)\' cannot be null/', $message, $matches);
            if (count($matches) > 1) {
                // clip off _id ending
                $column = $matches[1];
                $idPosition = strpos($column, '_id');
                if (false === $idPosition) {
                    $idPosition = strlen($column);
                }

                $column = substr($column, 0, $idPosition);
                // explode on underscore
                $aColumn = explode('_', $column);
                // create property name
                $property = "";
                foreach ($aColumn as $k => $v) {
                    if (0 !== $k) {
                        $v = ucfirst($v);
                    }
                    $property .= $v;
                }

                $message = $property . " can not be null.";
            }

            $viewOptions = array('error' => $message);

            $view = View::create($viewOptions, Response::HTTP_BAD_REQUEST);
        } catch (UniqueConstraintViolationException $ucve) {
            $message = $ucve->getMessage();
            $pattern = "/Duplicate entry '(.*)'/";
            preg_match($pattern, $message, $matches);
            if (count($matches) > 0) {
                $message = "This item has already been recorded in the database.";
                // would be awesome if we could figure out what the unique constraints are and do a find one by and return that.
            }

            $viewOptions = array('error' => $message);

            $view = View::create($viewOptions, Response::HTTP_CONFLICT);
        } catch (HandlerException $he) {
            // only bubble up handler exception for handling at concrete controller level
            throw $he;
        } catch (\Exception $e) {
            $viewOptions = array('error' => $e->getMessage());

            $view = View::create($viewOptions, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $format = $request->get('_format');
        if (!empty($format)) {
            $view->setFormat($format);
        }

        return $view;
    }

    /**
     * Update existing entity from the submitted data or create a new entity at a specific location.
     *
     * apidoc and view annotations set in child
     *
     * @param Request $request the request object
     * @param ParamFetcher $paramFetcher
     * @param int $id the entity id
     * @return View
     */
    public function putAction(Request $request, ParamFetcher $paramFetcher, $id)
    {
        //$this->get('monolog.logger.api')->debug(__METHOD__ . ":" . __LINE__ . 'request: ' . var_export($request, true));
        if (!($entity = $this->getHandler()->get($id))) {
            $statusCode = Response::HTTP_CREATED;
            $entity = $this->getHandler()->handlePostRequest(
                $request,
                $paramFetcher
            );
        } else {
            $statusCode = Response::HTTP_NO_CONTENT;
            $entity = $this->getHandler()->handlePutRequest(
                $entity,
                $request,
                $paramFetcher
            );
        }

        //$this->get('monolog.logger.api')->debug(__METHOD__ . ":" . __LINE__ . " - create:\n " . $statusCode);

        $routeOptions = array(
            'id' => $entity->getId(),
            '_format' => $request->get('_format')
        );

        //$this->get('monolog.logger.api')->debug(__METHOD__ . ":" . __LINE__ . " - route options:\n" . var_export($routeOptions, true));

        $view = new View(array($this->getViewTemplateVar() => $entity), $statusCode);
        $view->setRoute($this->getGetEntityRoute());
        $view->setRouteParameters($routeOptions);

        $format = $request->get('_format');
        if (!empty($format)) {
            $view->setFormat($format);
        }

        return $view;
    }

    /**
     * Update existing entity from the submitted data or create a new entity at a specific location.
     *
     * apidoc and view annotations set in child
     *
     * @param Request $request the request object
     * @param ParamFetcher $paramFetcher
     * @param int $id the entity id
     * @return View
     */
    public function patchAction(Request $request, ParamFetcher $paramFetcher, $id)
    {
        $entity = $this->getHandler()->handlePatchRequest(
            $this->getHandler()->getOr404($id),
            $request,
            $paramFetcher
        );

        $routeOptions = array(
            'id' => $entity->getId(),
            '_format' => $request->get('_format')
        );

        $view = new View(array($this->getViewTemplateVar() => $entity), Response::HTTP_NO_CONTENT);
        $view->setRoute($this->getGetEntityRoute());
        $view->setRouteParameters($routeOptions);
        //return $this->routeRedirectView('api_1_get_entity', $routeOptions, Response::HTTP_NO_CONTENT);

        $format = $request->get('_format');
        if (!empty($format)) {
            $view->setFormat($format);
        }

        return $view;
    }

    /**
     * Delete existing Entity
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     400 = "Returned when the form has errors"
     *   }
     * )
     *
     * @param Request $request the request object
     * @param int $id the entity id
     *
     * @return View
     *
     * @throws NotFoundHttpException when entity not exist
     */
    public function deleteAction(Request $request, ParamFetcher $paramFetcher, $id)
    {
        $entity = $this->getHandler()->handleDeleteRequest(
            $this->getHandler()->getOr404($id),
            $request,
            $paramFetcher
        );

        $view = new View(null, Response::HTTP_NO_CONTENT);

        $format = $request->get('_format');
        if (!empty($format)) {
            $view->setFormat($format);
        }

        return $view;
    }

    /**
     * @return AbstractRestHandler|object
     */
    public function getHandler()
    {
        return $this->get($this->getHandlerName());
    }

    /**
     * @return AbstractType
     */
    public function getFormType()
    {
        $formTypeName = $this->getFormTypeClassName();

        return new $formTypeName();
    }

    /**
     * @param string $routePrefixParameterName
     */
    public function setRoutePrefixParameterName($routePrefixParameterName)
    {
        $this->routePrefixParameterName = $routePrefixParameterName;
    }
}