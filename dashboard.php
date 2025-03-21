

<?php


session_start();

// Verifica se o usuário não está logado e redireciona para a página de login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Já que o usuário está logado, podemos pegar o ID do usuário da sessão
$user_id = $_SESSION['user_id'];

$host = 'ip-45-79-13-239.cloudezapp.io';
$db   = 'mazal';
$user = 'mazal';
$pass = 'Rodrigo2012@';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$message = "";

try {
    $pdo = new PDO($dsn, $user, $pass, $options);



    
    
        // Recupera informações do usuário logado usando o user_id
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $userInfo = $stmt->fetch();

    

    // Recupera informações do usuário logado
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$_SESSION['email']]);
    $userInfo = $stmt->fetch();


    if (isset($_POST['logout'])) {
        // Destroy the session and logout the user
        session_destroy();
        header('Location: login.php');
        exit;
    }

    // Recupera o título atual do usuário
    $stmt = $pdo->prepare("SELECT title FROM home_title WHERE user_id = ?");
$stmt->execute([$user_id]);
$titleInfo = $stmt->fetch();
$userTitle = $titleInfo['title'] ?? 'Sem título';

    if(isset($_POST['new_title'])) {
      $newTitle = $_POST['new_title'];
  
      // Recupera o título atual do usuário
      $stmt = $pdo->prepare("SELECT `title` FROM `home_title` WHERE `user_id` = ?");
      $stmt->execute([$userInfo['id']]);
      $titleInfo = $stmt->fetch();


      // **Salvar novo título**
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_title'])) {
    $newTitle = $_POST['new_title'];

    if ($titleInfo) {
        $stmt = $pdo->prepare("UPDATE home_title SET title = ? WHERE user_id = ?");
        $success = $stmt->execute([$newTitle, $user_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO home_title (user_id, title) VALUES (?, ?)");
        $success = $stmt->execute([$user_id, $newTitle]);
    }

    $message = $success ? "Título atualizado com sucesso!" : "Erro ao atualizar o título!";
    $userTitle = $newTitle;
}

  
      // Verifica se o usuário já tem um título
      if ($titleInfo) {
          $query = "UPDATE `home_title` SET `title` = ? WHERE `user_id` = ?";
          $stmt = $pdo->prepare($query);
          $success = $stmt->execute([$newTitle, $userInfo['id']]);
      } else {
          $query = "INSERT INTO `home_title` (`user_id`, `title`) VALUES (?, ?)";
          $stmt = $pdo->prepare($query);
          $success = $stmt->execute([$userInfo['id'], $newTitle]);
      }
  
      if($success) {
          $message = "Título atualizado com sucesso!";
          $userTitle = $newTitle;  // Atualiza a variável $userTitle com o novo título
      } else {
          $message = "Erro ao atualizar o título!";
      }
  }

  // Recupera a bio atual do usuário
$stmt = $pdo->prepare("SELECT `bio` FROM `user_bio` WHERE `user_id` = ?");
$stmt->execute([$userInfo['id']]);
$bioInfo = $stmt->fetch();
$userBio = $bioInfo ? $bioInfo['bio'] : 'Sem biografia';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_bio'])) {
    $newBio = $_POST['new_bio'];

    if ($bioInfo) {
        $stmt = $pdo->prepare("UPDATE user_bio SET bio = ? WHERE user_id = ?");
        $success = $stmt->execute([$newBio, $user_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO user_bio (user_id, bio) VALUES (?, ?)");
        $success = $stmt->execute([$user_id, $newBio]);
    }

    $message .= $success ? "\nBiografia atualizada com sucesso!" : "\nErro ao atualizar a biografia!";
    $userBio = $newBio;
}


// Verifica se uma nova bio foi enviada e atualiza no banco de dados
if(isset($_POST['new_bio'])) {
  $newBio = $_POST['new_bio'];

  // Verifica se o usuário já tem uma bio
  if ($bioInfo) {
      $query = "UPDATE `user_bio` SET `bio` = ? WHERE `user_id` = ?";
      $stmt = $pdo->prepare($query);
      $success = $stmt->execute([$newBio, $userInfo['id']]);
  } else {
      $query = "INSERT INTO `user_bio` (`user_id`, `bio`) VALUES (?, ?)";
      $stmt = $pdo->prepare($query);
      $success = $stmt->execute([$userInfo['id'], $newBio]);
  }

  if($success) {
      $message .= "\nBiografia atualizada com sucesso!";
      $userBio = $newBio;  // Atualiza a variável $userBio com a nova biografia
  } else {
      $message .= "\nErro ao atualizar a biografia!";
  }
}

if (!isset($cards) || !is_array($cards)) {
    $cards = []; // Define como array vazio para evitar erro
}

// **Recuperar configurações do usuário**
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_config') {
    $stmt = $pdo->prepare("SELECT background_color, title_color, card_color FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $config = $stmt->fetch();

    header('Content-Type: application/json');
    echo json_encode([
        "status" => "success",
        "background" => $config['background_color'] ?? '#ffffff',
        "title" => $config['title_color'] ?? '#000000',
        "card" => $config['card_color'] ?? '#ffffff'
    ]);
    exit;
}


// Verifica se uma imagem foi enviada
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    $uploadDir = 'uploads/';
    $uploadFile = $uploadDir . basename($_FILES['profile_image']['name']);
  
    // Mova o arquivo temporário para a pasta de uploads
    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadFile)) {
        // Utiliza a abordagem INSERT ... ON DUPLICATE KEY UPDATE
        $query = "
            INSERT INTO `profile_images` (`user_id`, `image_path`) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE `image_path` = VALUES(`image_path`)
        ";
  
        $stmt = $pdo->prepare($query);
        if ($stmt->execute([$userInfo['id'], $uploadFile])) {
            $message = "Imagem atualizada com sucesso!";
        } else {
            $message = "Erro ao atualizar a imagem!";
        }
    } else {
        $message = "Erro ao fazer upload da imagem!";
    }
  }
  


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar se os índices estão definidos e atribuir a variáveis, senão, atribuir valores padrão
    $new_name = $_POST["newName"] ?? "";
    $new_password = $_POST["newPassword"] ?? "";
    $confirm_password = $_POST["confirmPassword"] ?? "";
    
    // Inicializar uma variável para armazenar mensagens
    $message = [];
    
    // Se o nome não estiver vazio, atualize no banco de dados
    if (!empty($new_name)) {
        $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->execute([$new_name, $user_id]);
        $message[] = "Nome atualizado com sucesso!";
    }
    
    // Se a senha não estiver vazia e coincidir com a confirmação, atualize no banco de dados
    if (!empty($new_password) && $new_password == $confirm_password) {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $user_id]);
        $message[] = "Senha atualizada com sucesso!";
    } elseif (!empty($new_password) && $new_password != $confirm_password) {
        $message[] = "As senhas não coincidem!";
    }
    
    // Junte as mensagens para exibir ao usuário
    $message = implode(" ", $message);
}


// Recupera informações do usuário logado
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$userInfo = $stmt->fetch();

$message = [];

if (isset($_POST['new_social_link'], $_POST['new_social_icon_class'])) {
    $newSocialLink = $_POST['new_social_link'];
    $newSocialIconClass = $_POST['new_social_icon_class'];

    $query = "INSERT INTO `user_socials` (`user_id`, `link`, `icon_class`) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($query);
    try {
        $stmt->execute([$userInfo['id'], $newSocialLink, $newSocialIconClass]);
        $message[] = "Rede social adicionada com sucesso!";
    } catch (PDOException $e) {
        $message[] = "Erro ao adicionar a rede social!";
    }
}

if (isset($_POST['edit_social_id'], $_POST['edit_social_link'], $_POST['edit_social_icon_class'])) {
    $editSocialId = $_POST['edit_social_id'];
    $editSocialLink = $_POST['edit_social_link'];
    $editSocialIconClass = $_POST['edit_social_icon_class'];

    $query = "UPDATE `user_socials` SET `link` = ?, `icon_class` = ? WHERE `id` = ? AND `user_id` = ?";
    $stmt = $pdo->prepare($query);
    try {
        $stmt->execute([$editSocialLink, $editSocialIconClass, $editSocialId, $userInfo['id']]);
        $message[] = "Rede social atualizada com sucesso!";
    } catch (PDOException $e) {
        $message[] = "Erro ao atualizar a rede social!";
    }
}

if (isset($_POST['delete_social_id'])) {
    $deleteSocialId = $_POST['delete_social_id'];

    $query = "DELETE FROM `user_socials` WHERE `id` = ? AND `user_id` = ?";
    $stmt = $pdo->prepare($query);
    try {
        $stmt->execute([$deleteSocialId, $userInfo['id']]);
        $message[] = "Rede social excluída com sucesso!";
    } catch (PDOException $e) {
        $message[] = "Erro ao excluir a rede social!";
    }
}

// Se houver mensagens (indicando que uma ação foi realizada), retorne-as como JSON
if (!empty($message)) {
    header('Content-Type: application/json');
    echo json_encode(['messages' => $message]);
    exit;
}

  // Verifica se uma nova rede social foi enviada e a adiciona no banco de dados
if(isset($_POST['new_social_link'], $_POST['new_social_icon_class'])) {
  $newSocialLink = $_POST['new_social_link'];
  $newSocialIconClass = $_POST['new_social_icon_class'];

  $query = "INSERT INTO `user_socials` (`user_id`, `link`, `icon_class`) VALUES (?, ?, ?)";
  $stmt = $pdo->prepare($query);
  if($stmt->execute([$userInfo['id'], $newSocialLink, $newSocialIconClass])) {
      $message = "Rede social adicionada com sucesso!";
  } else {
      $message = "Erro ao adicionar a rede social!";
  }
}

// Verifica se o ID e os dados da rede social a ser atualizada foram enviados
if(isset($_POST['edit_social_id'], $_POST['edit_social_link'], $_POST['edit_social_icon_class'])) {
  $editSocialId = $_POST['edit_social_id'];
  $editSocialLink = $_POST['edit_social_link'];
  $editSocialIconClass = $_POST['edit_social_icon_class'];

  $query = "UPDATE `user_socials` SET `link` = ?, `icon_class` = ? WHERE `id` = ? AND `user_id` = ?";
  $stmt = $pdo->prepare($query);
  if($stmt->execute([$editSocialLink, $editSocialIconClass, $editSocialId, $userInfo['id']])) {
      $message = "Rede social atualizada com sucesso!";
  } else {
      $message = "Erro ao atualizar a rede social!";
  }
}

