<?php
  session_start(); // Pour les massages

  // Contenu du formulaire :
  $nom =  htmlentities($_POST['nom']);
  $prenom = htmlentities($_POST['prenom']);
  $email =  htmlentities($_POST['email']);
  $password = htmlentities($_POST['password']);
  $role = isset($_POST['role']) ? (int)$_POST['role'] : 0;
  
  // Validation du role : seuls les rôles 1 (Utilisateur) et 2 (Déménageur) sont autorisés
  // 0 : le compte n'est pas activé,
  // 1 : Utilisateur (Client),
  // 2 : Déménageur (Service de déménagement),
  // 3 : Administrateur (ne peut pas être créé via inscription)
  
  if ($role !== 1 && $role !== 2) {
      $_SESSION['erreur'] = "Type de compte invalide";
      header('Location: inscription.php');
      exit;
  }

  // Option pour bcrypt (voir le lien du cours vers le site de PHP) :
  $options = [
        'cost' => 10,
  ];
  // On crypte le mot de passe
  $password_crypt = password_hash($password, PASSWORD_BCRYPT, $options);

  // Connexion :
  require_once("param.inc.php");
  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if ($mysqli->connect_error) {
    $_SESSION['erreur']="Problème de connexion à la base de données ! &#128557;";
    header('Location: inscription.php');
    exit;
  }

  // Vérifier si l'email existe déjà
  if ($stmt = $mysqli->prepare("SELECT id FROM compte WHERE email = ? LIMIT 1")) {
      $stmt->bind_param("s", $email);
      if ($stmt->execute()) {
          $stmt->store_result();
          if ($stmt->num_rows > 0) {
              $_SESSION['erreur'] = "Email déjà utilisé";
              header('Location: inscription.php');
              exit;
          }
      }
      $stmt->close();
  }


  // Modifier la requête en fonction de la table et/ou des attributs :
  if ($stmt = $mysqli->prepare("INSERT INTO compte(nom, prenom, email, password, role) VALUES (?, ?, ?, ?, ?)")) {

    $stmt->bind_param("ssssi", $nom, $prenom, $email, $password_crypt, $role);
    // Le message est mis dans la session, il est préférable de séparer message normal et message d'erreur.
    if($stmt->execute()) {
        // Requête exécutée correctement 
        $newUserId = $stmt->insert_id;
        $stmt->close();
        
        // 自动登录：设置session并跳转到相应的页面
        $_SESSION['user'] = [
            'id' => $newUserId,
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'role' => $role
        ];
        $_SESSION['message'] = "Inscription réussie ! Bienvenue !";
        
        // 根据角色跳转到相应的页面
        if($role === 1) {
            // 客户 -> 创建公告页面
            header('Location: annonce_nouvelle.php');
        } else if($role === 2) {
            // 搬家工人 -> 我的出价页面
            header('Location: mes_offres.php');
        } else {
            header('Location: index.php');
        }
        exit;
    } else {
        // Il y a eu une erreur
        $_SESSION['erreur'] =  "Impossible d'enregistrer";
        $stmt->close();
        header('Location: inscription.php');
        exit;
    }
  } else {
    $_SESSION['erreur'] = "Erreur lors de la préparation de la requête";
    header('Location: inscription.php');
    exit;
  }


?>