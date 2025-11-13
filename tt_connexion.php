<?php
  session_start();
  require_once("param.inc.php");

  $email = isset($_POST['email']) ? trim($_POST['email']) : '';
  $password = isset($_POST['password']) ? $_POST['password'] : '';
  $selected_role = isset($_POST['role']) ? (int)$_POST['role'] : 0;

  if ($email === '' || $password === '' || $selected_role === 0) {
      $_SESSION['erreur'] = "Tous les champs sont requis";
      header('Location: connexion.php');
      exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if ($mysqli->connect_error) {
      $_SESSION['erreur'] = "Problème de connexion à la base de données";
      header('Location: connexion.php');
      exit;
  }

  // 检查 actif 字段是否存在
  $checkActif = $mysqli->query("SHOW COLUMNS FROM compte LIKE 'actif'");
  $hasActifField = $checkActif && $checkActif->num_rows > 0;
  
  // 构建查询语句
  $sql = "SELECT id, nom, prenom, email, password, role";
  if($hasActifField) {
    $sql .= ", actif";
  }
  $sql .= " FROM compte WHERE email = ? AND role = ? LIMIT 1";
  
  if ($stmt = $mysqli->prepare($sql)) {
      $stmt->bind_param("si", $email, $selected_role);
      if ($stmt->execute()) {
          $result = $stmt->get_result();
          if ($row = $result->fetch_assoc()) {
              // 检查账户是否被停用
              if($hasActifField && isset($row['actif']) && $row['actif'] == 0){
                $_SESSION['erreur'] = "Votre compte a été désactivé. Veuillez contacter l'administrateur.";
                header('Location: connexion.php');
                exit;
              }
              
              if (password_verify($password, $row['password'])) {
                  $_SESSION['user'] = [
                      'id' => $row['id'],
                      'nom' => $row['nom'],
                      'prenom' => $row['prenom'],
                      'email' => $row['email'],
                      'role' => (int)$row['role']
                  ];
                  $_SESSION['message'] = "Connexion réussie";
                  // Redirection selon le rôle
                  if((int)$row['role'] === 1){
                    header('Location: index.php'); // 用户跳转到首页
                  } else if((int)$row['role'] === 2){
                    header('Location: index.php'); // 搬家工人跳转到首页
                  } else if((int)$row['role'] === 3){
                    header('Location: admin.php'); // 管理员重定向到管理页面
                  } else {
                    header('Location: index.php');
                  }
                  exit;
              }
          }
      }
      $stmt->close();
  }

  $_SESSION['erreur'] = "Identifiants invalides ou type de compte incorrect";
  header('Location: connexion.php');
  exit;
?>