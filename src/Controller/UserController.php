<?php

namespace App\Controller;


use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Random\RandomException;

#[Route('/api', name: 'api')]
final class UserController extends AbstractController
{
    #[Route('/register', name: 'user_register', methods: ['post'])]
    public function userRegister(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        MailerInterface $mailer
    ): JsonResponse {

        $body = $request->getContent();
        $data = json_decode($body, true);

        $requiredFields = ['name', 'last_name', 'age', 'email', 'password', 'confirm_password'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->json(['error'=> [
                    'code' => 400,
                    'type' => 'invalid payload',
                    'message' => "El campo '$field' es obligatorio"
                ]], 400);
            }
        }

        if ($data['password'] !== $data['confirm_password']) {
            return $this->json(['error'=> [
                'code' => 400,
                'type' => 'invalid payload',
                'message' => 'Las contraseñas no coinciden'
            ]], 400);
        }

        if ($userRepository->findOneBy(['email' => $data['email']])) {
            return $this->json(['error'=> [
                'code' => 409,
                'type' => 'user already exists',
                'message' => 'Ya existe un usuario registrado con ese correo'
            ]], 409);
        }


        $user = new User();
        $user->setName($data['name']);
        $user->setLastName($data['last_name']);
        $user->setAge($data['age']);
        $user->setEmail($data['email']);
        $user->setIsActive(false);

        try {
            $user->setActivationToken(bin2hex(random_bytes(16)));
        } catch (RandomException) {
            return $this->json(['error' => 'Error generando el token. Intenta de nuevo.'], 500);
        }

        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $data['password']
        );
        $user->setPassword($hashedPassword);


        $email = (new Email())
            ->from('noreply@tuapp.com')
            ->to($user->getEmail())
            ->subject('Activa tu cuenta')
            ->html(sprintf(
                '<p>Gracias por registrarte. Haz clic aquí para activar tu cuenta:</p><a href="http://localhost/auth/activate?token=%s">Activar cuenta</a>',
                $user->getActivationToken()
            ));

        try {
            $mailer->send($email);

            $em->persist($user);
            $em->flush();

        } catch (TransportExceptionInterface) {
            return $this->json([
                'error' => [
                    'code' => 500,
                    'type' => 'mailer error',
                    'message' => 'No se pudo enviar el correo de activación. Intenta nuevamente más tarde.'
                ]
            ], 500);
        }


        return $this->json(['message'=> 'Usuario registrado exitosamente. Se ha enviado un correo de verificación.'], 201);
    }


}

