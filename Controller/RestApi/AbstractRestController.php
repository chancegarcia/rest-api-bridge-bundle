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
use Chance\RestApi\BridgeBundle\Exception\InvalidFormException;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
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
abstract class AbstractRestController extends AbstractRestViewController
{
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
     * @return Response
     */
    public function cgetAction(Request $request, ParamFetcher $paramFetcher)
    {
       $view = parent::cgetAction($request, $paramFetcher);

        return $this->handleView($view);
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
     * @return Response
     */
    public function getAction($id, Request $request, ParamFetcher $paramFetcher)
    {
        $view = parent::getAction($id, $request, $paramFetcher);

        return $this->handleView($view);
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
     * @return Response
     */
    public function newAction(Request $request, ParamFetcher $paramFetcher)
    {
        $view = parent::newAction($request, $paramFetcher);

        return $this->handleView($view);
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
     * @return Response
     */
    public function editAction(Request $request, ParamFetcher $paramFetcher, $id)
    {
        $view = parent::editAction($request, $paramFetcher, $id);

        return $this->handleView($view);
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
     * @return Response
     */
    public function removeAction(Request $request, ParamFetcher $paramFetcher, $id)
    {
        $view = parent::removeAction($request, $paramFetcher, $id);

        return $this->handleView($view);
    }

    /**
     * Create an Entity from the submitted data.
     *
     * apidoc and view annotations set in child
     *
     * @param Request $request the request object
     *
     * @param ParamFetcher $paramFetcher
     * @return FormInterface|FormTypeInterface|Response
     * @throws HandlerException
     */
    public function postAction(Request $request, ParamFetcher $paramFetcher)
    {
        try {
            $view = parent::postAction($request, $paramFetcher);
        } catch (InvalidFormException $exception) {
            $this->get('logger')->error(__METHOD__ . ":" . __LINE__ . " - " . $exception);

            /**
             * @var \Symfony\Component\Form\Form $form
             */
            $form = $exception->getForm();

            if (Response::HTTP_CONFLICT !== $exception->getCode()) {
                return $form;
            }

            $viewOptions = array('error' => (string)$form->getErrors(true));

            $view = View::create($viewOptions, Response::HTTP_CONFLICT);
        } catch (HandlerException $he) {
            // only bubble up handler exception for handling at concrete controller level
            throw $he;
        }

        return $this->handleView($view);
    }

    /**
     * Update existing entity from the submitted data or create a new entity at a specific location.
     *
     * apidoc and view annotations set in child
     *
     * @param Request $request the request object
     * @param ParamFetcher $paramFetcher
     * @param int $id the entity id
     * @return Response|FormTypeInterface|FormInterface
     */
    public function putAction(Request $request, ParamFetcher $paramFetcher, $id)
    {
        try {
            $view = parent::putAction($request, $paramFetcher, $id);
        } catch (InvalidFormException $exception) {

        /**
         * @var \Symfony\Component\Form\Form $form
         */
        $form = $exception->getForm();

        if (Response::HTTP_CONFLICT !== $exception->getCode()) {
            return $form;
        }

        $viewOptions = array('error' => (string)$form->getErrors(true));

        $view = View::create($viewOptions, Response::HTTP_CONFLICT);
        }

        return $this->handleView($view);
    }

    /**
     * Update existing entity from the submitted data or create a new entity at a specific location.
     *
     * apidoc and view annotations set in child
     *
     * @param Request $request the request object
     * @param ParamFetcher $paramFetcher
     * @param int $id the entity id
     * @return Response|FormTypeInterface|FormInterface
     */
    public function patchAction(Request $request, ParamFetcher $paramFetcher, $id)
    {
        try {
            $view = parent::patchAction($request, $paramFetcher, $id);
        } catch (InvalidFormException $exception) {

            return $exception->getForm();
        }

        return $this->handleView($view);
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
     * @return FormTypeInterface|Response|FormInterface
     *
     * @throws NotFoundHttpException when entity not exist
     */
    public function deleteAction(Request $request, ParamFetcher $paramFetcher, $id)
    {
        try {
            $view = parent::deleteAction($request, $paramFetcher, $id);
        } catch (InvalidFormException $exception) {

            return $exception->getForm();
        }

        return $this->handleView($view);
    }
}