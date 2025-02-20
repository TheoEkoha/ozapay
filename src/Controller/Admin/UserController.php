<?php
namespace App\Controller\Admin;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/users', name: 'api.user.')]
//#[Route('/admin/users', name: 'admin.user.')]dfqfsf
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $repository,
        private EntityManagerInterface  $em,
    ) {}

    #[Route('/', name: 'list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->repository->findAll();
        
        $data = array_map(fn(User $user) => [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'status' => $user->getStatus()->value,
            'created_at' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
        ], $users);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'profile', methods: ['GET'])]
    public function profile(User $user): JsonResponse
    {
        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'status' => $user->getStatus()->value,
            'created_at' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
        ];

        return $this->json($data);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['DELETE'])]
    public function delete(User $user): JsonResponse
    {
        $this->em->remove($user);
        $this->em->flush();

        return $this->json(['message' => 'User deleted successfully']);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['PUT'])]
    public function update(Request $request, User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
    
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }
    
        // Logique pour mettre à jour l'utilisateur
        return $this->userService->updateUser($user, $data);
    }
}