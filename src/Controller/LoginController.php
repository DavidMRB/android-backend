<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        // This method will be intercepted by the Symfony firewall
        // The JSON login authenticator will handle the authentication
        // If we reach here, authentication was successful and the JWT is already generated
        // by the success_handler in security.yaml
        
        return new JsonResponse([
            'message' => 'Login successful'
        ]);
    }
}
