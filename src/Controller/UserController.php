<?php


namespace App\Controller;


use App\Entity\Profile;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


/**
 *
 * @Route("/api/")
 *
 * Class UserController
 * @package App\Controller
 */
class UserController extends AbstractController
{

    /**
     *
     * @Route("users/me", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function meAction()
    {
        $user = $this->getUser();

        return new JsonResponse($user);
    }


    /**
     *
     * @Route("register", methods={"POST"})
     *
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse
     */
    public function registerAction(Request $request,UserPasswordEncoderInterface $encoder)
    {
        $body = json_decode($request->getContent(),true);


        if(!$body) {
            throw new BadRequestHttpException("User must be defined");
        }


        try {
            $user = new User($body);

            $em = $this->getDoctrine()->getManager();

            $password = $encoder->encodePassword($user,$user->getPassword());

            $user->setPassword($password);

            $em->persist($user);
            $em->flush();

            return new JsonResponse($user);

        }catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }


    }


    /**
     *
     * @Route("users/{user}", methods={"PUT"})
     *
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse
     */
    public function editAction(Request $request,User $user,UserPasswordEncoderInterface $encoder)
    {
        $body = json_decode($request->getContent(),true);

        if(!$body) {
            throw new BadRequestHttpException("User must be defined");
        }

        if($user != $this->getUser()) {
            throw new AccessDeniedHttpException("Access Denied");
        }


        try {
            $user->setData($body);

            $em = $this->getDoctrine()->getManager();
            if($user->getPassword()) {
                $password = $encoder->encodePassword($user, $user->getPassword());

                $user->setPassword($password);
            }
            $em->persist($user);
            $em->flush();

            return new JsonResponse($user);

        }catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }


    }


}