<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/auth', name: 'auth')]
final class AccountActivationController extends AbstractController
{
    #[Route('/activate', name: 'user_activate', methods: ['GET'])]
    public function activateUser(Request $request, UserRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $token = $request->query->get('token');

        $user = $repo->findOneBy(['activationToken' => $token]);

        if (!$user) {
            return $this->json(['error' => 'Token inválido o expirado.'], 404);
        }

        $user->setIsActive(true);
        $user->setActivationToken(null);
        $em->flush();

        return $this->json(['message' => 'Cuenta activada con exito.']);
    }

    #[Route('/resend-verification', name: 'resend_verification', methods: ['POST'])]
    public function resendVerification(Request $request, EntityManagerInterface $em, UserRepository $repo, MailerInterface $mailer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['error' => [
                'code' => 400,
                'type' => 'invalid payload',
                'message' => "El campo 'email' es obligatorio"
            ]], 400);
        }

        $user = $repo->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->json(['error' => [
                'code' => 404,
                'type' => 'user not found',
                'message' => "El usuario '$email' no existe"
            ]], 404);
        }

        if ($user->isActive()) {
            return $this->json(['error' => [
                'code' => 409,
                'type' => 'user already active',
                'message' => "El usuario '$email' ya se encuentra activo"
            ]], 409);
        }


        try {
            $user->setActivationToken(bin2hex(random_bytes(16)));
            $em->flush();
        } catch (RandomException) {
            return $this->json(['error' =>  [
                'code' => 500,
                'type' => 'error generating activation token',
                'message' => "Error generando el token. Intenta de nuevo."
            ]], 500);
        }

        $emailMessage = (new Email())
            ->from('noreply@tuapp.com')
            ->to($user->getEmail())
            ->subject('Reenvio de activacion de tu cuenta')
            ->html(sprintf(
                "<p>Haz clic aquí para activar tu cuenta:</p><a href='http://localhost/auth/activate?token=%s'>Activar cuenta</a>",
                $user->getActivationToken()
            ));

        try {
            $mailer->send($emailMessage);
        } catch (TransportExceptionInterface) {
            return $this->json(['error' => [
                'code' => 500,
                'type' => 'mailer error',
                'messages' => 'No se pudo enviar el correo de activación. Intenta nuevamente más tarde.'
            ]], 500);
        }

        return $this->json(['message' => 'Cuenta activada con exito.']);

    }
}
