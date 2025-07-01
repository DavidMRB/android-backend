<?php

// src/EventListener/TokenCheckListener.php
namespace App\EventListener;

use App\Repository\AccessTokenRepository;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use \Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;

class TokenCheckListener
{
    public function __construct(
        private AccessTokenRepository $accessTokenRepo,
        private Security $security,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) return;

        $request = $event->getRequest();
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) return;

        $jwt = substr($authHeader, 7); // quitar "Bearer "

        $token = $this->accessTokenRepo->findOneBy(['token' => $jwt]);

        if (!$token) {
            $response = new JsonResponse(['error' => 'Token no válido o revocado.'], Response::HTTP_UNAUTHORIZED);
            $event->setResponse($response);
        }
    }
}