// Verifica se o ID da rede social a ser excluída foi enviado
if(isset($_POST['delete_social_id'])) {
  $deleteSocialId = $_POST['delete_social_id'];

  $query = "DELETE FROM `user_socials` WHERE `id` = ? AND `user_id` = ?";
  $stmt = $pdo->prepare($query);
  if($stmt->execute([$deleteSocialId, $userInfo['id']])) {
      $message = "Rede social excluída com sucesso!";
  } else {
      $message = "Erro ao excluir a rede social!";
  }
}


// Recupera as redes sociais do usuário após quaisquer atualizações
$stmt = $pdo->prepare("SELECT * FROM `user_socials` WHERE `user_id` = ?");
$stmt->execute([$userInfo['id']]);
$userSocials = $stmt->fetchAll();






$stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ?");
$stmt->execute([$userInfo['id']]);
$categories = $stmt->fetchAll();

if(isset($_POST['new_category'])) {
  $newCategory = $_POST['new_category'];

  $stmt = $pdo->prepare("INSERT INTO categories (name, user_id) VALUES (?, ?)");
  if($stmt->execute([$newCategory, $userInfo['id']])) {
      $message = "Categoria adicionada com sucesso!";
  } else {
      $message = "Erro ao adicionar a categoria!";
  }
}





// Adicionar uma nova categoria
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_category') {
  $categoryName = $_POST['categoryName'];

  // Inserir no banco de dados
  $stmt = $pdo->prepare("INSERT INTO categories (name, user_id) VALUES (?, ?)");
  if ($stmt->execute([$categoryName, $userInfo['id']])) {
      echo json_encode(['success' => true]);
  } else {
      echo json_encode(['success' => false, 'message' => 'Erro ao adicionar categoria.']);
  }
  exit;
}

// Editar uma categoria
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_category') {
  $categoryId = $_POST['categoryId'];
  $newCategoryName = $_POST['newCategoryName'];

  // Atualizar no banco de dados
  $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ? AND user_id = ?");
  if ($stmt->execute([$newCategoryName, $categoryId, $userInfo['id']])) {
      echo json_encode(['success' => true]);
  } else {
      echo json_encode(['success' => false, 'message' => 'Erro ao editar categoria.']);
  }
  exit;
}

// Excluir uma categoria
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_category') {
  $categoryId = $_POST['categoryId'];

  // Excluir do banco de dados
  $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
  if ($stmt->execute([$categoryId, $userInfo['id']])) {
      echo json_encode(['success' => true]);
  } else {
      echo json_encode(['success' => false, 'message' => 'Erro ao excluir categoria.']);
  }
  exit;
}

// Buscar categorias existentes
$stmt = $pdo->prepare("SELECT c.*, cat.name as category_name FROM cards c LEFT JOIN categories cat ON c.category_id = cat.id WHERE c.user_id = ?");
$stmt->execute([$userInfo['id']]);
$cardsWithCategoryNames = $stmt->fetchAll();


$stmt = $pdo->prepare("INSERT INTO cards (name, category_id, layout_type, image_path, video_path, link, user_id) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)");

foreach ($cards as $card) {
    
    echo "<div class='work__card grid-container mix " . htmlspecialchars($category_name) . "'>";
    echo "<img src='" . htmlspecialchars($card['image_path']) . "' class='work__img'>";
    echo "<h3 class='work__title'>" . htmlspecialchars($card['name']) . "</h3>";
}


// Determinar o número de categorias
$numCategories = count($categories);

// Determinar quantos slides mostrar com base no número de categorias
if ($numCategories == 0 || $numCategories == 1) {
    $slidesToShow = 1;
} else {
    $slidesToShow = min(3, $numCategories); // Mostra no máximo 3 slides
}

// Verifica se o usuário não está logado e redireciona para a página de login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  echo "Erro: usuário não autenticado.";
  exit;
}


function convertLinkToEmbedIframe($link) {
  // Verifica se o link é do YouTube
  if (strpos($link, 'youtube.com') !== false || strpos($link, 'youtu.be') !== false) {
      // Extrai o VIDEO_ID usando regex
      if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $link, $matches)) {
          $video_id = $matches[1];
          return '<iframe width="100%" height="100%" src="https://www.youtube.com/embed/' . $video_id . '" title="Welinnk" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
      }
  }
  
  return false;  // Retornar falso se o link não for um link do YouTube válido
}


