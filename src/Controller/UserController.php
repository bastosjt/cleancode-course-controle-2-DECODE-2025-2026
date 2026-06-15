<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class UserController extends AbstractController
{
    #[Route('/users', name: 'get_user_list', methods:['GET'])]
    public function getUserList(EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $entityManager->getRepository(User::class)->findAll();
        return $this->json(
            $data,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/users', name: 'create_user', methods:['POST'])]
    public function createUser(Request $request,EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $form = $this->createFormBuilder()
            ->add('name', TextType::class, [
                'constraints'=>[
                    new Assert\NotBlank(),
                    new Assert\Length(['min'=>1, 'max'=>255])
                ]
            ])
            ->add('age', NumberType::class, [
                'constraints'=>[
                    new Assert\NotBlank()
                ]
            ])
            ->getForm();

        $form->submit($data);

        if(!$form->isValid()){
            return new JsonResponse('Invalid form', 400);
        }

        if($data['age'] < 21){
            return new JsonResponse('Wrong age', 400);
        }

        $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['name']]);
        if(count($user) > 0){
            return new JsonResponse('User already exists', 400);
        }

        $user = new User();
        $user->setName($data['name']);
        $user->setAge($data['age']);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(
            $user,
            201,
            ['Content-Type' => 'application/json;charset=UTF-8']
        );                    
    }

    #[Route('/user/{id}', name: 'get_user', methods:['GET'])]
    public function getUserWithIdentifiant($id, EntityManagerInterface $entityManager): JsonResponse
    {
        if(ctype_digit($id)){
            $player = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);
            if(count($player) == 1){
                return new JsonResponse(array('name'=>$player[0]->getName(), "age"=>$player[0]->getAge(), 'id'=>$player[0]->getId()), 200);
            }
            return new JsonResponse('Wrong id', 404);
        }
        return new JsonResponse('Wrong id', 404);
    }

    #[Route('/user/{id}', name: 'update_user', methods:['PATCH'])]
    public function updateUser(EntityManagerInterface $entityManager, $id, Request $request): JsonResponse
    {
        $player = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);

        if(count($player) !== 1){
            return new JsonResponse('Wrong id', 404);
        }

        $data = json_decode($request->getContent(), true);
        $form = $this->createFormBuilder()
            ->add('name', TextType::class, array(
                'required'=>false
            ))
            ->add('age', NumberType::class, [
                'required' => false
            ])
            ->getForm();

        $form->submit($data);
        if(!$form->isValid()) {
            return new JsonResponse('Invalid form', 400);
        }

        try {
            foreach($data as $key=>$value){
                switch($key){
                    case 'name':
                        $this->updateUserName($player[0], $value, $entityManager);
                        break;
                    case 'age':
                        $this->updateUserAge($player[0], $value);
                        break;
                }
            }
        } catch(\Exception $e) {
            return new JsonResponse($e->getMessage(), 400);
        }

        $entityManager->flush();

        return new JsonResponse([
            'id'   => $player[0]->getId(),
            'name' => $player[0]->getName(),
            'age'  => $player[0]->getAge(),
        ], 200);
    }

    private function updateUserName(User $user, string $value, EntityManagerInterface $entityManager): void {
        $userExists = $entityManager->getRepository(User::class)->findBy(['name' => $value]);
        if(count($userExists) > 0) {
            throw new \Exception('User name already exists');
        }
        $user->setName($value);
    }

    private function updateUserAge(User $user, int $value): void {
        if($value < 21){
            throw new \Exception('Wrong age');
        }
        $user->setAge($value);
    }

    #[Route('/user/{id}', name: 'delete_user', methods:['DELETE'])]
    public function deleteUser($id, EntityManagerInterface $entityManager): JsonResponse | null
    {
        $player = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);
        if(count($player) == 1){
            try{
                $entityManager->remove($player[0]);
                $entityManager->flush();

                $playerStillExists = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);
    
                if(!empty($playerStillExists)){
                    throw new \Exception("The user has not been deleted");
                }
                return new JsonResponse('', 204);

            }catch(\Exception $e){
                return new JsonResponse($e->getMessage(), 500);
            }
        }
        return new JsonResponse('Wrong id', 404);
    }
}
