<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;


/**
 * Class ApiExceptionSubscriber
 */
class ApiExceptionSubscriber implements EventSubscriberInterface
{


    /**
     * @var KernelInterface
     */
    private $kernel;


    /**
     * ApiExceptionSubscriber constructor.
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => 'onKernelException'
        );
    }


    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {

        if(($event->getRequest()->headers->get('User-Agent')) == 'PostmanRuntime/7.20.1') {
            return;
        }

        $e = $event->getException();

        $path = $event->getRequest()->getPathInfo();

        $isApi = (false != preg_match('#^/api/#',$path));


        if(!$isApi) {
            if (!$e instanceof HttpException) {

                if($this->kernel->isDebug()) {
                    return;
                }

                $event->getRequest()->getSession()->getFlashbag()->add('error', $e->getMessage());

                $response = new RedirectResponse('/dispatch');

                $event->setResponse($response);
                return;
            } else {
                return;
            }
        }

        if(method_exists($e,'getStatusCode')) {
            $code = $e->getStatusCode();
            $httpCode = $code;
        } else {
            $code = $e->getCode();
            $httpCode = 500;
        }

        if($httpCode == 0) {
            $httpCode = 500;
        }

        $data = array(
            'status' => $code,
            'error' => $e->getMessage(),
        );

        if($this->kernel->isDebug()) {
            $data['file'] = $e->getFile();
            $data['trace_as_string'] = $e->getTraceAsString();
            $data['trace'] = $e->getTrace();
        }

            $response =  new JsonResponse($data,$httpCode);


        $event->setResponse($response);

    }

}