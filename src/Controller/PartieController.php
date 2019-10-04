<?php

namespace App\Controller;

use App\Entity\Partie;
use App\Repository\CarteRepository;
use App\Repository\JoueurRepository;
use App\Repository\PartieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;


/**
 * @Route("/partie")
 */
class PartieController extends AbstractController
{
    /**
     * @Route("/", name="")
     */
    public function index(JoueurRepository $joueurRepository, PartieRepository $partieRepository)
    {

        $joueurs = $joueurRepository->findAll();

        $parties = $this->getUser()->getToutesMesParties();


        return $this->render('partie/index.html.twig', [
            'joueurs' => $joueurs,
            'parties' => $parties
        ]);
    }

    /**
     * @Route("/nouvelle-partie", name="nouvelle_partie")
     */
    public function nouvellePartie(JoueurRepository $joueurRepository, CarteRepository $carteRepository, Request $request)
    {
        $joueur1 = $this->getUser();
        $joueur2 = $joueurRepository->find($request->get('adversaire'));


        $cartes = $carteRepository->findAll();

        $partie = new Partie();
        $partie->setJoueur1($joueur1);
        $partie->setJoueur2($joueur2);

        $pioche = [];
        foreach ($cartes as $carte) {
            $pioche[] = $carte->getId();
        }
        shuffle($pioche);

        $mainJ1= [];
        $mainJ2= [];
        for ($i=0; $i < 7; $i++) {
            $mainJ1[] =  array_pop($pioche);
            $mainJ2[] =  array_pop($pioche);
        }
        dump($mainJ1);
        dump($mainJ2);

        $partie->setMainJ1($mainJ1);
        $partie->setMainJ2($mainJ2);
        $partie->setPioche($pioche);
        $partie->setTypeVictoire('');

        $partie->setDateVictoire(new \DateTime('now'));
        $partie->setTour(1);

        $em = $this->getDoctrine()->getManager();
        $em->persist($partie);
        $em->flush();

        return $this->redirectToRoute('afficher_partie', ['idPartie' => $partie->getId()]);



    }


    /**
     * @param Request $request
     * @Route("/depose_carte/{idPartie}", name="depose_carte")
     */
    public function deposeCarte(Request $request, Partie $idPartie, PartieRepository $partieRepository) {
//       $carte=$request->request->get('carte');
//       $colonne=$request->request->get('colonne');
//       $ligne=$request->request->get('ligne');

       $carteterrain = $partieRepository->find($idPartie);


       dump($cartepose);
       $cartepose[] .= $carteterrain->getTerrainJ1();

        $position = $partieRepository->find($idPartie);
        $position->setTerrainJ1($cartepose);

        $tour = $carteterrain->getTour();

        dump($tour);

        if ($tour == 1)
        {
            $tour = 2;
        }   else {
            $tour = 1;
        }

        dump($tour);

        $setTour = $partieRepository->find($idPartie);
        $setTour->setTour($tour);


        $em = $this->getDoctrine()->getManager();
        $em->persist($position);
        $em->persist($setTour);
        $em->flush();


    }




    /**
     * @Route("/{idPartie}", name="afficher_partie")
     */
    public function afficherPartie(CarteRepository $carteRepository, Partie $idPartie, PartieRepository $partieRepository, AuthenticationUtils $authenticationUtils)
    {
        //recupérer l'utilisateur connecté
        $lastUsername = $authenticationUtils->getLastUsername();


        //recupérer les deux joueurs
        $partie = $partieRepository->find($idPartie);
        $joueurCo1 = $partie->getJoueur1();

        //recupérer les mains

        if ($joueurCo1==$lastUsername) {
            $joueur1=$partie->getJoueur1();
            $joueur2=$partie->getJoueur2();
            $mainJ1 = $partie->getMainJ1();
            $mainJ2 = $partie->getMainJ2();
        } else {
            $joueur1=$partie->getJoueur2();
            $joueur2=$partie->getJoueur1();
            $mainJ1 = $partie->getMainJ2();
            $mainJ2 = $partie->getMainJ1();
        }


        //récupérer le tour actuel
        $tour = $partieRepository->find($idPartie);
        $setTour = $tour->getTour();

        //récupérer les cartes
        $cartes = $carteRepository->findAll();
        $tCartes = [];
        foreach ($cartes as $carte)
        {
            $tCartes[$carte->getId()] = $carte;
        }




        //envoyer les valeurs dans la vue
        return $this->render('partie/afficher-partie.html.twig', [
            'partie' => $idPartie,
            'cartes' => $tCartes,
            'tour' => $setTour,
            'user' => $lastUsername,
            'joueur1' => $joueur1,
            'joueur2' => $joueur2,
            'mainJ2' => $mainJ2,
            'mainJ1' => $mainJ1

        ]);
    }


}
