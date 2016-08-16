<?php
namespace Zend\Expressive\LegacyBridge\Zf1;

use Zend\Hydrator\HydratorInterface;
use Zf1ExpBridge\Psr7Bridge\ServerRequest;
use Zf1ExpBridge\Psr7Bridge\Response;
use Psr\Http\Message\ServerRequestInterface;

class Bridge {
    /**
     * @var \Zend_Application
     */
    private $application;

    /**
     * @var \Zend_Controller_Action_Helper_ViewRenderer
     */
    private $viewRenderer;
    
    /**
     * @var \Zend_Controller_Request_Abstract
     */
    private $zendRequest;
    
    /**
     * @var HydratorInterface
     */
    private $responseHydrator;
    
    private $paramsSetter;
   
    public function __construct(
        callable $requestParamsStrategy,
        \Zend_Application $application, 
        \Zend_Controller_Action_Helper_ViewRenderer$viewRenderer,
        callable $responseHydrator
    ) {

        $this->requestParamsStrategy = $requestParamsStrategy;
        $this->application = $application;
        $this->viewRenderer = $viewRenderer;
        $this->responseHydrator = $responseHydrator;
    }
    
    public function __invoke(ServerRequestInterface $req, $res, $next) {
        $routeResult = $req->getAttribute('Zend\Expressive\Router\RouteResult');
        $routeName = $routeResult->getMatchedRouteName();
        
        $req = ServerRequest::toZf1($req, ($this->requestParamsStrategy)($routeName));
        
        $this->application->bootstrap();
        
        $front = $this->application->getBootstrap()->getResource('FrontController');
        $front->setRequest($req);
        $front->returnResponse(true);
        
        $this->application->run();
    
        $view = $this->viewRenderer->getActionController()->view;
        
        $response = $this->viewRenderer->getActionController()->getResponse();
        
        return Response::fromZf1ViewToJson($response, ($this->responseHydrator)($routeName), $view);
    }
}