// Pega o ID do usuário da sessão
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
if (!$user_id) {
    echo "Erro: ID do usuário não definido.";
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['action']) && $_POST['action'] == 'create_card') {
    if (!isset($_POST['categoria_id'], $_POST['layoutType'], $_POST['titulo'])) {
        echo "Erro: Preencha todos os campos obrigatórios!";
        exit;
    }

    $categoriaCard = $_POST['categoria_id'];
    $layoutType = $_POST['layoutType'];
    $nomeCard = $_POST['titulo'];
    $linkLayout = $_POST['link'] ?? null;
    $target_file_image = null;
    $uploadDir = "uploads/";

    // Criar pasta de uploads se não existir
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Upload da Imagem (Apenas para `tipo1`)
    if ($layoutType == "tipo1" && isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $fileType = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $allowedTypes = ["jpg", "jpeg", "png", "gif"];
        if (in_array($fileType, $allowedTypes)) {
            $newFileName = "img_" . time() . "_" . $user_id . "." . $fileType;
            $target_file_image = $uploadDir . $newFileName;
            if (!move_uploaded_file($_FILES["imagem"]["tmp_name"], $target_file_image)) {
                echo "Erro ao fazer o upload da imagem!";
                exit;
            }
        } else {
            echo "Formato de imagem inválido!";
            exit;
        }
    }

    // Verificar conexão com o banco de dados
    if (!$pdo) {
        echo "Erro de conexão com o banco de dados.";
        exit;
    }

    // Buscar o nome da categoria
    $stmtCategory = $pdo->prepare("SELECT name FROM categories WHERE id = ? AND user_id = ?");
    $stmtCategory->execute([$categoriaCard, $user_id]);
    $category = $stmtCategory->fetch();

    if (!$category) {
        echo "Categoria não encontrada.";
        exit;
    }

    $categoriaName = $category['name'];

    // Inserção no banco de dados
    $stmt = $pdo->prepare("INSERT INTO cards (name, category_id, layout_type, image_path, link, user_id) 
                          VALUES (?, ?, ?, ?, ?, ?)");

    if ($stmt->execute([$nomeCard, $categoriaCard, $layoutType, $target_file_image, $linkLayout, $user_id])) {
        echo "Card de Links criado com sucesso!";
    } else {
        echo "Erro ao criar o card: " . implode(" - ", $stmt->errorInfo());
    }
}

$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$current_name = $user['name'];





if (isset($_POST['changeInfo'])) {
    // Definindo valores padrão para evitar erros de "Undefined index"
    $new_name = isset($_POST["newName"]) ? $_POST["newName"] : null;
    $new_password = isset($_POST["newPassword"]) ? $_POST["newPassword"] : null;
    $confirm_password = isset($_POST["confirmPassword"]) ? $_POST["confirmPassword"] : null;

    // Obter os valores atuais do banco de dados
    $stmt = $pdo->prepare("SELECT name, password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_data = $stmt->fetch();

    $sql = "UPDATE users SET ";
    $params = [];
    $updates = [];

    // Verificar se o nome foi alterado
    if ($new_name !== null && $new_name !== $current_data['name']) {
        $updates[] = "name = ?";
        $params[] = $new_name;
    }
    
    // Verificar se a senha foi alterada
    if ($new_password !== null && $confirm_password !== null && $new_password == $confirm_password && !password_verify($new_password, $current_data['password'])) {
        $updates[] = "password = ?";
        $params[] = password_hash($new_password, PASSWORD_DEFAULT);
    }

    // Se não houver atualizações para fazer, saia da lógica de atualização
    if (empty($updates)) {
        return;
    }

    $sql .= implode(", ", $updates);
    $sql .= " WHERE id = ?";
    $params[] = $user_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
  
}




if (isset($_POST['getCardDetails'])) {
    header('Content-Type: application/json');

    if (!isset($_POST['cardId']) || empty($_POST['cardId'])) {
        echo json_encode(['success' => false, 'message' => 'ID do card não fornecido.']);
        exit;
    }

    $cardId = intval($_POST['cardId']);

    try {
        $stmt = $pdo->prepare("SELECT * FROM cards WHERE id = ?");
        $stmt->execute([$cardId]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($card) {
            echo json_encode(['success' => true, 'card' => $card]);
        } else {
            echo json_encode(['success' => false, 'message' => "Nenhum card encontrado com ID: $cardId."]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar detalhes do card.', 'error' => $e->getMessage()]);
    }
    exit;
}






if (isset($_POST['action']) && $_POST['action'] === 'delete_card' && isset($_POST['cardId'])) {
    
    $response = ['success' => false];  // Default response
    
    $cardId = $_POST['cardId'];

    try {
        $stmt = $pdo->prepare("DELETE FROM cards WHERE id = ?");
        $result = $stmt->execute([$cardId]);

        if ($result && $stmt->rowCount() > 0) {
            $response['success'] = true;
        } else {
            $response['message'] = 'Erro ao excluir card ou card não encontrado.';
        }

    } catch (PDOException $e) {
        $response['message'] = "Erro na execução da consulta: " . $e->getMessage();
    }

    echo json_encode($response);
    exit;
}



if (isset($_POST['logout'])) {
    // Destrua a sessão e faça logout do usuário
    session_destroy();
    header('Location: login.php');  // Redireciona para a página de login
    exit;
}



// Consulta para obter todos os cards associados ao usuário logado
$stmt = $pdo->prepare("SELECT * FROM cards WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cards = $stmt->fetchAll();


// ✅ Salvar configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_config') {
    try {
        // 🛠 DEBUG: Captura os valores enviados pelo formulário
        file_put_contents('debug_log.txt', json_encode($_POST, JSON_PRETTY_PRINT), FILE_APPEND);

        $backgroundColor = $_POST['background_color'] ?? '#ffffff';
        $titleColor = $_POST['title_color'] ?? '#000000';
        $cardColor = $_POST['card_color'] ?? '#ffffff';
        $cardTextColor = $_POST['card_text_color'] ?? '#000000';
        $categoryTextColor = $_POST['category_text_color'] ?? '#000000';
        $categoryButtonColor = $_POST['category_button_color'] ?? '#ffffff';
        $categoryButtonBgColor = $_POST['category_button_bg_color'] ?? '#cccccc';
        $workCardBgColor = $_POST['work_card_bg_color'] ?? '#ffffff';
        $workButtonColor = $_POST['work_button_color'] ?? '#ff0000';

        // ✅ Verifica se o usuário já tem configurações salvas
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $configExists = $stmt->fetchColumn();

        if ($configExists) {
            // ✅ Atualiza configurações existentes
            $stmt = $pdo->prepare("
                UPDATE user_preferences 
                SET 
                    background_color = ?, 
                    title_color = ?, 
                    card_color = ?, 
                    card_text_color = ?, 
                    category_text_color = ?, 
                    category_button_color = ?, 
                    category_button_bg_color = ?, 
                    work_card_bg_color = ?, 
                    work_button_color = ?
                WHERE user_id = ?
            ");

            $success = $stmt->execute([
                $backgroundColor,
                $titleColor,
                $cardColor,
                $cardTextColor,
                $categoryTextColor,
                $categoryButtonColor,
                $categoryButtonBgColor,
                $workCardBgColor,
                $workButtonColor,
                $user_id
            ]);
        } else {
            // ✅ Insere novas configurações
            $stmt = $pdo->prepare("
                INSERT INTO user_preferences (user_id, background_color, title_color, card_color, card_text_color, category_text_color, category_button_color, category_button_bg_color, work_card_bg_color, work_button_color) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $success = $stmt->execute([
                $user_id,
                $backgroundColor,
                $titleColor,
                $cardColor,
                $cardTextColor,
                $categoryTextColor,
                $categoryButtonColor,
                $categoryButtonBgColor,
                $workCardBgColor,
                $workButtonColor
            ]);
        }

        if ($success) {
            echo json_encode(["status" => "success", "message" => "Configuração salva com sucesso!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Erro ao salvar no banco de dados."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Erro ao salvar configuração: " . $e->getMessage()]);
    }
    exit;
}

// ✅ Buscar configurações ao carregar a página
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_config') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $userPreference = $stmt->fetch();

        if (!$userPreference) {
            echo json_encode(["status" => "error", "message" => "Nenhuma configuração encontrada."]);
            exit;
        }

        // ✅ Retorna os dados em JSON
        echo json_encode([
            "status" => "success",
            "background_color" => $userPreference['background_color'] ?? '#ffffff',
            "title_color" => $userPreference['title_color'] ?? '#000000',
            "card_color" => $userPreference['card_color'] ?? '#ffffff',
            "card_text_color" => $userPreference['card_text_color'] ?? '#000000',
            "category_text_color" => $userPreference['category_text_color'] ?? '#000000',
            "category_button_color" => $userPreference['category_button_color'] ?? '#ffffff',
            "category_button_bg_color" => $userPreference['category_button_bg_color'] ?? '#cccccc',
            "work_card_bg_color" => $userPreference['work_card_bg_color'] ?? '#ffffff',
            "work_button_color" => $userPreference['work_button_color'] ?? '#ff0000'
        ]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Erro ao recuperar as configurações: " . $e->getMessage()]);
    }
    exit;
}

// ✅ Tratamento de erro geral do PDO
} catch (PDOException $e) {
    error_log("Erro no banco de dados: " . $e->getMessage()); // Registra o erro no log do servidor
    echo json_encode(["status" => "error", "message" => "Erro interno do servidor."]);
    exit;
}






   
?>






<!DOCTYPE html>
<html lang="en">
<head>


    <!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-102491435-1"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-102491435-1');
</script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--==================== UNICONS ====================-->
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v3.0.6/css/line.css">

    <!--==================== SWIPER CSS ====================-->
    <link rel="stylesheet" href="assets/css/swiper-bundle.min.css">

    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">

    <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>


    <!-- CSS do Bootstrap -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">



    <!--==================== CSS ====================-->
    <link rel="stylesheet" href="assets/css/dash.css">

    <title><?php echo htmlspecialchars($userTitle); ?></title>
</head>


<style>
/* Aplica a cor nos títulos gerais do site */
h1, h2, h3, h4, h5, h6, 
.home__title, 
.footer__copy,
.home__description {
    color: var(--title-color);
}

/* Exclui os títulos dos cards e dos modais */
.work__title, 
.modal-title,
.card-title,
.modal-body,
.modal-content {
    color: initial !important; /* Mantém a cor original */
}

/* Aplica a cor nos cards */
.card {
    background-color: var(--card-color);
}

/* Aplica a cor dos botões de categoria */
.work__item {
    background-color: var(--category-button-bg-color) !important;
    color: var(--category-text-color) !important;
}


/* Aplica a cor do texto dentro do botão */
.work__item button {
    color: var(--category-button-color);
}

.work__card {
    background-color: var(--work-card-bg-color, #ffffff) !important;
    color: var(--card-text-color, #000000) !important;
}


.home__img {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 150px; /* Ajuste conforme necessário */
    height: 150px; /* Ajuste conforme necessário */
    border-radius: 50%; /* Garante o formato de círculo */
    overflow: hidden; /* Corta qualquer parte da imagem que sair do círculo */
    background-color: #ddd; /* Caso a imagem não carregue, fica um fundo */
}

.home__img .home__blob {
    width: 100%;
    height: 100%;
    border-radius: 50%;
}

.home__img .home__blob-img {
    width: 100%;
    height: 100%;
    object-fit: cover; /* Faz a imagem preencher o círculo corretamente */
    border-radius: 50%;
}

.swiper-container-free-mode>.swiper-wrapper {
    transition-timing-function: ease-out;
    margin: 0 auto;
    justify-content: center;
}

body {
    max-width: 100%;
    overflow-x: hidden; /* Impede rolagem lateral */
    min-height: 80vh; /* Faz o conteúdo ocupar toda a altura da tela */
}
img {
    max-width: 100%;
    height: auto; /* Mantém a proporção correta da imagem */
}

/* Impede a rolagem lateral no body e html */
html, body {
    overflow-x: hidden; 
    max-width: 100vw; 
}

/* Garante que nenhum elemento ultrapasse a largura da tela */
* {
    box-sizing: border-box;
    max-width: 100%;
}


.home__container {
    display: flex;
    flex-direction: column; /* Coloca os elementos em coluna */
    align-items: center; /* Centraliza na horizontal */
    justify-content: center; /* Centraliza na vertical */
    text-align: center; /* Centraliza o texto */
   
}

.home__description {
    text-align: center; /* Centraliza o texto */
    max-width: 80%; /* Limita a largura do texto para não ficar muito espalhado */
    margin: 10px auto; /* Centraliza horizontalmente */
    line-height: 1.5; /* Melhora a legibilidade */
}


/* Centraliza a imagem do perfil */
.home__img {
    display: flex;
    justify-content: center;
    align-items: center;
  
    border-radius: 50%; /* Garante que fique em formato de círculo */
    overflow: hidden; /* Evita que a imagem ultrapasse */
}

.work__container {
    display: flex;
    flex-direction: column;
    align-items: center; /* Centraliza horizontalmente */
    justify-content: center; /* Centraliza verticalmente */
    width: 100%; /* Garante que ocupa toda a largura */
    text-align: center; /* Centraliza o conteúdo */
}

.work__card {
    display: flex;
   
    margin-left: -1.8rem;
}

.home__content{
    display:flex;
    margin-right:6.5rem;
}

.home__img img {
    width: 100%;
    height: 100%;
    object-fit: cover; /* Mantém a proporção correta */
}

/* Ajusta a posição dos ícones de edição */
.pen-edit {
    display: flex;
    justify-content: center;
   
}

/* Ajusta a seção de botões */
.work__container {
    display: flex;
    justify-content: center; /* Centraliza os botões horizontalmente */
    flex-wrap: wrap; /* Garante que os botões não fiquem espremidos */
    gap: 10px; /* Adiciona espaço entre os botões */
}

.work__item {
    text-align: center; /* Centraliza o texto dos botões */
}

* {
    box-sizing: border-box;
}
.category-button {
        color: <?= htmlspecialchars($userPreference['category_button_color']); ?>;
        background-color: <?= htmlspecialchars($userPreference['category_button_bg_color']); ?>;
    }
    .category-text {
        color: <?= htmlspecialchars($userPreference['category_text_color']); ?>;
    }
    
/* === Estilização do Modal === */
#configModal {
    display: none; /* Escondido por padrão */
    position: fixed;
    top: 5%; /* Posiciona mais próximo do topo */
    left: 50%;
    transform: translate(-50%, 0);
    width: 90%; /* Mais responsivo */
    max-width: 380px; /* Mantém um tamanho adequado */
    background-color: rgba(0, 0, 0, 0.6); /* Fundo semi-transparente */
    backdrop-filter: blur(5px); /* Efeito de vidro */
    padding: 15px;
    z-index: 1000;
    border-radius: 10px;
    transition: opacity 0.3s ease-in-out;
}

/* === Fundo escuro para fechar ao clicar fora === */
#configModal::before {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
    z-index: -1;
}

/* === Conteúdo do Modal === */
.modal-content {
    background: #fff;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    position: relative;
    width: 100%;
    max-height: 70vh; /* Limita altura para não ficar gigante */
    overflow-y: auto; /* Permite rolagem dentro do modal */
    margin-top: 10vh;
}

/* === Botão Fechar (X) === */
.close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 22px;
    font-weight: bold;
    color: #333;
    cursor: pointer;
    transition: 0.3s;
}

.close:hover {
    color: red;
}

/* === Inputs de cor === */
.modal-content input[type="color"] {
    width: 100%;
    height: 40px;
    border: none;
    cursor: pointer;
    border-radius: 5px;
}

/* === Botão Salvar === */
#saveConfigButton {
    width: 100%;
    background: #ff4500;
    color: white;
    padding: 10px;
    border: none;
    font-size: 16px;
    border-radius: 5px;
    cursor: pointer;
    transition: 0.3s;
    margin-top: 10px;
}

#saveConfigButton:hover {
    background: #e03e00;
}

/* === Responsividade === */
@media (max-width: 600px) {
    #configModal {
        width: 95%;
        top: 3%; /* Ajusta para telas menores */
    }

    .modal-content {
        padding: -10px;
    }
}

</style>


<body>
<!--==================== HEADER ====================-->

<header class="header" id="header">
    <nav class="nav container">
    <i class="uil uil-setting settings-icon" onclick="abrirModalConfiguracoes()"></i>


        <i class="uil  uil-user" data-toggle="modal" data-target="#userModal"></i>
        <i  class="uil uil-moon change-theme" id="theme-button"></i>
    </nav>
</header>

   
   
</header>


<div id="configModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="fecharModalConfiguracoes()">&times;</span>
        <h2 class="card-title">Configurações</h2>

        <div class="modal-group">
            <label for="backgroundColor">Fundo:</label>
            <input type="color" id="backgroundColor">

            <label for="titleColor">Títulos:</label>
            <input type="color" id="titleColor">

            <label for="cardColor">Cards:</label>
            <input type="color" id="cardColor">
        </div>

        <div class="modal-group">
            <label for="workCardBgColor">Fundo Cards:</label>
            <input type="color" id="workCardBgColor">

            <label for="cardTextColor">Texto Cards:</label>
            <input type="color" id="cardTextColor">

            <label for="workButtonColor">Botões Cards:</label>
            <input type="color" id="workButtonColor">
        </div>

        <div class="modal-group">
            <label for="categoryTextColor">Texto Categoria:</label>
            <input type="color" id="categoryTextColor">

            <label for="categoryButtonBgColor">Botão Categoria:</label>
            <input type="color" id="categoryButtonBgColor">
        </div>

        <button id="saveConfigButton" onclick="salvarConfiguracao()">Salvar</button>
    </div>
</div>



<!--==================== MAIN ====================-->
<main class="main">

<!-- User Modal -->
<div class="modal fade" style="z-index: 9999;" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="userModalLabel">Opções do Usuário</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="post" action="dashboard.php">
      <div class="modal-body">
        Selecione uma das opções abaixo:
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal" data-toggle="modal" data-target="#changeInfoModal">Trocar nome e senha</button>
        <button type="submit" name="logout" class="btn btn-danger">Fazer logout</button>

      </div>
      </form>
    </div>
  </div>
</div>

       
<!-- Change Info Modal -->
<div class="modal fade" id="changeInfoModal" tabindex="-1" aria-labelledby="changeInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="changeInfoModalLabel">Trocar nome e senha</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="dashboard.php" method="post">
        <div class="modal-body">
            <div class="form-group">
                <label for="newName">Novo nome:</label>
                <input type="text" class="form-control" id="newName" name="newName" value="<?php echo $current_name; ?>" placeholder="<?php echo $current_name; ?>">


            </div>
            <div class="form-group">
                <label for="newPassword">Nova senha:</label>
                <input type="password" class="form-control" id="newPassword" name="newPassword" placeholder="Digite sua nova senha">
            </div>
            <div class="form-group">
                <label for="confirmPassword">Confirmar senha:</label>
                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Confirme sua nova senha">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            <button type="submit" class="btn btn-primary">Salvar alterações</button>
        </div>
      </form>
    </div>
  </div>
</div>







      
    <!--==================== HOME ====================-->
    <section class="home section" id="home">
        <div class="home__container container grid">
            <div class="home__content grid">
                

            <div class="home__img">
    <?php
    $stmt = $pdo->prepare("SELECT `image_path` FROM `profile_images` WHERE `user_id` = ?");
    $stmt->execute([$userInfo['id']]);
    $imageInfo = $stmt->fetch();
    $imagePath = $imageInfo ? $imageInfo['image_path'] : 'assets/img/logo23.png';
    ?>
    <img src="<?php echo htmlspecialchars($imagePath); ?>" class="home__blob-img" alt="Imagem de Perfil">
</div>



                       
    <div class="pen-edit">
        <i  class="uil uil-edit-alt" data-toggle="modal" data-target="#modalEditarImagemPerfil"></i>
    </div>
</div>



<!-- Modal para Editar/Adicionar Imagem de Perfil -->
<div class="modal fade" id="modalEditarImagemPerfil">
    <div class="modal-dialog">
        <div class="modal-content">
  
            <!-- Cabeçalho do Modal -->
            <div class="modal-header">
                <h4 class="modal-title">Foto de perfil</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <!-- Formulário para atualizar a imagem de perfil -->
            <form action="dashboard.php" method="post" enctype="multipart/form-data">
                <!-- Corpo do Modal -->
                <div class="modal-body">
                    <label>Selecione uma imagem:</label>
                    <input type="file" class="form-control" name="profile_image" accept="image/*">
                </div>
      
                <!-- Rodapé do Modal -->
                <div class="modal-footer">
                    <!-- Botão para salvar a nova imagem de perfil -->
                    <button type="submit" class="btn btn-primary">Salvar</button>

                    <!-- Botão para fechar o modal sem salvar -->
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                </div>
            </form>
  
        </div>
    </div>
</div>


  
  
            
            <div class="home__data">
                
            <h1 class="home__title">
    <!-- Exibe o título do usuário, usando htmlspecialchars para prevenir XSS -->
    <?php echo htmlspecialchars($userTitle); ?> 

    <!-- Ícone que abrirá o modal para edição do título -->
    <i class="uil uil-edit-alt" data-toggle="modal" data-target="#myModal"></i>
</h1>

<br>

<!-- Modal para edição do título -->
<div class="modal fade" id="myModal">
    <div class="modal-dialog">
        <div class="modal-content">
  
            <!-- Cabeçalho do Modal -->
            <div class="modal-header">
                <h4 class="modal-title">Editar Nome</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <!-- Formulário para atualizar o título -->
            <form action="dashboard.php" method="post">
                <!-- Corpo do Modal -->
                <div class="modal-body">
                    <!-- Campo de entrada para o novo título. O título atual é exibido como valor padrão -->
                    <input type="text" class="form-control" placeholder="Digite o nome" name="new_title" value="<?php echo htmlspecialchars($userTitle); ?>">
                </div>
      
                <!-- Rodapé do Modal -->
                <div class="modal-footer">
                    <!-- Botão para salvar o novo título -->
                    <button style="border-radius: 0.40rem;" type="submit" class="btn btn-primary">Salvar</button>

                    <!-- Botão para fechar o modal sem salvar -->
                    <button style="border-radius: 0.40rem;" type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                </div>
            </form>
  
        </div>
    </div>
</div>





  
  <p class="home__description">
    <!-- Exibe a biografia do usuário, usando htmlspecialchars para prevenir XSS -->
    <?php echo htmlspecialchars($userBio); ?> 
    <!-- Ícone que abrirá o modal para edição da bio -->
    <i style="font-size: 1.8rem;" class="uil uil-edit-alt" data-toggle="modal" data-target="#myModal2"></i>
</p>

<!-- Modal para edição da bio -->
<div class="modal fade" id="myModal2">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Cabeçalho do Modal -->
            <div class="modal-header">
                <h4 class="modal-title">Editar Bio</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <!-- Formulário para atualizar a bio -->
            <form action="dashboard.php" method="post">
                <!-- Corpo do Modal -->
                <div class="modal-body">
                    <!-- Campo de entrada para a nova bio. A bio atual é exibida como valor padrão -->
                    <input type="text" class="form-control" placeholder="Seu texto" name="new_bio" value="<?php echo htmlspecialchars($userBio); ?>">
                </div>
      
                <!-- Rodapé do Modal -->
                <div class="modal-footer">
                    <!-- Botão para salvar a nova bio -->
                    <button style="border-radius: 0.40rem;" type="submit" class="btn btn-primary">Salvar</button>

                    <!-- Botão para fechar o modal sem salvar -->
                    <button style="border-radius: 0.40rem;" type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="home__social">
    <!-- Loop para exibir as redes sociais do usuário -->
    <?php foreach ($userSocials as $social): ?>
        <a href="<?php echo htmlspecialchars($social['link']); ?>" target="_blank" class="home__social-icon">
            <i class="<?php echo htmlspecialchars($social['icon_class']); ?>"   ></i>
        </a>
    <?php endforeach; ?>
</div>
<br>

<i style="display: grid; justify-content: center; font-size: 1.8rem;" class="uil uil-edit-alt" data-toggle="modal" data-target="#modalRedeSocial"></i>

<!-- Modal para exibição e edição de Redes Sociais -->
<div class="modal fade" id="modalRedeSocial">
    <div class="modal-dialog">
        <div class="modal-content">

            <!-- Loop para exibir as redes sociais no modal -->
            <?php foreach ($userSocials as $social): ?>
                <div class="card mt-3">
                    <div class="card-body">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h5 class="card-title"><?php echo ucfirst($social['platform']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($social['link']); ?></p>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalEditarRedeSocial">Editar</button>
                        <button type="button" class="btn btn-danger" onclick="excluirRedeSocial(<?php echo $social['id']; ?>)">Excluir</button>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Botão para adicionar nova rede social -->
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalNovoRedeSocial">Adicionar Rede Social</button>

        </div>
    </div>
</div>

<!-- Modal para Edição de Rede Social -->
<div class="modal fade" id="modalEditarRedeSocial">
    <div class="modal-dialog">
      <div class="modal-content">
  
        <!-- Cabeçalho do Modal -->
        <div class="modal-header">
          <h4 class="modal-title">Editar Rede Social</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
  
        <!-- Corpo do Modal -->
        <div class="modal-body">
          <label>Selecione um ícone:</label>
          <select name="platformName" class="form-control">
          <option value="uil uil-facebook-f">Facebook</option>
            <option value="uil uil-envelope">Email</option>
            <option value="uil uil-instagram">Instagram</option>
            <option value="uil uil-twitter">Twitter</option>
            <option value="uil uil-linkedin">LinkedIn</option>
            <option value="uil uil-pinterest">Pinterest</option>
            <option value="uil uil-youtube">YouTube</option>
            <option value="uil uil-snapchat">Snapchat</option>
            <option value="uil uil-tiktok">TikTok</option>
            <option value="uil uil-whatsapp">WhatsApp</option>

    <!-- Adicione mais opções conforme necessário -->
</select>

  
          <label class="mt-3">Link da Rede Social:</label>
          <input type="url" class="form-control" placeholder="https://www.seulink.com" id="editarLinkInput">
        </div>
  
        <!-- Rodapé do Modal -->
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" onclick="atualizarRedeSocial()">Atualizar</button>
          <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
        </div>
  
      </div>
    </div>
</div>

<!-- Modal para Adicionar Nova Rede Social -->
<div class="modal fade" id="modalNovoRedeSocial">
    <div class="modal-dialog">
      <div class="modal-content">
  
        <!-- Cabeçalho do Modal -->
        <div class="modal-header">
          <h4 class="modal-title">Adicionar Rede Social</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
  
        <!-- Corpo do Modal -->
        <div class="modal-body">
          <label>Selecione um ícone:</label>
          <select class="form-control" id="iconeSelect">
            <option value="uil uil-facebook-f">Facebook</option>
            <option value="uil uil-envelope">Email</option>
            <option value="uil uil-instagram">Instagram</option>
            <option value="uil uil-twitter">Twitter</option>
            <option value="uil uil-linkedin">LinkedIn</option>
            <option value="uil uil-pinterest">Pinterest</option>
            <option value="uil uil-youtube">YouTube</option>
            <option value="uil uil-snapchat">Snapchat</option>
            <option value="uil uil-tiktok">TikTok</option>
            <option value="uil uil-whatsapp">WhatsApp</option>

            <!-- Adicione mais opções conforme necessário -->
          </select>
  
          <label class="mt-3">Link da Rede Social:</label>
          <input type="url" class="form-control" placeholder="https://www.seulink.com" id="linkInput">
        </div>
  
        <!-- Rodapé do Modal -->
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" onclick="salvarRedeSocial()">Salvar</button>
          <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
        </div>
  
      </div>
    </div>
</div>



  
                
            </div>
        </div>
  

        <h2 class="section__title">
    <i class="uil uil-edit-alt" style="display: grid; justify-content: center; margin-right:3rem; font-size: 1.8rem;" data-toggle="modal" data-target="#modalEditarCategoria"></i>
</h2>
<!-- Modal para Criar/Editar Categoria -->
<div class="modal fade" id="modalEditarCategoria">
    <div class="modal-dialog">
        <div class="modal-content">

            <!-- Cabeçalho do Modal -->
            <div class="modal-header">
                <h4 class="modal-title" id="modalTituloCategoria">Adicionar Categoria</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <!-- Seção de Categorias Existentes -->
            <div id="secaoCategoriasExistentes">
                <!-- Loop para exibir as categorias do usuário -->
                <?php foreach ($categories as $category): ?>
                    <div class="card mt-3">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                            <button type="button" class="btn btn-primary" onclick="preencherFormularioEdicao(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>')">Editar</button>
                            <button type="button" class="btn btn-danger" onclick="excluirCategoria(<?php echo $category['id']; ?>)">Excluir</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Corpo do Modal -->
            <div class="modal-body">
                <input type="hidden" id="categoriaId" value="">
                <label>Nome da Categoria:</label>
                <input type="text" class="form-control" placeholder="Nome da Categoria" id="categoriaInput">
            </div>

            <!-- Rodapé do Modal -->
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="salvarCategoria()">Salvar</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
            </div>

        </div>
    </div>
</div>



<!-- Modal para Criar/Editar Categoria -->
<div class="modal fade" id="modalCategoria">
    <div class="modal-dialog">
        <div class="modal-content">

            <!-- Cabeçalho do Modal -->
            <div class="modal-header">
                <h4 class="modal-title" id="modalTitle">Adicionar Categoria</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <!-- Corpo do Modal -->
            <div class="modal-body">
                <input type="hidden" id="categoriaId" value="">
                <label>Nome da Categoria:</label>
                <input type="text" class="form-control" placeholder="Nome da Categoria" id="categoriaInput">
            </div>

            <!-- Rodapé do Modal -->
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="actionButton" onclick="salvarCategoria()">Adicionar</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>



<div class="swiper-container work__filters container grid">
    <div class="swiper-wrapper">
        <!-- Botão para mostrar todos os itens -->
        <div class="swiper-slide work__item active-work" data-filter="all">Tudo</div>

        <!-- Criando os botões de filtro para cada categoria -->
        <?php foreach ($categories as $category): 
            // Criar um nome de categoria formatado corretamente (sem espaços ou caracteres especiais)
            $category_filter = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($category['name'])));
        ?>
            <div class="swiper-slide work__item" data-filter=".<?php echo htmlspecialchars($category_filter); ?>">
                <?php echo htmlspecialchars($category['name']); ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>



    
<i style="display: grid; justify-content: center; font-size: 1.8rem; cursor: pointer;" class="uil uil-edit-alt" data-toggle="modal" data-target="#modalSelecaoLayout" title="Criar links"></i>

<br>

<div class="modal fade" id="modalSelecaoLayout">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Cabeçalho do Modal -->
            <div class="modal-header">
                <h4 class="modal-title">Selecione o Layout</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <!-- Corpo do Modal -->
            <div class="modal-body">
                <!-- Campo oculto para armazenar o ID do card selecionado -->
                <input type="hidden" id="selectedCardId">
                
                <label>Escolha o Layout do Card:</label>
                <select class="form-control" id="selecaoLayoutType">
                    <option value="layout1">Linnks</option>
                    <option value="layout2">Linnks Video</option>
                    <option value="layout3">Linnks Galeria</option>
                    <option value="layout4">Linnks Youtube</option>
                    
                </select>
                <!-- Seção dos Cards Criados -->
                <div class="mt-4">
                    <h5 class="card-title">Cards Criados:</h5>
                    <div id="cardsContainer">
                        <?php foreach ($cards as $card): ?>
                            <div class="card mt-2">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($card['name']) ?></h5>
                                    <button class="btn btn btn-primary" onclick="verificarLayoutEabrirModal('<?= $card['layoutType'] ?>', <?= $card['cardId'] ?>)">Editar</button>
                                    <button class="btn btn-danger" onclick="deletarCard(<?= $card['id'] ?>)">Deletar</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <!-- Rodapé do Modal -->
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="abrirModalEspecifico()">Continuar</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>




<div class="modal fade" id="modalLayout1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Linnks</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="dashboard.php" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <!-- Campos específicos para o Layout 1 -->

                    <label class="mt-3">Categoria:</label>
                    <select class="form-control" name="categoria_id">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <!-- Campo do Título -->
                    <label>Título:</label>
                    <input type="text" class="form-control" name="titulo" placeholder="Título do Card" id="tituloLayout1">
                    
                    <!-- Adicione o campo layoutType aqui -->
                    <label>Escolha o Tipo de Layout:</label>
                    <select class="form-control" name="layoutType">
                    <option value="tipo1">Linnks</option>
                   
                    <!-- Adicione outros tipos conforme necessário -->
                   </select>

                    <!-- Campo da Imagem -->
                    <label class="mt-3">Imagem:</label>
                    <input type="file" class="form-control" name="imagem" id="imagemLayout1">
                    
                    <!-- Campo do Link -->
                    <label class="mt-3">Link:</label>
                    <input type="url" class="form-control" name="link" placeholder="https://www.seulink.com" id="linkLayout1">
                </div>
                <div class="modal-footer">
                    <button type="submit" name="action" value="create_card" class="btn btn-primary">Salvar</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>






<div class="modal fade" id="modalLayout2">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Linnks Video</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="dashboard.php" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <!-- Campos específicos para o Layout 2 -->

                    <label class="mt-3">Categoria:</label>
                    <select class="form-control" name="categoria_id">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>

                     <!-- Campo do Título -->
                     <label>Título:</label>
                    <input type="text" class="form-control" name="titulo" placeholder="Título do Card" id="tituloLayout1">
                    

                    <!-- Adicione o campo layoutType aqui -->
                    <label>Escolha o Tipo de Layout:</label>
                    <select class="form-control" name="layoutType">
                        <option value="tipo2">Linnks Video</option>
                        <!-- Adicione outros tipos conforme necessário -->
                    </select>

                    <!-- Campo do Vídeo -->
                    <label class="mt-3">Vídeo (MP4):</label>
                    <input type="file" class="form-control" name="video" accept="video/mp4" id="videoLayout2">
                    
                    <!-- Não há campos de título e link para o tipo2, já que você mencionou que eles devem ser nulos. -->
                </div>
                <div class="modal-footer">
                    <button type="submit" name="action" value="create_card" class="btn btn-primary">Salvar</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>




<div class="modal fade" id="modalLayout3">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Linnks Galeria</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="dashboard.php" method="post" enctype="multipart/form-data">
            <div class="modal-body">

            <label class="mt-3">Categoria:</label>
                    <select class="form-control" name="categoria_id">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>

                      <!-- Campo do Título -->
                      <label>Título:</label>
                    <input type="text" class="form-control" name="titulo" placeholder="Título do Card" id="tituloLayout1">
                    
                      <!-- Adicione o campo layoutType aqui -->
                      <label>Escolha o Tipo de Layout:</label>
                    <select class="form-control" name="layoutType">
                        <option value="tipo3">Linnks Galeria</option>
                        <!-- Adicione outros tipos conforme necessário -->
                    </select>

                <!-- Campos específicos para o Layout 3 -->

                <label>Imagens:</label>
<input type="file" class="form-control image-input" name="images[]" multiple accept="image/*">


                <button type="button" class="btn btn-secondary mt-3" onclick="addImageField()">Adicionar mais imagens</button>
                
            </div>
            <div class="modal-footer">
                    <button type="submit" name="action" value="create_card" class="btn btn-primary">Salvar</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="modalLayout4">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Linnks Youtube</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="dashboard.php" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <!-- Campos específicos para o Layout 2 -->

                    <label class="mt-3">Categoria:</label>
                    <select class="form-control" name="categoria_id">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>

                     <!-- Campo do Título -->
                     <label>Título:</label>
                    <input type="text" class="form-control" name="titulo" placeholder="Título do Card" id="tituloLayout1">
                    

                    <!-- Adicione o campo layoutType aqui -->
                    <label>Escolha o Tipo de Layout:</label>
                    <select class="form-control" name="layoutType">
                        <option value="tipo4">Linnks Youtube</option>
                        <!-- Adicione outros tipos conforme necessário -->
                    </select>

                    <!-- Campo do Vídeo -->
                    <label class="mt-3">Link do Vídeo:</label>
                    <input type="text" class="form-control" name="video_link" placeholder="Link do Vídeo">
                    
                    <!-- Não há campos de título e link para o tipo2, já que você mencionou que eles devem ser nulos. -->
                </div>
                <div class="modal-footer">
                    <button type="submit" name="action" value="create_card" class="btn btn-primary">Salvar</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>







<!-- Modal de Edição para Layout1 -->
<div class="modal fade" id="modalEditLayout1">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Cabeçalho do Modal -->
            <div class="modal-header">
                <h4 class="modal-title">Editar Card (Layout1)</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <!-- Corpo do Modal -->
            <div class="modal-body">
                <label>Nome:</label>
                <input type="text" id="editNameLayout1" class="form-control mb-3">
                <label>Imagem:</label>
                <input type="text" id="editImageLayout1" class="form-control mb-3">
                <label>Link:</label>
                <input type="text" id="editLinkLayout1" class="form-control mb-3">
            </div>
            <!-- Rodapé do Modal -->
            <div class="modal-footer">
                <button type="button" class="btn btn-primary">Salvar</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar cards do tipo Linnks Video (layout2) -->
<div class="modal fade" id="modalEditLayout2">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Cabeçalho do Modal -->
            <div class="modal-header">
                <h4 class="modal-title">Editar Card - Linnks Video</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <!-- Corpo do Modal -->
            <div class="modal-body">
                <label>Nome do Card:</label>
                <input type="text" class="form-control mb-4" id="editNameLayout2">
                <label>Caminho do Vídeo:</label>
                <input type="text" class="form-control mb-4" id="editVideoPathLayout2">
                <label>Link:</label>
                <input type="text" class="form-control mb-4" id="editLinkLayout2">
            </div>
            <!-- Rodapé do Modal -->
            <div class="modal-footer">
                <button type="button" class="btn btn-primary">Salvar</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar cards do tipo Linnks Galeria (layout3) -->
<div class="modal fade" id="modalEditLayout3">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Cabeçalho do Modal -->
            <div class="modal-header">
                <h4 class="modal-title">Editar Card - Linnks Galeria</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <!-- Corpo do Modal -->
            <div class="modal-body">
                <label>Nome do Card:</label>
                <input type="text" class="form-control mb-4" id="editNameLayout3">
                <label>Imagens da Galeria:</label>
                <input type="text" class="form-control mb-4" id="editGalleryImagesLayout3">
                <label>Link:</label>
                <input type="text" class="form-control mb-4" id="editLinkLayout3">
            </div>
            <!-- Rodapé do Modal -->
            <div class="modal-footer">
                <button type="button" class="btn btn-primary">Salvar</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar cards do tipo Linnks Youtube (layout4) -->
<div class="modal fade" id="modalEditLayout4">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Cabeçalho do Modal -->
            <div class="modal-header">
                <h4 class="modal-title">Editar Card - Linnks Youtube</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <!-- Corpo do Modal -->
            <div class="modal-body">
                <label>Nome do Card:</label>
                <input type="text" class="form-control mb-4" id="editNameLayout4">
                <label>Link do Vídeo do Youtube:</label>
                <input type="text" class="form-control mb-4" id="editYoutubeLinkLayout4">
                <label>Link:</label>
                <input type="text" class="form-control mb-4" id="editLinkLayout4">
            </div>
            <!-- Rodapé do Modal -->
            <div class="modal-footer">
                <button type="button" class="btn btn-primary">Salvar</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>





<div class="work__container container grid">
 <?php
 if (!empty($cards)):
  foreach ($cards as $card):

    // Buscar o nome da categoria pelo ID
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$card['category_id']]);
    $category = $stmt->fetch();
    $category_name = $category ? $category['name'] : 'Categoria desconhecida';

     // Consultar as imagens relacionadas ao card na tabela card_images
     $stmtImages = $pdo->prepare("SELECT image_path FROM card_images WHERE card_id = ?");
     $stmtImages->execute([$card['id']]);
     $images = $stmtImages->fetchAll(PDO::FETCH_COLUMN);

    switch ($card['layout_type']) {
      case "tipo1":
        echo "<div class='work__card grid-container mix " . htmlspecialchars($category_name) . "'>";
        echo "<h3 class='work__title'>" . htmlspecialchars($card['name']) . "</h3>";
        echo "<a href='" . htmlspecialchars($card['link']) . "' class='work__button'>";
        // Adicionando o ícone SVG aqui
        echo '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" id="plus" style="
    margin-left: 11rem;"><path fill="#000" fill-rule="evenodd" d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10ZM11 8a1 1 0 1 1 2 0v3h3a1 1 0 1 1 0 2h-3v3a1 1 0 1 1-2 0v-3H8a1 1 0 1 1 0-2h3V8Z" clip-rule="evenodd"></path></svg>';
        echo "</a>";
        echo "</div>";
        
        break;
      
       

        case "tipo2":
          if (isset($card['video_path']) && !empty($card['video_path'])) {
              echo "<div class='work__card2 mix " . htmlspecialchars($category_name) . "'>";
              echo "<video width='100%' height='100%' controls>";
              echo "<source src='" . htmlspecialchars($card['video_path']) . "' type='video/mp4'>";
              echo "Seu navegador não suporta a tag de vídeo.";
              echo "</video>";
              echo "</div>";
          }
          break;
      
          case "tipo3":
            // Consultar as imagens relacionadas ao card na tabela card_images
            $stmtImages = $pdo->prepare("SELECT image_path FROM card_images WHERE card_id = ?");
            $stmtImages->execute([$card['id']]);
            $images = $stmtImages->fetchAll(PDO::FETCH_COLUMN);
            
            if ($images) {
               
                echo "<div class='swiper-container work__card3 mix " . htmlspecialchars($category_name) . "'>";
                echo "<div class='work__card3 mix " . htmlspecialchars($category_name) . "'>";
                echo "<div class='swiper-wrapper'>";
                
                foreach ($images as $image_url) {
                    echo "<div class='swiper-slide'>";
                    echo "<img src='" . htmlspecialchars($image_url) . "' class='work__img'>";
                    echo "</div>";
                }
                
                echo "</div>";  // fechar swiper-wrapper
                
                // Adicione controles se você quiser que eles sejam visíveis
                echo "<div class='swiper-button-next'></div>";
                echo "<div class='swiper-button-prev'></div>";
                
                echo "</div>";  // fechar swiper-container
                echo "</div>";  // fechar work__card
            }
            break;
        

      
      case "tipo4":
        if (!empty($card['video_path'])) {
            $videoLink = $card['video_path'];
            $iframeCode = convertLinkToEmbedIframe($videoLink);
    
            if ($iframeCode) {
                // Exiba o iframe
                echo "<div class='work__card2 mix " . htmlspecialchars($category_name) . "'>";
                echo $iframeCode;
                echo "</div>";
            } else {
                // O link não é um link do YouTube válido
                echo '<p>Link de vídeo inválido.</p>';
            }
        }
        break;
        
       

    }
  endforeach;
else: 
  echo "<p>Nenhum card disponível.</p>";
endif;
?>
</div>




        
        
     


        
        
  
 
   
  </section>
 
<!--==================== FOOTER ====================-->


</main>








<!--==================== SCROLL TOP ====================-->
<a href="#" class="scrollup" id="scroll-up">
    <i class="uil uil-arrow-up scrollup__icon"></i>
</a>


<!--==================== SWIPER JS ====================-->
<script src="assets/js/swiper-bundle.min.js"></script>

 <!--=============== MIXITUP FILTER ===============-->
 <script src="assets/js/mixitup.min.js"></script>

 <!-- JS, Popper.js e jQuery do Bootstrap -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<!--==================== MAIN JS ====================-->
<script src="assets/js/main.js"></script>

<script async src='https://d2mpatx37cqexb.cloudfront.net/delightchat-whatsapp-widget/embeds/embed.min.js'></script>


<script>

document.addEventListener("DOMContentLoaded", function() {
    // Inicialização do Mixitup
    let mixerPortfolio = mixitup('.work__container');

    
    const linkWork = document.querySelectorAll('.work__item');

    linkWork.forEach(l => {
        l.addEventListener('click', function() {
            // Remova a classe active-work de todos os itens
            linkWork.forEach(link => link.classList.remove('active-work'));

            // Adicione a classe active-work ao item clicado
            this.classList.add('active-work');

            // Pega o valor do filtro e aplica
            const filterValue = this.getAttribute('data-filter');
            mixerPortfolio.filter(filterValue);
        });
    });

    // Inicialização do Swiper para os filtros
    const swiperFilters = new Swiper('.work__filters', {
        slidesPerView: 3,
        spaceBetween: 10,
        freeMode: true,
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
    });

    // Se você tiver um Swiper para os cards, inicialize-o aqui.
    // (O código que você forneceu para swiperCards parece estar relacionado ao tipo3 de card)

// Inicialização do Swiper para cards
var swiperCards = new Swiper('.work__card3', {
    slidesPerView: 1.1, // Quantidade de slides visíveis
    loop: true,
    navigation: {
        nextEl: '.work__card2-button-next',
        prevEl: '.work__card2-button-prev',
    },
    pagination: {
        el: '.work__card2-pagination',
        clickable: true,
    },
});

const swiperCardsTipo3 = new Swiper('.work__card3 .swiper-container', {
    slidesPerView: 1.1,
    loop: true,
    navigation: {
        nextEl: '.work__card2-button-next',
        prevEl: '.work__card2-button-prev',
    },
    pagination: {
        el: '.work__card2-pagination',
        clickable: true,
    },
});


});





function preencherEdicao(button) {
        let id = button.getAttribute('data-id');
        let link = button.getAttribute('data-link');
        let icon = button.getAttribute('data-icon');

        document.getElementById('editarIconeSelect').value = icon;
        document.getElementById('editarLinkInput').value = link;
        document.querySelector('#modalEditarRedeSocial .btn-primary').setAttribute('data-id', id);
    }

    function atualizarRedeSocial() {
        let id = document.querySelector('#modalEditarRedeSocial .btn-primary').getAttribute('data-id');
        let link = document.getElementById('editarLinkInput').value;
        let iconClass = document.getElementById('editarIconeSelect').value;

        let formData = new FormData();
        formData.append('edit_social_id', id);
        formData.append('edit_social_link', link);
        formData.append('edit_social_icon_class', iconClass);

        fetch('dashboard.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            alert(data); // Exibe uma mensagem de confirmação
            location.reload(); // Recarrega a página para ver as atualizações
        })
        .catch(error => {
            console.error('Erro:', error);
        });
    }

    function salvarRedeSocial() {
        let link = document.getElementById('linkInput').value;
        let iconClass = document.getElementById('iconeSelect').value;

        let formData = new FormData();
        formData.append('new_social_link', link);
        formData.append('new_social_icon_class', iconClass);

        fetch('dashboard.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            alert(data); // Exibe uma mensagem de confirmação
            location.reload(); // Recarrega a página para ver as atualizações
        })
        .catch(error => {
            console.error('Erro:', error);
        });
    }

    function excluirRedeSocial(id) {
        if (!confirm('Tem certeza de que deseja excluir esta rede social?')) {
            return;
        }

        let formData = new FormData();
        formData.append('delete_social_id', id);

        fetch('dashboard.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            alert(data); // Exibe uma mensagem de confirmação
            location.reload(); // Recarrega a página para ver as atualizações
        })
        .catch(error => {
            console.error('Erro:', error);
        });
    }


