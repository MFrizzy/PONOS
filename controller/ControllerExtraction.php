<?php

require_once File::build_path(array('lib', 'Extraction.php'));
require_once File::build_path(array('model', 'ModelErreurExport.php'));

/**
 * Class ControllerExtraction
 */
class ControllerExtraction
{

    /**
     * @var string
     */
    protected static $object = 'extraction';

    /**
     * Envoie vers la page d'importation du fichier .csv
     */
    public static function extract()
    {
        if (isset($_SESSION['login'])) {
            $view = 'extract';
            $pagetitle = 'Importation des données';
            require_once File::build_path(array('view', 'view.php'));
        } else ControllerUser::connect();

    }

    /**
     * Recupere le fichier .csv envoyé par @see ControllerExtraction::extract()
     *
     * Le fichier récupéré est lu, transformé en array @see Extraction::csvToArray()
     * Puis on rentre les valeurs dans la BDD @see Extraction::ArrayToBDD()
     * Finalement on affiche l'interface de résolution des erreurs @see ControllerExtraction::home()
     */
    public static function extracted()
    {
        if (isset($_SESSION['login'])) {
            if (isset($_FILES['extract'])) {
                $array = Extraction::csvToArray($_FILES['extract']["tmp_name"]);
                Extraction::ArrayToBDD($array);
                ControllerExtraction::home();
            } else ControllerMain::erreur("Veuillez fournir un fichier");
        } else ControllerUser::connect();
    }

    /**
     * Affiche l'interface de résolution des erreurs d'importation
     *
     * Dans cette interface il y a 4 possibilités :
     *
     * - Statut : @see ControllerExtraction::solveStatuts()
     * - Departement Enseignant @see ControllerExtraction::solveDepEns()
     * - Departement Invalide @see ControllerExtraction::solveDepInv()
     * - Autre : TODO
     *
     */
    public static function home()
    {
        if (isset($_SESSION['login'])) {
            $view = 'home';
            $pagetitle = 'Erreurs';
            require_once File::build_path(array('view', 'view.php'));
        } else ControllerUser::connect();
    }

    /**
     * @deprecated
     *
     */
    public static function readAll()
    {
        if (isset($_SESSION['login'])) {
            if (isset($_GET['p'])) {
                $p = intval($_GET['p']);
                if ($p > ModelErreurExport::getNbP()) $p = ModelErreurExport::getNbP();
            } else $p = 1;
            $max = ModelErreurExport::getNbP();
            $tab = ModelErreurExport::selectByPage($p);
            $view = 'error';
            $pagetitle = 'Erreur';
            require_once File::build_path(array("view", "view.php"));
        } else ControllerUser::connect();
    }

    /**
     * @deprecated
     */
    public static function tentative()
    {
        if (isset($_SESSION['login'])) {
            if (isset($_GET['idErreur'])) {
                $erreur = ModelErreurExport::select($_GET['idErreur']);
                if (!$erreur) ControllerMain::erreur("L'erreur n'exite pas..");
                else {
                    if (Extraction::erreurToBD($erreur)) {
                        echo "cban";
                    } else {
                        echo ')=';
                    }
                }
            }
        } else ControllerUser::connect();
    }

    /**
     * Redirection vers les 3 types d'erreurs @see ControllerExtraction::home()
     *
     * Dans cette interface il y a 4 possibilités :
     *
     * - Statut : @see ControllerExtraction::solveStatuts()
     * - Departement Enseignant @see ControllerExtraction::solveDepEns()
     * - Departement Invalide @see ControllerExtraction::solveDepInv()
     * - Autre : TODO
     */
    public static function readAllType()
    {
        if (isset($_SESSION['login'])) {
            if (isset($_POST['typeErreur'])) {
                $redirct = $_POST['typeErreur'];
                switch ($redirct) {
                    case 'statut':
                        ControllerExtraction::solveStatuts();
                        break;
                    case 'departementEns':
                        ControllerExtraction::solveDepEns();
                        break;
                    case 'Département invalide':
                        ControllerExtraction::solveDepInv();
                        break;
                    case 'autre':
                        ControllerMain::erreur("En cours d'implémentation");
                        break;
                }
            } else ControllerMain::erreur("Il manque des informations");
        } else ControllerUser::connect();
    }

    /**
     * Affiche les erreurs liées aux statuts invalides/inexistants
     */
    public static function solveStatuts()
    {
        if (isset($_SESSION['login'])) {
            $statuts = ModelErreurExport::selectAllStatuts();
            if (!$statuts) ControllerMain::erreur("Il n'y a pas de statuts invalides");
            else {
                $modelStatuts = ModelStatutEnseignant::selectAll();
                $view = 'solveStatut';
                $pagetitle = 'Resolution erreurs de statuts';
                require_once File::build_path(array('view', 'view.php'));
            }
        } else ControllerUser::connect();
    }

    /**
     * Affiche les erreurs liées aux Départements des enseignants invalide
     */
    public static function solveDepEns()
    {
        if (isset($_SESSION['login'])) {
            $depEns = ModelErreurExport::selectAllDepEns();
            if (!$depEns) ControllerMain::erreur("Il n'y a pas de départements d'enseignant invalides");
            else {
                $dep = ModelDepartement::selectAll();
                $view = 'solveDepEns';
                $pagetitle = 'Resolution erreurs de statuts';
                require_once File::build_path(array('view', 'view.php'));
            }
        } else ControllerUser::connect();
    }

    /**
     * Affiche les erreurs liées aux Département invalides dans les code d'activitées
     */
    public static function solveDepInv()
    {
        if (isset($_SESSION['login'])) {
            $depInv = ModelErreurExport::selectAllDepInv();
            if (!$depInv) ControllerMain::erreur("Il n'y a pas de départements invalides");
            else {
                $dep = ModelDepartement::selectAll();
                $view = 'solveDepInv';
                $pagetitle = 'Resolution erreurs de statuts';
                require_once File::build_path(array('view', 'view.php'));
            }
        } else ControllerUser::connect();
    }


    /**
     * Résout les erreurs liées aux stage
     *
     * Récupére les informations du formulaire de @see ControllerExtraction::solveStatuts()
     */
    public static function solvedStatuts()
    {
        if (isset($_SESSION['login'])) {
            foreach ($_POST as $cle => $item) {
                /**
                 * @var $statut [0] est le statut
                 * @var $statut [1] est le type statut
                 */
                $cle = str_replace("_", " ", $cle);
                $statut = explode('/', $cle);
                if ($item != 'rien') {
                    $idErreurs = ModelErreurExport::selectIdErreurStatut($statut[0], $statut[1]);
                    if (!$idErreurs) echo 'crack';
                    else {
                        if ($item == 'nouveau') {
                            // Créer nouveau statut
                            ModelStatutEnseignant::save(array(
                                'statut' => $statut[0],
                                'typeStatut' => $statut[1]
                            ));
                        } else {
                            // Changer le statut des erreurs par celui selectionné par l'utilisateur
                            foreach ($idErreurs as $idErreur) {
                                ModelErreurExport::update(array(
                                    'idErreur' => $idErreur['idErreur'],
                                    'statut' => $statut[0],
                                    'typeStatut' => $statut[1]
                                ));
                            }
                        }
                        // Refaire entrer les valeurs dans la bdd
                        foreach ($idErreurs as $idErreur) {
                            Extraction::erreurToBD(ModelErreurExport::select($idErreur['idErreur']));
                        }
                    }
                }
            }
            ControllerExtraction::solveStatuts();
        } else ControllerUser::connect();
    }
}