<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['email']) || !isset($data['password'])) {
                return $this->json([
                    'error' => [
                        'code' => 400,
                        'type' => 'invalid_request',
                        'message' => 'Email and password are required'
                    ]
                ], 400);
            }

            $user = $userRepository->findOneBy(['email' => $data['email']]);

            if (!$user) {
                return $this->json([
                    'error' => [
                        'code' => 401,
                        'type' => 'invalid_credentials',
                        'message' => 'Invalid email or password'
                    ]
                ], 401);
            }

            if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
                return $this->json([
                    'error' => [
                        'code' => 401,
                        'type' => 'invalid_credentials',
                        'message' => 'Invalid email or password'
                    ]
                ], 401);
            }

            if (!$user->isActive()) {
                return $this->json([
                    'error' => [
                        'code' => 403,
                        'type' => 'account_inactive',
                        'message' => 'Your account is not activated'
                    ]
                ], 403);
            }

            // Return success - the JWT will be handled by the firewall
            return $this->json([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => [
                    'code' => 500,
                    'type' => 'server_error',
                    'message' => 'An error occurred: ' . $e->getMessage()
                ]
            ], 500);
        }
    }
}