function salvarImagemPerfil() {
  var imagemPerfil = document.getElementById("imagemPerfilInput").files[0];
  if (imagemPerfil) {
    console.log("Imagem de perfil selecionada:", imagemPerfil.name);
    // Aqui você pode adicionar a lógica para salvar a imagem de perfil onde desejar
    $('#modalEditarImagemPerfil').modal('hide'); // Fecha o modal
    alert("Imagem de perfil salva com sucesso!");
  } else {
    alert("Por favor, selecione uma imagem de perfil.");
  }
}
function abrirModalEspecifico() {
    let layoutSelecionado = document.getElementById("selecaoLayoutType").value;
    $('#modalSelecaoLayout').modal('hide');  // Feche o modal de seleção
    $('#modal' + layoutSelecionado.charAt(0).toUpperCase() + layoutSelecionado.slice(1)).modal('show');  // Abra o modal específico do layout
}


let imageCount = 1;

function addImageField() {
    let imageContainer = document.getElementById('imagesContainer');
    let newImageInput = document.createElement('input');
    newImageInput.type = "file";
    newImageInput.className = "form-control image-input mt-3";
    newImageInput.name = "images[]";
    newImageInput.accept = "image/*";
    imageContainer.appendChild(newImageInput);
}



function preencherFormularioEdicao(id, nome) {
    document.getElementById('categoriaId').value = id;
    document.getElementById('categoriaInput').value = nome;
    document.getElementById('modalTitle').innerText = 'Editar Categoria';
    document.getElementById('actionButton').innerText = 'Editar';
    $('#modalCategoria').modal('show');
}
function abrirModalEdicaoCategoria(id, nome) {
    document.getElementById('categoriaId').value = id;
    document.getElementById('categoriaInput').value = nome;
    document.getElementById('modalTitle').innerText = 'Editar Categoria';
    document.getElementById('actionButton').innerText = 'Salvar Alterações';
    $('#modalCategoria').modal('show');
}



