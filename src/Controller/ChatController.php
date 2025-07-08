<?php

namespace App\Controller;

use App\Entity\Chat;
use App\Entity\ChatMember;
use App\Entity\User;
//use App\Repository\ChatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/chats', name: 'chat_')]
class ChatController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security
    )
    {
    }

    #[Route('', name: 'list_chats', methods: ['GET'])]
    public function listChats(): JsonResponse
    {
        $user = $this->security->getUser();

        $memberships = $this->em->getRepository(ChatMember::class)
            ->findBy(['user_associated' => $user]);

        $chats = [];

        foreach ($memberships as $membership) {
            $chat = $membership->getChat();

            // Excluir chats sin miembros válidos o sin entidad
            if (!$chat) {
                continue;
            }

            if (!$chat->isGroup()) {
                // Chat individual - mostrar datos del otro usuario
                $otherMember = $chat->getChatMembers()
                    ->filter(fn($m) => $m->getUserAssociated()->getId() !== $user->getId())
                    ->first();

                $otherUser = $otherMember ? $otherMember->getUserAssociated() : null;

                $chats[] = [
                    'id' => $chat->getId(),
                    'is_group' => false,
                    'name' => $otherUser?->getName(),
                    'profile_image_url' => $otherUser?->getProfileImageUrl(),
                    'user_id' => $otherUser?->getId(),
                ];
            } else {
                // Grupo
                $chats[] = [
                    'id' => $chat->getId(),
                    'is_group' => true,
                    'name' => $chat->getName(),
                    'members_count' => count($chat->getChatMembers()),
                ];
            }
        }

        return $this->json(['chats' => $chats]);
    }


    #[Route('', name: 'create_individual', methods: ['POST'])]
    public function createIndividual(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();

        if (!isset($data['user_id'])) {
            return $this->json(['error' => 'user_id es requerido'], 400);
        }

        $otherUser = $this->em->getRepository(User::class)->find($data['user_id']);
        if (!$otherUser) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        // Buscar un chat individual existente entre ambos
        $existingChats = $this->em->getRepository(Chat::class)
            ->findBy(['isGroup' => false]);

        foreach ($existingChats as $chat) {
            $members = $chat->getChatMembers();

            if (count($members) === 2) {
                $memberIds = array_map(
                    fn($m) => $m->getUserAssociated()->getId(),
                    $members->toArray()
                );

                if (in_array($user->getId(), $memberIds) && in_array($otherUser->getId(), $memberIds)) {
                    // Ya existe un chat, devolverlo
                    return $this->json([
                        'id' => $chat->getId(),
                        'type' => 'individual',
                        'participants' => [
                            ['id' => $user->getId(), 'name' => $user->getName()],
                            ['id' => $otherUser->getId(), 'name' => $otherUser->getName()],
                        ]
                    ]);
                }
            }
        }

        // Si no existe, crear nuevo chat
        $chat = new Chat();
        $chat->setIsGroup(false);
        $chat->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($chat);

        foreach ([$user, $otherUser] as $u) {
            $member = new ChatMember();
            $member->setChat($chat);
            $member->setUserAssociated($u);
            $this->em->persist($member);
        }

        $this->em->flush();

        return $this->json([
            'id' => $chat->getId(),
            'type' => 'individual',
            'participants' => [
                ['id' => $user->getId(), 'name' => $user->getName()],
                ['id' => $otherUser->getId(), 'name' => $otherUser->getName()],
            ]
        ], 201);
    }



    #[Route('/group', name: 'create_group', methods: ['POST'])]
    public function createGroup(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();

        if (empty($data['name']) || empty($data['members']) || !is_array($data['members'])) {
            return $this->json(['error' => 'Nombre del grupo y miembros son requeridos'], 400);
        }

        $chat = new Chat();
        $chat->setIsGroup(true);
        $chat->setName($data['name']);
        $chat->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($chat);

        // Agregar al creador como miembro
        $member = new ChatMember();
        $member->setChat($chat);
        $member->setUserAssociated($user);
        $this->em->persist($member);

        // Agregar los otros usuarios
        $users = $this->em->getRepository(User::class)->findBy(['id' => $data['members']]);
        foreach ($users as $u) {
            if ($u->getId() === $user->getId()) {
                continue; // ya fue agregado
            }

            $m = new ChatMember();
            $m->setChat($chat);
            $m->setUserAssociated($u);
            $this->em->persist($m);
        }

        $this->em->flush();

        // Recuperamos miembros desde DB actualizada
        $members = $this->em->getRepository(ChatMember::class)->findBy(['chat' => $chat]);

        $participants = [];
        foreach ($members as $m) {
            $participant = $m->getUserAssociated();
            $participants[] = [
                'id' => $participant->getId(),
                'name' => $participant->getName()
            ];
        }

        return $this->json([
            'id' => $chat->getId(),
            'type' => 'group',
            'name' => $chat->getName(),
            'participants' => $participants
        ], 201);
    }



    #[Route('/{id}', name: 'chat_detail', methods: ['GET'])]
    public function chatDetail(int $id): JsonResponse
    {
        $user = $this->security->getUser();
        $chat = $this->em->getRepository(Chat::class)->find($id);

        if (!$chat) {
            return $this->json(['error' => 'Chat no encontrado'], 404);
        }

        $isMember = $this->em->getRepository(ChatMember::class)
            ->findOneBy(['chat' => $chat, 'user_associated' => $user]);

        if (!$isMember) {
            return $this->json(['error' => 'Acceso denegado'], 403);
        }

        // Participantes
        $participants = [];
        foreach ($chat->getChatMembers() as $member) {
            $participant = $member->getUserAssociated();
            $participants[] = [
                'id' => $participant->getId(),
                'name' => $participant->getName()
            ];
        }

        // Mensajes
        $messages = [];
        foreach ($chat->getMessages() as $message) {
            $sender = $message->getSender();
            $messages[] = [
                'id' => $message->getId(),
                'sender' => [
                    'id' => $sender->getId(),
                    'name' => $sender->getName()
                ],
                'content' => $message->getContent(),
                'sent_at' => $message->getCreatedAt()->format('c')
            ];
        }

        return $this->json([
            'id' => $chat->getId(),
            'type' => $chat->isGroup() ? 'group' : 'individual',
            'name' => $chat->isGroup() ? $chat->getName() : null,
            'participants' => $participants,
            'messages' => $messages
        ]);
    }


    #[Route('/{id}/leave', name: 'leave', methods: ['POST'])]
    public function leaveGroup(int $id): JsonResponse
    {
        $user = $this->security->getUser();
        $chat = $this->em->getRepository(Chat::class)->find($id);

        if (!$chat || !$chat->isGroup()) {
            return $this->json(['error' => 'Grupo no válido'], 400);
        }

        $member = $this->em->getRepository(ChatMember::class)
            ->findOneBy(['chat' => $chat, 'user_associated' => $user]);

        if ($member) {
            $this->em->remove($member);
            $this->em->flush();
        }

        return $this->json(['message' => 'Has salido del grupo']);
    }
}

