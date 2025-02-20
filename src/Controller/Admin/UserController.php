<?php

namespace App\Controller\Admin;

use App\Common\Constants\Response\ErrorsConstant;
use App\Service\UserService;
use App\Common\Constants\Response\SuccessResponse;
use App\Entity\Enum\Status;
use App\Entity\Enum\Step;
use App\Entity\User\User;
use App\Entity\User\VerificationCode;
use App\JsResponse\JsResponseBuilder;
use App\Repository\User\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route('/admin/user', name: 'admin.user.')]
class UserController extends AbstractAdminController
{
    public function __construct(
        private readonly UserRepository $repository,
        protected TranslatorInterface   $translator,
        private EntityManagerInterface  $em,
        private UserService $userService,
    ) {
    }

    #[Route('/', name: 'list', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('pages/user/index.html.twig', [
            'page_title' => $this->translator->trans('Users'),
            'breadcrumbs' => [
                [
                    'title' => 'Dashboard',
                    'url' => 'dashboard'
                ],
                [
                    'title' => 'Users',
                    'url' => 'admin.user.list'
                ]
            ],
            'count_user' => count($this->repository->findBy(['status' => Status::Published, '_step' => Step::Pin])),
        ]);
    }


    #[Route('/{id}/edit', name: 'edit', methods: ['POST'])]
    public function update(Request $request, User $user): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new Response(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        $this->userService->updateUser($user, $data);

        return new Response(['message' => 'User updated successfully']);
    }

    #[Route('/{id}', name: 'profile', methods: ['GET'])]
    public function profile(User $user): Response
    {
        return $this->render('pages/user/profile.html.twig', [
            'page_title' => 'Profile',
            'user' => $user
        ]);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws SyntaxError
     * @throws ContainerExceptionInterface
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/modal', name: 'modal', methods: ['GET', 'POST'])]
    public function modal(): JsResponseBuilder
    {
        return $this->js()->modal('pages/user/modal.html.twig', [
            'data' => []
        ]);
    }

    /**
     * @param User $user
     * @return RedirectResponse
     */
    #[Route('/{id}/delete', name: 'delete')]
    public function delete(User $user): RedirectResponse
    {

        $this->em->getRepository(VerificationCode::class)->createQueryBuilder('v')
            ->delete()
            ->where('v.responsible = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();

        $this->em->remove($user);
        $this->em->flush();

        $this->addFlash('success', 'User deleted !');
        return $this->redirectToRoute('admin.user.list');
    }
}
