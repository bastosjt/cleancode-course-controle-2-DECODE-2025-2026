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
        if($request->getMethod() === 'POST'){
            $data = json_decode($request->getContent(), true);
            $form = $this->createFormBuilder()
                ->add('nom', TextType::class, [
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

            if($form->isValid())
            {
                if($data['age'] > 21){
                    $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
                    if(count($user) === 0){
                        $user = new User();
                        $user->setName($data['nom']);
                        $user->setAge($data['age']);
                        $entityManager->persist($user);
                        $entityManager->flush();

                        return $this->json(
                                    $user,
                                    201,
                                    ['Content-Type' => 'application/json;charset=UTF-8']
                                );                    
                    }
                    return new JsonResponse('Name already exists', 400);
                }
                return new JsonResponse('Wrong age', 400);
            }
            return new JsonResponse('Invalid form', 400);
        }
        return new JsonResponse('Wrong method', 405);
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


        if(count($player) == 1){
            if($request->getMethod() == 'PATCH'){
                $data = json_decode($request->getContent(), true);
                $form = $this->createFormBuilder()
                    ->add('nom', TextType::class, array(
                        'required'=>false
                    ))
                    ->add('age', NumberType::class, [
                        'required' => false
                    ])
                    ->getForm();

                $form->submit($data);
                if($form->isValid()) {

                    foreach($data as $key=>$value){
                        switch($key){
                            case 'nom':
                                $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
                                if(count($user) === 0){
                                    $player[0]->setName($data['nom']);
                                    $entityManager->flush();
                                }else{
                                    return new JsonResponse('Name already exists', 400);
                                }
                                break;
                            case 'age':
                                if($data['age'] > 21){
                                    $player[0]->setAge($data['age']);
                                    $entityManager->flush();
                                }else{
                                    return new JsonResponse('Wrong age', 400);
                                }
                                break;
                        }
                    }
                }else{
                    return new JsonResponse('Invalid form', 400);
                }
            }else{
                $data = json_decode($request->getContent(), true);
                return new JsonResponse('Wrong method', 405);
            }

            return new JsonResponse(array('name'=>$player[0]->getName(), "age"=>$player[0]->getAge(), 'id'=>$player[0]->getId()), 200);
        }
        return new JsonResponse('Wrong id', 404);    
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
