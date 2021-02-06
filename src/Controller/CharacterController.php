<?php

namespace App\Controller;

use App\Entity\Character;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/character")
 */
class CharacterController extends AbstractController
{
    /**
     * @Route("/", name="character_index", methods={"GET"})
     */
    public function index(PaginatorInterface $paginator, Request $request, CharacterRepository $characterRepository): Response
    {
        $session = $this->get('session');
        if (isset($session['characterFilter'])) {
            $characters = $this->getDoctrine()
                ->getRepository(Character::class)
                ->listByName($session['characterFilter']);
        } else {
            $characters = $this->getDoctrine()
                ->getRepository(Character::class)
                ->findAll();
        }
        $pagination = $paginator->paginate(
            $characters, $request->query->getInt('page', 1), 10);

        return $this->render('character/index.html.twig', [
            'characters' => $pagination
        ]);
    }

    /**
     * @Route("/new", name="character_new", methods={"GET","POST"})
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
     * @Route("/{id}", name="character_show", methods={"GET"})
     */
    public function show(Character $character): Response
    {
        return $this->render('character/show.html.twig', [
            'character' => $character,
        ]);
    }

    /**
     * @Route("/edit/{id}", name="character_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Character $character): Response
    {
        $form = $this->createForm(CharacterType::class, $character, ['edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            // Recogemos el fichero
            $file = $form->get('picture')->getViewData();
            // Sacamos la extensión del fichero
            $ext = $file->getClientOriginalExtension();
            // Le ponemos un nombre al fichero
            $filename = strtr($formData->getName(), " ", "_"). date_timestamp_get(new \DateTime()) . '.' . $ext;
            // Cogemos el Path desde services.yml parameter
            $filepath = $this->getParameter('brochures_directory');
            // Movemos el arhcivo al path que hemos definido
            $file->move($filepath, $filename);

            $character->setPicture($filename);

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('character_index');
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

//    /**
//     * @Route("/uploadNewImage/{id}", name="character_uploadnewimage", requirements={"id"="\d+"})
//     */
//    public function filter(Request $request, $id)
//    {
//        $form = $this->createForm(Name::class);
//
//        $form->handleRequest($request);
//        if ($form->isSubmitted() && $form->isValid()) {
//            $formData = $form->getData();
//            $this->get('session')->set('characterFilter', $formData['name'])
//
//            return $this->redirectToRoute('character_index');
//        }
//
//        return $this->render('character/render.html.twig', [
//            'character' => $character,
//            'form' => $form->createView(),
//        ]);
//    }
//
//    /**
//     * @Route("/clearfilter", name="character_clearfilter")
//     */
//    public function clearFilter(Request $request)
//    {
//        $this->get('session')->remove('characterFilter');
//
//        return $this->render('character/render.html.twig', [
//            'character' => $character,
//            'form' => $form->createView(),
//        ]);
//    }
}