document.addEventListener("DOMContentLoaded", function () {
    carregarConfiguracoes();
});

// ✅ Abre o modal de configurações
function abrirModalConfiguracoes() {
    document.getElementById("configModal").style.display = "block";
}

// ✅ Fecha o modal de configurações
function fecharModalConfiguracoes() {
    document.getElementById("configModal").style.display = "none";
}

// ✅ Função para salvar todas as configurações (background, títulos, cards, botões)
function salvarConfiguracao() {
    let corFundo = getInputValue("backgroundColor", "#ffffff");
    let corTitulo = getInputValue("titleColor", "#000000");
    let corCard = getInputValue("cardColor", "#ffffff");
    let corTextoCard = getInputValue("cardTextColor", "#000000");
    let corTextoCategoria = getInputValue("categoryTextColor", "#000000");
    let corBotaoCategoria = getInputValue("categoryButtonColor", "#ffffff");
    let corBgBotaoCategoria = getInputValue("categoryButtonBgColor", "#cccccc");
    let corWorkCard = getInputValue("workCardBgColor", "#ffffff");
    let corWorkButton = getInputValue("workButtonColor", "#ff0000"); // ✅ Cor do botão dentro dos cards

    let formData = new FormData();
    formData.append("background_color", corFundo);
    formData.append("title_color", corTitulo);
    formData.append("card_color", corCard);
    formData.append("card_text_color", corTextoCard);
    formData.append("category_text_color", corTextoCategoria);
    formData.append("category_button_color", corBotaoCategoria);
    formData.append("category_button_bg_color", corBgBotaoCategoria);
    formData.append("work_card_bg_color", corWorkCard);
    formData.append("work_button_color", corWorkButton); // ✅ Nova cor do botão do card
    formData.append("action", "save_config");

    fetch("salvar_config.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            aplicarConfiguracoes({
                background_color: corFundo,
                title_color: corTitulo,
                card_color: corCard,
                card_text_color: corTextoCard,
                category_text_color: corTextoCategoria,
                category_button_color: corBotaoCategoria,
                category_button_bg_color: corBgBotaoCategoria,
                work_card_bg_color: corWorkCard,
                work_button_color: corWorkButton // ✅ Aplicando nova cor do botão do card
            });

            fecharModalConfiguracoes();
        } else {
            alert("Erro ao salvar configurações.");
        }
    })
    .catch(error => console.error("❌ Erro ao salvar configuração:", error));
}

