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
     * @Route("/", name="partie")
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
        for ($i=0; $i < 6; $i++) {
            $mainJ1[] =  array_pop($pioche);
            $mainJ2[] =  array_pop($pioche);
        }
        dump($mainJ1);
        dump($mainJ2);

        // tuile

        $tuile = [];
        for ($i=1; $i < 10; $i++) {
            $tuile[] = $i;
        }

        $tuileJ1 = [];
        for ($i=1; $i < 10; $i++) {
            $tuileJ1[] = 0;
        }

        $tuileJ2 = [];
        for ($i=1; $i < 10; $i++) {
            $tuileJ2[] = $i;
        }

        var_dump($tuile);

        $partie->setTuileJ1($tuileJ1);
        $partie->setTuileJ2($tuileJ2);
        $partie->setTuile($tuile);
        $partie->setMainJ1($mainJ1);
        $partie->setMainJ2($mainJ2);
        $partie->setPioche($pioche);
        $partie->setTypeVictoire('');

        $partie->setDateVictoire(new \DateTime('now'));
        $partie->setTour(1);

        $terrainJ1 = [];
        $terrainJ2 = [];

        for($i = 1; $i <= 9; $i++) {
            $terrainJ1[$i][1] = 0;
            $terrainJ1[$i][2] = 0;
            $terrainJ1[$i][3] = 0;
            $terrainJ2[$i][1] = 0;
            $terrainJ2[$i][2] = 0;
            $terrainJ2[$i][3] = 0;
        }

        $partie->setTerrainJ1($terrainJ1);
        $partie->setTerrainJ2($terrainJ2);

        $em = $this->getDoctrine()->getManager();
        $em->persist($partie);
        $em->flush();

        return $this->redirectToRoute('afficher_partie', ['idPartie' => $partie->getId()]);



    }


    /**
     * @param Request $request
     * @Route("/depose_carte/{idPartie}", name="depose_carte")
     */
    public function deposeCarte(Request $request, Partie $idPartie, PartieRepository $partieRepository, CarteRepository $carteRepository) {
       $carte=$request->request->get('carte');
       $colonne=$request->request->get('colonne');
       $ligne=$request->request->get('ligne');


       //Joueur 1 ou joueur 2
        $partie = $partieRepository->find($idPartie);
        $leuser = $this->getUser()->getId();

        if ($leuser == $partie->getJoueur1()->getId()) {
            $carteterrain = $partieRepository->find($idPartie);

            $terrainJ1 = $idPartie->getTerrainJ1();
            $terrainJ1[$colonne][$ligne] = $carte;
            $idPartie->setTerrainJ1($terrainJ1);

            $mainJ1 = $partie->getMainJ1();
        }

        else {
            $carteterrain = $partieRepository->find($idPartie);

            $terrainJ2 = $idPartie->getTerrainJ2();
            $terrainJ2[$colonne][$ligne] = $carte;
            $idPartie->setTerrainJ2($terrainJ2);

            $mainJ1 = $partie->getMainJ2();
        }

        // Retirer carte main
        var_dump($mainJ1);

        $mainJ1 = array_diff($mainJ1, [$carte]);
        $pioche = $partie->getPioche();
        shuffle($pioche);
        $mainJ1[] =  array_pop($pioche);
        $partie->setPioche($pioche);
        var_dump($mainJ1);
        var_dump(count($pioche));


        if ($leuser == $partie->getJoueur1()->getId()) {
            $newmain=$partie->setMainJ1($mainJ1);
        }

        else {
            $newmain=$partie->setMainJ2($mainJ1);
        }


        // Gestion tour

        $tour = $carteterrain->getTour();


        if ($tour == 1)
        {
            $tour = 2;
        }   else {
            $tour = 1;
        }

        // Condition d'attribution des tuiles



        $setTour = $partieRepository->find($idPartie);
        $setTour->setTour($tour);


        $em = $this->getDoctrine()->getManager();
        $em->persist($idPartie);
        $em->persist($setTour);
        $em->persist($newmain);
        $em->persist($partie);
        $em->flush();


    }




    /**
     * @Route("/{idPartie}", name="afficher_partie")
     */
    public function afficherPartie(CarteRepository $carteRepository, Partie $idPartie, PartieRepository $partieRepository, AuthenticationUtils $authenticationUtils)
    {
        //recupérer les deux joueurs
        $partie = $partieRepository->find($idPartie);
        $joueurCo1 = $partie->getJoueur1();

        //recupérer les mains


        if ($joueurCo1->getId()==$this->getUser()->getId()) {
            $joueur1=$partie->getJoueur1()->getId();
            $joueur2=$partie->getJoueur2()->getId();
            $mainJ1 = $partie->getMainJ1();
            $mainJ2 = $partie->getMainJ2();
            $terrainJ1 = $partie->getTerrainJ1();
            $terrainJ2 = $partie->getTerrainJ2();
            $tuileJ1 = $partie->getTuileJ1();
            $tuileJ2 = $partie->getTuileJ2();
        } else {
            $joueur2=$partie->getJoueur2()->getId();
            $joueur1=$partie->getJoueur1()->getId();
            $mainJ1 = $partie->getMainJ2();
            $mainJ2 = $partie->getMainJ1();
            $terrainJ2 = $partie->getTerrainJ1();
            $terrainJ1 = $partie->getTerrainJ2();
            $tuileJ2 = $partie->getTuileJ1();
            $tuileJ1 = $partie->getTuileJ2();
        }


        //récupérer le tour actuel
        $tour = $partieRepository->find($idPartie);
        $setTour = $tour->getTour();

        //recuperer les tuiles
        $tuile = $partie->getTuile();
        //récupérer les cartes
        $cartes = $carteRepository->findAll();
        $tCartes = [];
        foreach ($cartes as $carte)
        {
            $tCartes[$carte->getId()] = $carte;
        }

        $leuser = $this->getUser()->getId();

        //envoyer les valeurs dans la vue
        return $this->render('partie/afficher-partie.html.twig', [
            'partie' => $idPartie,
            'cartes' => $tCartes,
            'tour' => $setTour,
            'user' => $leuser,
            'joueur1' => $joueur1,
            'joueur2' => $joueur2,
            'mainJ2' => $mainJ2,
            'mainJ1' => $mainJ1,
            'terrainJ1' => $terrainJ1,
            'terrainJ2' => $terrainJ2,
            'tuile' => $tuile,
            'tuileJ1' => $tuileJ1,
            'tuileJ2' => $tuileJ2,

        ]);
    }


}
