<?php
  session_start();
  if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 1) {
    header('Location: connexion.php');
    exit;
  }
  require_once('param.inc.php');

  $clientId = (int)$_SESSION['user']['id'];

  $titre = trim($_POST['titre'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $date_debut = $_POST['date_debut'] ?? '';
  $ville_depart = trim($_POST['ville_depart'] ?? '');
  $ville_arrivee = trim($_POST['ville_arrivee'] ?? '');
  $depart_type = $_POST['depart_type'] ?? 'maison';
  $depart_etage = $_POST['depart_etage'] !== '' ? (int)$_POST['depart_etage'] : NULL;
  $depart_ascenseur = isset($_POST['depart_ascenseur']) ? 1 : 0;
  $arrivee_type = $_POST['arrivee_type'] ?? 'maison';
  $arrivee_etage = $_POST['arrivee_etage'] !== '' ? (int)$_POST['arrivee_etage'] : NULL;
  $arrivee_ascenseur = isset($_POST['arrivee_ascenseur']) ? 1 : 0;
  $volume_m3 = $_POST['volume_m3'] !== '' ? (float)$_POST['volume_m3'] : NULL;
  $poids_kg = $_POST['poids_kg'] !== '' ? (int)$_POST['poids_kg'] : NULL;
  $nb_demenageurs = $_POST['nb_demenageurs'] !== '' ? (int)$_POST['nb_demenageurs'] : NULL;

  if($titre === '' || $date_debut === '' || $ville_depart === '' || $ville_arrivee === '') {
    $_SESSION['erreur'] = "Champs obligatoires manquants";
    header('Location: annonce_nouvelle.php');
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if ($mysqli->connect_error) {
    $_SESSION['erreur'] = "Problème de BDD";
    header('Location: annonce_nouvelle.php');
    exit;
  }

  $sql = "INSERT INTO annonce (client_id, titre, description, date_debut, ville_depart, ville_arrivee, depart_type, depart_etage, depart_ascenseur, arrivee_type, arrivee_etage, arrivee_ascenseur, volume_m3, poids_kg, nb_demenageurs) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
  if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param(
      "issssssii ssii i",
      $clientId,
      $titre,
      $description,
      $date_debut,
      $ville_depart,
      $ville_arrivee,
      $depart_type,
      $depart_etage,
      $depart_ascenseur,
      $arrivee_type,
      $arrivee_etage,
      $arrivee_ascenseur,
      $volume_m3,
      $poids_kg,
      $nb_demenageurs
    );
    // 修正: 为了避免空格影响，重新按段绑定
  }

  // 由于 bind_param 类型串需紧凑，我们重新准备并绑定
  if($stmt2 = $mysqli->prepare($sql)){
    // client_id(i), titre(s), description(s), date_debut(s), ville_depart(s), ville_arrivee(s), depart_type(s), depart_etage(i), depart_ascenseur(i), arrivee_type(s), arrivee_etage(i), arrivee_ascenseur(i), volume_m3(d), poids_kg(i), nb_demenageurs(i)
    $stmt2->bind_param(
      "issssssiisidii",
      $clientId,
      $titre,
      $description,
      $date_debut,
      $ville_depart,
      $ville_arrivee,
      $depart_type,
      $depart_etage,
      $depart_ascenseur,
      $arrivee_type,
      $arrivee_etage,
      $arrivee_ascenseur,
      $volume_m3,
      $poids_kg,
      $nb_demenageurs
    );
    if($stmt2->execute()){
      $annonceId = $stmt2->insert_id;

      // 上传图片（可选）
      if(isset($_FILES['images']) && is_array($_FILES['images']['name'])){
        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
        if(!is_dir($uploadDir)){
          @mkdir($uploadDir, 0777, true);
        }
        for($i=0; $i<count($_FILES['images']['name']); $i++){
          if($_FILES['images']['error'][$i] === UPLOAD_ERR_OK){
            $tmp = $_FILES['images']['tmp_name'][$i];
            $name = basename($_FILES['images']['name'][$i]);
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $safeName = 'annonce_'.$annonceId.'_'.time().'_'.$i.'.'.strtolower($ext);
            $dest = $uploadDir . DIRECTORY_SEPARATOR . $safeName;
            if(@move_uploaded_file($tmp, $dest)){
              $relPath = 'uploads/'.$safeName;
              if($stmtImg = $mysqli->prepare("INSERT INTO annonce_image(annonce_id, path) VALUES (?,?)")){
                $stmtImg->bind_param("is", $annonceId, $relPath);
                $stmtImg->execute();
                $stmtImg->close();
              }
            }
          }
        }
      }

      $_SESSION['message'] = "Annonce publiée";
      header('Location: annonce_detail.php?id='.$annonceId);
      exit;
    } else {
      $_SESSION['erreur'] = "Impossible d'enregistrer l'annonce";
      header('Location: annonce_nouvelle.php');
      exit;
    }
  }

  $_SESSION['erreur'] = "Erreur interne";
  header('Location: annonce_nouvelle.php');
  exit;
?>


