<?php

namespace Chance\RestApi\BridgeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('ChanceRestApiBridgeBundle:Default:index.html.twig');
    }
}
