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

        $joueurco = $this->getUser()->getUsername();

        $mail = $this->getUser()->getEmail();

        $gagne = $this->getUser()->getGagne();

        $perdu = $this->getUser()->getPerdu();



        return $this->render('partie/index.html.twig', [
            'joueurs' => $joueurs,
            'parties' => $parties,
            'gagne' => $gagne,
            'perdu' => $perdu,
            'joueurco' => $joueurco,
            'mail' => $mail,


        ]);
    }

    /**
     * @Route("/nouvelle-partie", name="nouvelle_partie")
     */
    public function nouvellePartie(JoueurRepository $joueurRepository, CarteRepository $carteRepository, Request $request)
    {

        if (empty($joueurRepository->find($request->get('adversaire')))) {
            return $this->redirectToRoute('partie');
        }

        else {

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

            $mainJ1 = [];
            $mainJ2 = [];
            for ($i = 0; $i < 6; $i++) {
                $mainJ1[] = array_pop($pioche);
                $mainJ2[] = array_pop($pioche);
            }

            // tuile

            $tuile = [];
            for ($i = 1; $i < 10; $i++) {
                $tuile[] = $i;
            }

            $tuileJ1 = [];
            for ($i = 1; $i < 10; $i++) {
                $tuileJ1[] = 0;
            }

            $tuileJ2 = [];
            for ($i = 1; $i < 10; $i++) {
                $tuileJ2[] = 0;
            }

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

            for ($i = 1; $i <= 9; $i++) {
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

    }


    /**
     * @param Request $request
     * @Route("/depose_carte/{idPartie}", name="depose_carte")
     */
    public function deposeCarte(Request $request, Partie $idPartie, PartieRepository $partieRepository, CarteRepository $carteRepository, JoueurRepository $joueurRepository) {
       $carte=$request->request->get('carte');
       $colonne=$request->request->get('colonne');
       $ligne=$request->request->get('ligne');

        $partie = $partieRepository->find($idPartie);
        $joueurCo1 = $partie->getJoueur1();

        // Récupérer les tuiles
        $tuile = $idPartie->getTuile();


       //Joueur 1 ou joueur 2
        $partie = $partieRepository->find($idPartie);
        $leuser = $this->getUser()->getId();

        if ($leuser == $partie->getJoueur1()->getId()) {
            $carteterrain = $partieRepository->find($idPartie);

            $terrainJ1 = $idPartie->getTerrainJ1();
            $terrainJ1[$colonne][$ligne] = $carte;

            $terrainJ2 = $idPartie->getTerrainJ2();

            $tuileJ1 = $idPartie->getTuileJ1();
            $tuileJ2 = $idPartie->getTuileJ2();

            $idPartie->setTerrainJ1($terrainJ1);

            $mainJ1 = $partie->getMainJ1();
        }

        else {
            $carteterrain = $partieRepository->find($idPartie);

            $terrainJ1 = $idPartie->getTerrainJ2();

            $terrainJ2 = $idPartie->getTerrainJ1();

            $tuileJ1 = $idPartie->getTuileJ2();
            $tuileJ2 = $idPartie->getTuileJ1();

            $terrainJ1[$colonne][$ligne] = $carte;
            $idPartie->setTerrainJ2($terrainJ1);

            $mainJ1 = $partie->getMainJ2();
        }

        // Retirer carte main


        $mainJ1 = array_diff($mainJ1, [$carte]);
        $pioche = $partie->getPioche();
        shuffle($pioche);
        $mainJ1[] =  array_pop($pioche);
        $partie->setPioche($pioche);



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

            $allcarte = $carteRepository->findAll();

            $cartepose = $allcarte[$carte-1]->getCouleur();

            var_dump($cartepose);

            if ($terrainJ1[$colonne][1]!=0 &&  $terrainJ1[$colonne][2]!=0 && $terrainJ1[$colonne][3]!=0){

                $forceJ2 = 0;

                for ($ligne = 1; $ligne <= 3; $ligne++) {
                    $couleur[] = $allcarte[$terrainJ1[$colonne][$ligne]-1]->getCouleur();
                    $puissance[] = $allcarte[$terrainJ1[$colonne][$ligne]-1]->getPuissance();
                }
                sort($puissance);
                var_dump($couleur);
                var_dump($puissance);

                // Si la colonne adversaire est complète ou nom

                if ($terrainJ2[$colonne][1]!=0 &&  $terrainJ2[$colonne][2]!=0 && $terrainJ2[$colonne][3]!=0) {


                    for ($ligne = 1; $ligne <= 3; $ligne++) {
                        $couleurJ2[] = $allcarte[$terrainJ2[$colonne][$ligne]-1]->getCouleur();
                        $puissanceJ2[] = $allcarte[$terrainJ2[$colonne][$ligne]-1]->getPuissance();
                    }

                    sort($puissanceJ2);


                    //Suite Couleur J2

                    if ($couleurJ2[0]==$couleurJ2[1] && $couleurJ2[1]== $couleurJ2[2] && $puissanceJ2[2] == $puissanceJ2[1]+1 && $puissanceJ2[1] == $puissanceJ2[0]+1) {
                        var_dump("C'est une suite couleur pour le J2!");

                        $forceJ2 = 5;
                    }

                    //Suite J2

                    elseif ($puissanceJ2[2] == $puissanceJ2[1]+1 && $puissanceJ2[1] == $puissanceJ2[0]+1) {
                        var_dump("C'est une suite pour le J2!");

                        $forceJ2 = 2;
                    }

                    //Couleur J2

                    elseif ($couleurJ2[0]==$couleurJ2[1] && $couleurJ2[1]== $couleurJ2[2]){
                        var_dump("c'est une couleur pour le J2!");

                        $forceJ2 = 3;
                    }

                    // Brelan J2

                    elseif ($puissanceJ2[0] == $puissanceJ2[1] && $puissanceJ2[1] == $puissanceJ2[2]){
                        var_dump("C'est un brelan pour le J2!");
                        $forceJ2 = 4;
                    }

                    //Somme J2

                    else {
                        $forceJ2 = 1;
                    }


                }


                //Suite Couleur J1

                if ($couleur[0]==$couleur[1] && $couleur[1]== $couleur[2] && $puissance[2] == $puissance[1]+1 && $puissance[1] == $puissance[0]+1) {
                    var_dump("C'est une suite couleur!");

                    $forceJ1 = 5;
                }

                //SUITE J1


                elseif ($puissance[2] == $puissance[1]+1 && $puissance[1] == $puissance[0]+1) {
                    var_dump("C'est une suite!");

                    $forceJ1 = 2;

                }


                //Couleur J1

                elseif ($couleur[0]==$couleur[1] && $couleur[1]== $couleur[2]){
                    var_dump("c'est une couleur!");

                    $forceJ1 = 3;
                }

                // Brelan J1

                elseif ($puissance[0] == $puissance[1] && $puissance[1] == $puissance[2]){
                    var_dump("C'est un brelan!");
                    $forceJ1 = 4;
                }

                //Somme

                else {
                    var_dump("c'est une somme");
                    $forceJ1 = 1;
                }


                if ($forceJ2 != 0 ){
                    if ($forceJ1 > $forceJ2){
                        //Attribution de la tuile
                        var_dump('La tuile est pour le J1');
                        $tuileJ1[$colonne-1] = $tuile[$colonne-1];
                        $tuile[$colonne-1] = 0;


                    }
                    if ($forceJ1 < $forceJ2) {
                        var_dump('La tuile est pour le J2');
                        $tuileJ2[$colonne-1] = $tuile[$colonne-1];
                        $tuile[$colonne-1] = 0;
                    }
                    if ($forceJ1 == $forceJ2){
                        $sommeJ1 = $puissance[0] + $puissance[1] + $puissance[2];
                        $sommeJ2 = $puissanceJ2[0] + $puissanceJ2[1] + $puissanceJ2[2];
                        if ($sommeJ1 > $sommeJ2) {
                            //Attribution de la tuile plus chiante si les deux joueurs on la même puissance
                            var_dump("La tuile est pour le J1 car sa somme est plus forte");
                            $tuileJ1[$colonne-1] = $tuile[$colonne-1];
                            $tuile[$colonne-1] = 0;
                        }
                        if ($sommeJ1 < $sommeJ2) {
                            var_dump("La tuile est pour le J2 car sa somme est plus forte");
                            $tuileJ2[$colonne-1] = $tuile[$colonne-1];
                            $tuile[$colonne-1] = 0;
                        }

                        if ($sommeJ1 == $sommeJ2) {
                            var_dump("La tuile est pour le J2 car il a posé ses cartes en premier!");
                            $tuileJ2[$colonne-1] = $tuile[$colonne-1];
                            $tuile[$colonne-1] = 0;
                        }
                    }
                }

                $idPartie->setTuile($tuile);
                if ($leuser == $partie->getJoueur1()->getId()) {
                    $idPartie->setTuileJ1($tuileJ1);
                    $idPartie->setTuileJ2($tuileJ2);
                }

                else {
                    $idPartie->setTuileJ1($tuileJ2);
                    $idPartie->setTuileJ2($tuileJ1);
                }


            }











        $joueur1 = $joueurRepository->find($idPartie->getJoueur1()->getId());
        $joueur2 = $joueurRepository->find($idPartie->getJoueur2()->getId());


        for ($c = 0; $c <= 6; $c++) {
            if ($tuileJ1[$c]!=0 && $tuileJ1[$c+1]!=0 && $tuileJ1[$c+2]!=0) {

                if ($joueurCo1->getId()==$this->getUser()->getId()) {
                    $gagnant = $idPartie->getJoueur1()->getId();
                    $idPartie->setGagnantId($gagnant);
                    $gagne = $joueur1->getGagne();
                    $joueur1->setGagne($gagne+1);
                    $perdu = $joueur2->getPerdu();
                    $joueur2->setPerdu($perdu+1);

                } else {
                    $gagnant = $idPartie->getJoueur2()->getId();
                    $idPartie->setGagnantId($gagnant);
                    $gagne = $joueur2->getGagne();
                    $joueur2->setGagne($gagne+1);
                    $perdu = $joueur1->getPerdu();
                    $joueur1->setPerdu($perdu+1);
                }


            }
            if ($tuileJ2[$c]!=0 && $tuileJ2[$c+1]!=0 && $tuileJ2[$c+2]!=0) {

                if ($joueurCo1->getId()==$this->getUser()->getId()) {
                    $gagnant = $idPartie->getJoueur2()->getId();
                    $idPartie->setGagnantId($gagnant);
                    $gagne = $joueur1->getGagne();
                    $joueur1->setGagne($gagne+1);
                    $perdu = $joueur2->getPerdu();
                    $joueur2->setPerdu($perdu+1);
                } else {
                    $gagnant = $idPartie->getJoueur1()->getId();
                    $idPartie->setGagnantId($gagnant);
                    $gagne = $joueur2->getGagne();
                    $joueur2->setGagne($gagne+1);
                    $perdu = $joueur1->getPerdu();
                    $joueur1->setPerdu($perdu+1);
                }

            }
        }


        $nbtuile1 = array_count_values($tuileJ1);
        $nbtuile2 = array_count_values($tuileJ2);



        if ($nbtuile1[0] == 4) {
            if ($joueurCo1->getId()==$this->getUser()->getId()) {
                $gagnant = $idPartie->getJoueur1()->getId();
                $idPartie->setGagnantId($gagnant);
                $gagne = $joueur1->getGagne();
                $joueur1->setGagne($gagne+1);
                $perdu = $joueur2->getPerdu();
                $joueur2->setPerdu($perdu+1);

            } else {
                $gagnant = $idPartie->getJoueur2()->getId();
                $idPartie->setGagnantId($gagnant);
                $gagne = $joueur2->getGagne();
                $joueur2->setGagne($gagne+1);
                $perdu = $joueur1->getPerdu();
                $joueur1->setPerdu($perdu+1);

            }
        } elseif ($nbtuile2[0] == 4) {
            if ($joueurCo1->getId()==$this->getUser()->getId()) {
                $gagnant = $idPartie->getJoueur2()->getId();
                $idPartie->setGagnantId($gagnant);
                $gagne = $joueur1->getGagne();
                $joueur1->setGagne($gagne+1);
                $perdu = $joueur2->getPerdu();
                $joueur2->setPerdu($perdu+1);
            } else {
                $gagnant = $idPartie->getJoueur1()->getId();
                $idPartie->setGagnantId($gagnant);
                $gagne = $joueur2->getGagne();
                $joueur2->setGagne($gagne+1);
                $perdu = $joueur1->getPerdu();
                $joueur1->setPerdu($perdu+1);
            }
        }









        // Fin condition d'attribution
        $setTour = $partieRepository->find($idPartie);
        $setTour->setTour($tour);


        $em = $this->getDoctrine()->getManager();
        $em->persist($joueur1);
        $em->persist($joueur2);
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
        $joueur1=$partie->getJoueur1()->getId();
        $joueur2=$partie->getJoueur2()->getId();

        if ($joueurCo1->getId()==$this->getUser()->getId()) {
            $mainJ1 = $partie->getMainJ1();
            $mainJ2 = $partie->getMainJ2();
            $terrainJ1 = $partie->getTerrainJ1();
            $terrainJ2 = $partie->getTerrainJ2();
            $tuileJ1 = $partie->getTuileJ1();
            $tuileJ2 = $partie->getTuileJ2();
        } else {
            $mainJ1 = $partie->getMainJ2();
            $mainJ2 = $partie->getMainJ1();
            $terrainJ2 = $partie->getTerrainJ1();
            $terrainJ1 = $partie->getTerrainJ2();
            $tuileJ2 = $partie->getTuileJ1();
            $tuileJ1 = $partie->getTuileJ2();
        }

        $legagnant = $idPartie->getGagnantId();

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
            'gagnant' => $legagnant,

        ]);
    }


}