// ✅ Função para carregar configurações ao iniciar a página
function carregarConfiguracoes() {
    fetch("salvar_config.php?action=get_config")
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                console.log("🎨 Configurações carregadas:", data);
                aplicarConfiguracoes(data);
            } else {
                console.warn("⚠️ Erro ao carregar configurações:", data.message);
            }
        })
        .catch(error => console.error("❌ Erro ao carregar configuração:", error));
}

// ✅ Aplica as configurações no CSS e na página
function aplicarConfiguracoes(data) {
    document.body.style.backgroundColor = data.background_color;
    document.documentElement.style.setProperty('--title-color', data.title_color);
    document.documentElement.style.setProperty('--card-color', data.card_color);
    document.documentElement.style.setProperty('--card-text-color', data.card_text_color);
    document.documentElement.style.setProperty('--category-text-color', data.category_text_color);
    document.documentElement.style.setProperty('--category-button-color', data.category_button_color);
    document.documentElement.style.setProperty('--category-button-bg-color', data.category_button_bg_color);
    document.documentElement.style.setProperty('--work-card-bg-color', data.work_card_bg_color);
    document.documentElement.style.setProperty('--work-button-color', data.work_button_color); // ✅ Aplicando cor do botão

    // ✅ Atualiza os inputs do modal
    setInputValue("backgroundColor", data.background_color);
    setInputValue("titleColor", data.title_color);
    setInputValue("cardColor", data.card_color);
    setInputValue("cardTextColor", data.card_text_color);
    setInputValue("categoryTextColor", data.category_text_color);
    setInputValue("categoryButtonColor", data.category_button_color);
    setInputValue("categoryButtonBgColor", data.category_button_bg_color);
    setInputValue("workCardBgColor", data.work_card_bg_color);
    setInputValue("workButtonColor", data.work_button_color); // ✅ Atualiza input do botão dos cards

    // ✅ Aplica as cores aos cards
    document.querySelectorAll('.card').forEach(card => {
        card.style.backgroundColor = data.card_color;
        card.style.color = data.card_text_color;
    });

    // ✅ Aplica a cor de fundo nos work__card
    document.querySelectorAll('.work__card').forEach(workCard => {
        workCard.style.backgroundColor = data.work_card_bg_color;
        workCard.style.color = data.card_text_color;
    });

    // ✅ Aplica as cores aos botões dentro dos cards (.work__button)
    document.querySelectorAll('.work__button').forEach(button => {
        button.style.backgroundColor = data.work_button_color;
    });

    // ✅ Aplica as cores aos botões de categoria
    document.querySelectorAll('.work__item').forEach(button => {
        button.style.color = data.category_button_color;
        button.style.backgroundColor = data.category_button_bg_color;
    });
}

