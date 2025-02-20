<?php
namespace App\Controller\Admin;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

//#[Route('/api/admin/users', name: 'api.admin.user.')]
#[Route('/admin/users', name: 'admin.user.')]
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

        // Assurez-vous que les données sont valides
        if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Invalid email'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Mettre à jour les informations de l'utilisateur
        $user->setEmail($data['email']);

        // Si vous avez d'autres champs à mettre à jour, ajoutez-les ici

        $this->em->flush();

        return $this->json(['message' => 'User updated successfully']);
    }
}