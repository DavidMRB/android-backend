<?php

// src/Controller/AuthController.php

namespace App\Controller;

use App\Repository\AccessTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;


#[Route('/api', name: 'api_logout')]
class AuthController extends AbstractController
{
    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(
        Request $request,
        AccessTokenRepository $accessTokenRepo,
        EntityManagerInterface $em,
        Security $security
    ): JsonResponse {
        $user = $security->getUser();

        if (!$user) {
            return $this->json([
                'error' => [
                    'code' => 401,
                    'type' => 'unauthorized',
                    'message' => 'Usuario no autenticado',
                ]
            ], 401);
        }

        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->json([
                'error' => [
                    'code' => 400,
                    'type' => 'invalid_request',
                    'message' => 'Token no enviado correctamente en la cabecera Authorization.',
                ]
            ], 400);
        }

        $jwt = substr($authHeader, 7); // Quitar "Bearer "

        $accessToken = $accessTokenRepo->findOneBy(['token' => $jwt]);

        if (!$accessToken || $accessToken->getUserToken() !== $user) {
            return $this->json([
                'error' => [
                    'code' => 404,
                    'type' => 'not_found',
                    'message' => 'Token no válido o ya fue eliminado.',
                ]
            ], 404);
        }

        $em->remove($accessToken);
        $em->flush();

        return $this->json(['message' => 'Sesión cerrada exitosamente']);
    }
}