// ✅ Obtém o valor do input (evita erro se o input não existir)
function getInputValue(id, defaultValue = "") {
    let input = document.getElementById(id);
    return input ? input.value : defaultValue;
}

// ✅ Define o valor do input (evita erro se o input não existir)
function setInputValue(id, value) {
    let input = document.getElementById(id);
    if (input) {
        input.value = value;
    }
}



function salvarCategoria() {
    var id = document.getElementById('categoriaId').value; // ID da categoria (usado para edição)
    var nome = document.getElementById('categoriaInput').value;
    var actionType = id ? 'edit_category' : 'add_category'; // Se tiver um ID, é edição, caso contrário, é adição

    var data = {
        action: actionType,
        categoryName: nome
    };

    if (actionType == 'edit_category') {
        data.categoryId = id;
        data.newCategoryName = nome;
    }

    fetch('dashboard.php', {
        method: 'POST',
        body: new URLSearchParams(data),
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

function excluirCategoria(id) {
    var data = {
        action: 'delete_category',
        categoryId: id
    };

    if (confirm('Tem certeza de que deseja excluir esta categoria?')) {
        fetch('dashboard.php', {
            method: 'POST',
            body: new URLSearchParams(data),
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        });
    }
}

// Esta função pode ser usada para preencher o formulário de edição com dados existentes
function preencherFormularioEdicao(id, nome) {
    document.getElementById('categoriaId').value = id;
    document.getElementById('categoriaInput').value = nome;
}


function adicionarCategoriaExistente(nomeCategoria) {
    var secaoCategorias = document.getElementById("secaoCategoriasExistentes");
    var categoriaCardSelect = document.getElementById("categoriaCardSelect");
  
    var cardCategoria = `
        <div class="card mt-3">
          <div class="card-body">
            <h5 class="card-title">${nomeCategoria}</h5>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalEditarCategoria">Editar</button>
            <button type="button" class="btn btn-danger" onclick="excluirCategoria(this)">Excluir</button>
          </div>
        </div>
    `;
    secaoCategorias.innerHTML += cardCategoria;

    // Adiciona a categoria ao select do modal de criação de card
    var option = document.createElement("option");
    option.text = nomeCategoria;
    option.value = nomeCategoria;
    categoriaCardSelect.add(option);
}





document.addEventListener("DOMContentLoaded", function () {
    carregarConfiguracao();
});

// Abre o modal de configurações
function abrirModalConfiguracoes() {
    document.getElementById("configModal").style.display = "block";
}

// Fecha o modal de configurações
function fecharModalConfiguracoes() {
    document.getElementById("configModal").style.display = "none";
}

// Salva a cor de fundo no banco de dados via AJAX
function salvarConfiguracao() {
    let corFundo = document.getElementById("backgroundColor").value;

    let formData = new FormData();
    formData.append("background_color", corFundo);
    formData.append("action", "save_background");

    fetch("salvar_config.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        alert(data);
        document.body.style.backgroundColor = corFundo;
        fecharModalConfiguracoes();
    })
    .catch(error => console.error("Erro ao salvar configuração:", error));
}

function carregarConfiguracao() {
    fetch("dashboard.php?action=get_background")
    .then(response => response.json())
    .then(data => {
        if (data.status === "success" && data.color) {
            console.log("Cor carregada:", data.color); // Debug para verificar no console
            document.body.style.backgroundColor = data.color;
            document.getElementById("backgroundColorPicker").value = data.color;
        } else {
            console.warn("Nenhuma cor encontrada, aplicando padrão.");
        }
    })
    .catch(error => console.error("Erro ao carregar configuração:", error));
}

document.addEventListener("DOMContentLoaded", function () {
    carregarConfiguracoes();
});

// ✅ Função para carregar configurações ao iniciar a página
function carregarConfiguracoes() {
    fetch("salvar_config.php?action=get_config")
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                console.log("🎨 Configurações carregadas:", data);

                // ✅ Aplica as cores ao body e elementos da página
                document.body.style.backgroundColor = data.background_color;
                document.documentElement.style.setProperty('--title-color', data.title_color);
                document.documentElement.style.setProperty('--card-color', data.card_color);
                document.documentElement.style.setProperty('--card-text-color', data.card_text_color);
                document.documentElement.style.setProperty('--category-text-color', data.category_text_color);
                document.documentElement.style.setProperty('--category-button-color', data.category_button_color);
                document.documentElement.style.setProperty('--category-button-bg-color', data.category_button_bg_color);
                document.documentElement.style.setProperty('--work-card-bg-color', data.work_card_bg_color);

                // ✅ Atualiza os inputs do modal
                setInputValue("backgroundColor", data.background_color);
                setInputValue("titleColor", data.title_color);
                setInputValue("cardColor", data.card_color);
                setInputValue("cardTextColor", data.card_text_color);
                setInputValue("categoryTextColor", data.category_text_color);
                setInputValue("categoryButtonColor", data.category_button_color);
                setInputValue("categoryButtonBgColor", data.category_button_bg_color);
                setInputValue("workCardBgColor", data.work_card_bg_color);

                // ✅ Aplica as cores aos cards
                document.querySelectorAll('.card').forEach(card => {
                    card.style.backgroundColor = data.card_color;
                    card.style.color = data.card_text_color; // ✅ Cor do texto dos cards
                });

                // ✅ Aplica a cor de fundo nos work__card
                document.querySelectorAll('.work__card').forEach(workCard => {
                    workCard.style.backgroundColor = data.work_card_bg_color;
                    workCard.style.color = data.card_text_color; // ✅ Cor do texto dentro dos work__card
                });

                // ✅ Aplica as cores aos botões de categoria
                document.querySelectorAll('.work__item').forEach(button => {
                    button.style.color = data.category_button_color;
                    button.style.backgroundColor = data.category_button_bg_color;
                });

            } else {
                console.warn("⚠️ Erro ao carregar configurações:", data.message);
            }
        })
        .catch(error => console.error("❌ Erro ao carregar configuração:", error));
}

