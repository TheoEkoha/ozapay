<?php

namespace App\Controller\Api\User;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class PinController
{
    private $userRepository;
    private $em;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $em)
    {
        $this->userRepository = $userRepository;
        $this->em = $em;
    }

    /**
     * @Route("/update-pin/{id}", name="update_pin", methods={"POST"})
     */
    public function updatePin(int $id, Request $request)
    {
        try {
            $user = $this->userRepository->find($id);

            if (!$user) {
                return new JsonResponse(['error' => 'User not found.'], JsonResponse::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            $pin = $data['pin'] ?? null;

            if (!$pin) {
                return new JsonResponse(['error' => 'PIN is required.'], JsonResponse::HTTP_BAD_REQUEST);
            }

            // Appel à la méthode pour gérer le code PIN
            $user->updatePin($pin); // Déplacez la logique de gestion du code PIN dans cette méthode

            $this->em->persist($user);
            $this->em->flush();

            return new JsonResponse(['message' => 'PIN updated successfully.']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}