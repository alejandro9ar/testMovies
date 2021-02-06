<?php

namespace App\Controller;

use App\Entity\Character;
use App\Form\CharacterFilterType;
use App\Form\CharacterType;
use App\Form\FileUploadType;
use App\Repository\CharacterRepository;
use Cassandra\Timestamp;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use PhpParser\Node\Name;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Json;

/**
 * @Route("/character")
 */
class CharacterController extends AbstractController
{
    /**
     * @Route("/", name="character_index")
     */
    public function index(PaginatorInterface $paginator, Request $request)
    {

        return $this->render('character/index.html.twig',[
            'page' => $request->get('page') ? $request->get('page') : 1
        ]);
    }

    /**
     * @Route("/list", name="character_list")
     */
    public function list(PaginatorInterface $paginator, Request $request)
    {
        $page = 1;
        if($request->get('page') != null){
            $page = $request->get('page');
        }
        if($this->get('session')->get('characterFilter')) {
            $characters = $this->getDoctrine()
                ->getRepository(Character::class)
                ->listByName($this->get('session')->get('characterFilter'));
        } else {
            $characters = $this->getDoctrine()
                ->getRepository(Character::class)
                ->findAll();
        }

        $pagination = $paginator->paginate(
            $characters, $page, 10);

        return $this->render('character/list.html.twig', [
            'characters' => $pagination
        ]);
    }

    /**
     * @Route("/new", name="character_new")
     */
    public function new(Request $request): Response
    {
        $character = new Character();
        $form = $this->createForm(CharacterType::class, $character);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            // Recogemos el fichero
            $file = $form->get('picture')->getViewData();
            // Sacamos la extensión del fichero
            $ext = $file->getClientOriginalExtension();
            // Le ponemos un nombre al fichero
            $filename = strtr($formData->getName(), " ", "_") . date_timestamp_get(new \DateTime()) . '.' . $ext;
            // Cogemos el Path desde services.yml parameter
            $filepath = $this->getParameter('brochures_directory');
            // Movemos el arhcivo al path que hemos definido
            $file->move($filepath, $filename);

            $character->setPicture($filename);
            $this->getDoctrine()->getManager()->persist($character);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('character_index');
        }

        return $this->render('character/new.html.twig', [
            'character' => $character,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="character_show", requirements={"id"="\d+"})
     */
    public function show(Character $character): Response
    {
        return $this->render('character/show.html.twig', [
            'character' => $character,
        ]);
    }

    /**
     * @Route("/edit/{id}", name="character_edit")
     */
    public function edit(Request $request, Character $character): Response
    {
        $form = $this->createForm(CharacterType::class, $character, ['edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('character_show', [
                'id' => $character->getId(),
            ]);
        }

        return $this->render('character/edit.html.twig', [
            'character' => $character,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/delete/{id}", name="character_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Character $character): Response
    {
        if ($this->isCsrfTokenValid('delete'.$character->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($character);
            $entityManager->flush();
        }

        return $this->redirectToRoute('character_index');
    }

    /**
     * @Route("/uploadNewImage/{id}", name="character_uploadnewimage", requirements={"id"="\d+"})
     */
    public function uploadNewImage(Request $request, $id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $character = $entityManager->getRepository(Character::class)->find($id);
        $form = $this->createForm(FileUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            // Recogemos el fichero
            $file = $formData['picture'];
            // Sacamos la extensión del fichero
            $ext = $file->getClientOriginalExtension();
            // Le ponemos un nombre al fichero
            $filename = strtr($character->getName(), " ", "_"). date_timestamp_get(new \DateTime()) . '.' . $ext;
            // Cogemos el Path desde services.yml parameter
            $filepath = $this->getParameter('brochures_directory');
            // Movemos el arhcivo al path que hemos definido
            $file->move($filepath, $filename);

            $character->setPicture($filename);

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('character_show',['id' => $id]);
        }

        return $this->render('character/upload_new_file.html.twig', [
            'character' => $character,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/filter", name="character_filter")
     */
    public function filter(Request $request)
    {
        $sessionVariable = '';
        if ($this->get('session')->get('characterFilter')) {
            $sessionVariable = $this->get('session')->get('characterFilter');
        }
        $form = $this->createForm(CharacterFilterType::class, ['name' => $sessionVariable]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $this->get('session')->set('characterFilter', $formData['name']);

            return $this->redirectToRoute('character_index', [
                'page' => 1
            ]);
        }

        return $this->render('character/filter.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/clearfilter", name="character_clearfilter")
     */
    public function clearFilter(Request $request)
    {
        $this->get('session')->remove('characterFilter');

        return $this->redirectToRoute('character_index', [
            'page' => 1
        ]);
    }
}
