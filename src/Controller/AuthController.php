<?php

// src/Controller/AuthController.php

namespace App\Controller;

use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken;
use Symfony\Bundle\SecurityBundle\Security;


#[Route('/api', name: 'api_logout')]
class AuthController extends AbstractController
{
    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(
        RefreshTokenManagerInterface $refreshTokenManager,
        EntityManagerInterface $em,
        Security $security
    ): JsonResponse {
        $user = $security->getUser();

        if (!$user) {
            return $this->json(['error' => [
                'code' => 401,
                'type' => 'unauthorized',
                'message' => 'Ususario no autenticado'
            ]], 401);
        }

        $tokens = $em->getRepository(RefreshToken::class)
            ->findBy(['username' => $user->getUserIdentifier()]);

        foreach ($tokens as $token) {
            $refreshTokenManager->delete($token);
        }

        return $this->json(['message' => 'Sesión cerrada exitosamente']);
    }
}

