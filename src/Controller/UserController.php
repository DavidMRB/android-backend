<?php

namespace App\Controller;


use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
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
                '<p>Gracias por registrarte. Haz clic aquí para activar tu cuenta:</p><a href="http://159.203.187.94/auth/activate?token=%s">Activar cuenta</a>',
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

    #[Route('/user', name: 'user_profile', methods: ['GET', 'PUT'])]
    public function getUserProfile(Request $request, EntityManagerInterface $em, Security $security): JsonResponse
    {

        // GET, Give me the info regarding the user authenticated
        $user = $security->getUser();

        if (!$user instanceof User) {
            return $this->json(['error'=> [
                'code' => 401,
                'type' => 'Unauthorized',
                'message' => 'Token invalido o Usuario no autenticado'
            ]], 401);
        }

        if ($request->getMethod() === 'GET') {
		return $this->json(['user' => [
		'id' => $user->getId(),	
                'name' => $user->getName(),
                'last_name' => $user->getLastName(),
                'age' => $user->getAge(),
                'email' => $user->getEmail(),
                'profile_image_url' => $user->getProfileImageUrl()
            ]]);
        }

        //PUT, Update the information in user profile
        $data = json_decode($request->getContent(), true);

        $allowedFields = ['name', 'last_name', 'age'];
        $invalidFields = array_diff(array_keys($data), $allowedFields);

        if (!empty($invalidFields)) {
            return $this->json([
                'error' => [
                    'code' => 400,
                    'type' => 'invalid fields',
                    'message' => 'Los siguientes campos no se pueden actualizar: ' . implode(', ', $invalidFields),
                ]
            ], 400);
        }

        if (!is_array($data) || empty($data)) {
            return $this->json(['error'=> [
                'code' => 400,
                'type' => 'invalid payload',
                'message' => "Debes enviar al menos un campo valido"
            ]], 400);
    }

        if (isset($data['name'])) {
            $user->setName($data['name']);
        }
        if (isset($data['last_name'])) {
            $user->setLastName($data['last_name']);
        }
        if (isset($data['age'])) {
            $user->setAge($data['age']);
        }

        $em->flush();

        return $this->json([
            'code' => 200,
            'message'=> 'Usuario actualizado exitosamente']);
    }

    #[Route('/user/profile-image', name: 'user_profile_image', methods: ['POST'])]
    public function uploadProfileImage(Request $request, Security $security, EntityManagerInterface $em): JsonResponse
    {
        $user = $security->getUser();

        if (!$user) {
            return $this->json(['error' => [
                'code' => 401,
                'type' => 'Unauthorized',
                'message' => 'Token invalido o Usuario no autenticado'
            ]], 401);
        }

        $file = $request->files->get('image');

        if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png'])) {
            return $this->json(['error' => [
                'code' => 400,
                'type' => 'invalid image',
                'message' => 'Solo se permiten imágenes JPG o PNG'
            ]], 400);
        }


        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profile_pictures';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }


        $filename = uniqid() . '.' . $file->guessExtension();
        $file->move($uploadDir, $filename);

        $publicUrl = '/uploads/profile_pictures/' . $filename;
        $user->setProfileImageUrl($publicUrl);

        $em->flush();

        return $this->json(['message' => 'Imagen actualizada', 'profile_image_url' => $publicUrl]);
    }

    #[Route('/users', name: 'user_list', methods: ['GET'])]
    public function listUsers(UserRepository $userRepository, Security $security): JsonResponse
    {
        $currentUser = $security->getUser();

        if (!$currentUser) {
            return $this->json(['error' => [
                'code' => 401,
                'type' => 'unauthorized',
                'message' => 'Usuario no autenticado'
            ]], 401);
        }

        $users = $userRepository->createQueryBuilder('u')
            ->where('u != :currentUser')
            ->andWhere('u.is_active = :active')
            ->setParameter('currentUser', $currentUser)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        $response = [];

        foreach ($users as $u) {
            $response[] = [
                'id' => $u->getId(),
                'name' => $u->getName() . ' ' . $u->getLastName(),
                'profile_image_url' => $u->getProfileImageUrl(),
            ];
        }

        return $this->json(['users' => $response]);
    }


}