// ✅ Função para salvar as configurações
function salvarConfiguracao() {
    let corFundo = getInputValue("backgroundColor", "#ffffff");
    let corTitulo = getInputValue("titleColor", "#000000");
    let corCard = getInputValue("cardColor", "#ffffff");
    let corTextoCard = getInputValue("cardTextColor", "#000000");
    let corTextoCategoria = getInputValue("categoryTextColor", "#000000");
    let corBotaoCategoria = getInputValue("categoryButtonColor", "#ffffff");
    let corBgBotaoCategoria = getInputValue("categoryButtonBgColor", "#cccccc");
    let corWorkCard = getInputValue("workCardBgColor", "#ffffff");

    let formData = new FormData();
    formData.append("background_color", corFundo);
    formData.append("title_color", corTitulo);
    formData.append("card_color", corCard);
    formData.append("card_text_color", corTextoCard);
    formData.append("category_text_color", corTextoCategoria);
    formData.append("category_button_color", corBotaoCategoria);
    formData.append("category_button_bg_color", corBgBotaoCategoria);
    formData.append("work_card_bg_color", corWorkCard);
    formData.append("action", "save_config");

    fetch("salvar_config.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            aplicarConfiguracoes({
                background_color: corFundo,
                title_color: corTitulo,
                card_color: corCard,
                card_text_color: corTextoCard,
                category_text_color: corTextoCategoria,
                category_button_color: corBotaoCategoria,
                category_button_bg_color: corBgBotaoCategoria,
                work_card_bg_color: corWorkCard
            });

            fecharModalConfiguracoes();
        } else {
            alert("Erro ao salvar configurações.");
        }
    })
    .catch(error => console.error("❌ Erro ao salvar configuração:", error));
}

// ✅ Aplica as cores sem precisar recarregar a página
function aplicarConfiguracoes(data) {
    document.body.style.backgroundColor = data.background_color;
    document.documentElement.style.setProperty('--title-color', data.title_color);
    document.documentElement.style.setProperty('--card-color', data.card_color);
    document.documentElement.style.setProperty('--card-text-color', data.card_text_color);
    document.documentElement.style.setProperty('--category-text-color', data.category_text_color);
    document.documentElement.style.setProperty('--category-button-color', data.category_button_color);
    document.documentElement.style.setProperty('--category-button-bg-color', data.category_button_bg_color);
    document.documentElement.style.setProperty('--work-card-bg-color', data.work_card_bg_color);

    document.querySelectorAll('.card').forEach(card => {
        card.style.backgroundColor = data.card_color;
        card.style.color = data.card_text_color; // ✅ Cor do texto dos cards
    });

    document.querySelectorAll('.work__card').forEach(workCard => {
        workCard.style.backgroundColor = data.work_card_bg_color;
        workCard.style.color = data.card_text_color; // ✅ Cor do texto dentro dos work__card
    });

    document.querySelectorAll('.work__item').forEach(button => {
        button.style.color = data.category_button_color;
        button.style.backgroundColor = data.category_button_bg_color;
    });
}

// ✅ Abre o modal de configurações
function abrirModalConfiguracoes() {
    let modal = document.getElementById("configModal");
    if (modal) {
        modal.style.display = "block";
    } else {
        console.error("❌ Erro: Modal de configurações não encontrado.");
    }
}

// ✅ Fecha o modal de configurações
function fecharModalConfiguracoes() {
    let modal = document.getElementById("configModal");
    if (modal) {
        modal.style.display = "none";
    }
}

// ✅ Obtém o valor do input (evita erro se o input não existir)
function getInputValue(id, defaultValue = "") {
    let input = document.getElementById(id);
    return input ? input.value : defaultValue;
}

// ✅ Define o valor do input (evita erro se o input não existir)
function setInputValue(id, value) {
    let input = document.getElementById(id);
    if (input) {
        input.value = value;
    }
}

document.addEventListener("DOMContentLoaded", function () {
    let sections = document.querySelectorAll("section[id]");

    if (sections.length === 0) {
        console.warn("⚠️ Nenhuma seção encontrada com um ID.");
        return;
    }

    function scrollActive() {
        sections.forEach(section => {
            let scrollY = window.pageYOffset;
            let sectionHeight = section.offsetHeight;
            let sectionTop = section.offsetTop - 50;
            let sectionId = section.getAttribute("id");
            let link = document.querySelector('.nav__menu a[href*=' + sectionId + ']');

            if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
                if (link) {
                    link.classList.add("active-link");
                }
            } else {
                if (link) {
                    link.classList.remove("active-link");
                }
            }
        });
    }

    window.addEventListener("scroll", scrollActive);
});




function verificarLayoutEabrirModal(layoutType, cardId) {
    // Salva o ID do card no campo oculto
    document.getElementById('selectedCardId').value = cardId;
    
    // Busca os detalhes do card e preenche o modal
    fetchCardDetailsAndPopulateModal(cardId, layoutType);
}

function fetchCardDetailsAndPopulateModal(cardId, layoutType) {
    $.post('dashboard.php', { getCardDetails: true, cardId: cardId }, function(response) {
        console.log(response); // para fins de depuração
        if (response.success) {
            let card = response.card;
            switch (layoutType) {
        case 'layout1':
            $('#editNameLayout1').val(card.name);
            $('#editImageLayout1').val(card.image_path);
            $('#editLinkLayout1').val(card.link);
            $('#modalEditLayout1').modal('show');
            break;
        case 'layout2':
            $('#editNameLayout2').val(card.name);
            $('#editVideoPathLayout2').val(card.video_path);
            $('#editLinkLayout2').val(card.link);
            $('#modalEditLayout2').modal('show');
            break;
        case 'layout3':
            $('#editNameLayout3').val(card.name);
            $('#editGalleryImagesLayout3').val(card.image_path);
            $('#editLinkLayout3').val(card.link);
            $('#modalEditLayout3').modal('show');
            break;
        case 'layout4':
            $('#editNameLayout4').val(card.name);
            $('#editYoutubeLinkLayout4').val(card.video_path);
            $('#editLinkLayout4').val(card.link);
            $('#modalEditLayout4').modal('show');
            break;
          }
        } else {
            alert('Erro ao buscar detalhes do card.');
        }
    }, 'json');
}




function deletarCard(cardId) {
    if (confirm("Tem certeza que deseja excluir este card?")) {
        fetch('dashboard.php', {
            method: 'POST',
            body: new URLSearchParams({
                action: 'delete_card',
                cardId: cardId
            }),
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Card excluído com sucesso!");
                location.reload();
            } else {
                alert(data.message);
            }
        });
    }
}




      </script>
  



      
</body>
</html>