<?php
session_start();

// ✅ Verifica se o usuário está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(["status" => "error", "message" => "Usuário não autenticado."]);
    exit;
}

// ✅ Conexão com o banco de dados
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

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Erro de conexão: " . $e->getMessage()]);
    exit;
}

// ✅ Obtém o ID do usuário logado
$user_id = $_SESSION['user_id'];

// ✅ Salvar configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_config') {
    try {
        // Captura os valores enviados pelo formulário
        $backgroundColor = $_POST['background_color'] ?? '#ffffff';
        $titleColor = $_POST['title_color'] ?? '#000000';
        $cardColor = $_POST['card_color'] ?? '#ffffff';
        $cardTextColor = $_POST['card_text_color'] ?? '#000000';
        $categoryTextColor = $_POST['category_text_color'] ?? '#000000';
        $categoryButtonColor = $_POST['category_button_color'] ?? '#ffffff';
        $categoryButtonBgColor = $_POST['category_button_bg_color'] ?? '#cccccc';
        $workCardBgColor = $_POST['work_card_bg_color'] ?? '#ffffff';
        $workButtonColor = $_POST['work_button_color'] ?? '#ff0000'; // ✅ Nova cor do botão dos cards

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
            "work_button_color" => $userPreference['work_button_color'] ?? '#ff0000' // ✅ Retorna cor do botão dos cards
        ]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Erro ao recuperar as configurações: " . $e->getMessage()]);
    }
    exit;
}
?>
