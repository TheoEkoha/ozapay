<?php
namespace App\Controller\Admin;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use App\Service\Api\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

#[Route('/api/users', name: 'api.user.')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly UserService $service,
        private EntityManagerInterface $em,
        private LoggerInterface $logger // Injection du logger
    ) {}

    #[Route('/', name: 'list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->repository->findAll();

        $data = array_map(fn(User $user) => [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'status' => $user->getStatus()->value,
            'created_at' => $user->getCreated()?->format('Y-m-d H:i:s'),
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
            'created_at' => $user->getCreated()?->format('Y-m-d H:i:s'),
        ];

        return $this->json($data);
    }


    #[Route('/profile/{id}', name: 'profileuser', methods: ['GET'])]
    public function profileUser(int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);

        $this->logger->info('API Call PROFIL USER', [
            'USER' => $user
        ], ['channel' => 'api']);

        $data = $this->json([
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
        ]);
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

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['status'])) {
            $user->setStatus($data['status']);
        }

        $this->em->flush();

        return new JsonResponse(['message' => 'User updated successfully'], JsonResponse::HTTP_OK);
    }

     // Nouvelle route pour supprimer un utilisateur par numéro de téléphone
     #[Route('/delete', name: 'delete', methods: ['POST'])]
     public function deleteUser(Request $request): JsonResponse
     { 
        $body = json_decode($request->getContent(), true);
        $phone = $body['phoneNumber'] ?? null; // Récupérer le numéro de téléphone depuis le corps

         if (!$phone) {
             return new JsonResponse(['error' => 'Le numéro de téléphone est requis.'], JsonResponse::HTTP_BAD_REQUEST);
         }
 
         $this->logger->info('API Call', [
             'path' => $request->getPathInfo(),
             'method' => $request->getMethod(),
             'headers' => $request->headers->all(),
             'body' => json_decode($request->getContent(), true),
         ], ['channel' => 'api']);
 
         try {
             $this->service->deleteUserByPhoneNumber($phone); // Appeler la méthode pour supprimer l'utilisateur
             return new JsonResponse(['message' => 'Utilisateur supprimé avec succès.'], JsonResponse::HTTP_OK);
         } catch (\Exception $e) {
             return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_NOT_FOUND);
         }
     }

    // Nouvelle route pour la liste des utilisateurs sans le préfixe api.user
    #[Route('/admin/users', name: 'admin.user.list', methods: ['GET'])]
    public function adminIndex(): JsonResponse
    {
        return $this->index(); // Réutilise la méthode index pour éviter la duplication
    }

    // // Nouvelle route pour supprimer un utilisateur par numéro de téléphone
    // #[Route('/admin/users/delete', name: 'admin.user.delete', methods: ['POST'])]
    // public function deleteUser(Request $request): JsonResponse
    // {
    //     $phone = $request->query->get('phone'); // Récupérer le numéro de téléphone depuis les paramètres de requête

    //     if (!$phone) {
    //         return new JsonResponse(['error' => 'Le numéro de téléphone est requis.'], JsonResponse::HTTP_BAD_REQUEST);
    //     }

    //     $this->logger->info('API Call', [
    //         'path' => $request->getPathInfo(),
    //         'method' => $request->getMethod(),
    //         'headers' => $request->headers->all(),
    //         'body' => json_decode($request->getContent(), true),
    //     ], ['channel' => 'api']);

    //     try {
    //         $this->service->deleteUserByPhoneNumber($phone); // Appeler la méthode pour supprimer l'utilisateur
    //         return new JsonResponse(['message' => 'Utilisateur supprimé avec succès.'], JsonResponse::HTTP_OK);
    //     } catch (\Exception $e) {
    //         return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_NOT_FOUND);
    //     }
    // }
}