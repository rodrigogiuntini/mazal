<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

// --- Extração do parâmetro "name" da URL amigável ---
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestUri = trim($requestUri, '/');
if (!empty($requestUri) && $requestUri !== 'index.php') {
    $_GET['name'] = $requestUri;
}

if (!isset($_GET['name'])) {
    echo "Parâmetro 'name' não definido.";
    exit;
}

$nomeUsuario = $_GET['name'];

// --- Conexão com o banco de dados ---
$host    = 'ip-45-79-13-239.cloudezapp.io';
$db      = 'mazal';
$dbUser  = 'mazal';
$dbPass  = 'Rodrigo2012@';
$charset = 'utf8mb4';

$conn = new mysqli($host, $dbUser, $dbPass, $db);
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}
$conn->set_charset($charset);

// --- Consulta dos dados do usuário ---
$stmt = $conn->prepare("SELECT * FROM users WHERE name = ?");
$stmt->bind_param("s", $nomeUsuario);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $userData = $result->fetch_assoc();
} else {
    echo "Usuário não encontrado.";
    exit;
}
$stmt->close();

// Para compatibilidade com o HTML (que utiliza $user), definimos:
$user = $userData;

// --- Valores padrão para exibição ---
$userTitle         = 'seu titulo';
$backgroundColor   = "#ffffff";
$userBio           = "";
$userSocials       = [];
$userCategories    = [];
$imagePath         = 'assets/img/logo23.png';
$cards             = [];
$homeTitleColor    = "#000000";
$categoryButtonColor = "#ffffff";
$categoryTextColor = "#000000";
$workCardBgColor   = "#ffffff";
$workCardTextColor = "#000000";

// --- Recupera o título do usuário ---
$stmt_title = $conn->prepare("SELECT `title` FROM `home_title` WHERE `user_id` = ?");
$stmt_title->bind_param("i", $user['id']);
$stmt_title->execute();
$result_title = $stmt_title->get_result();
if ($result_title->num_rows > 0) {
    $titleInfo = $result_title->fetch_assoc();
    $userTitle = $titleInfo['title'];
}
$stmt_title->close();

// --- Recupera a cor de fundo ---
$stmt_bg = $conn->prepare("SELECT `background_color` FROM `user_preferences` WHERE `user_id` = ?");
$stmt_bg->bind_param("i", $user['id']);
$stmt_bg->execute();
$result_bg = $stmt_bg->get_result();
if ($result_bg->num_rows > 0) {
    $bgInfo = $result_bg->fetch_assoc();
    if (!empty($bgInfo['background_color'])) {
        $backgroundColor = htmlspecialchars($bgInfo['background_color']);
    }
}
$stmt_bg->close();

// --- Recupera a biografia ---
$stmt_bio = $conn->prepare("SELECT `bio` FROM `user_bio` WHERE `user_id` = ?");
$stmt_bio->bind_param("i", $user['id']);
$stmt_bio->execute();
$result_bio = $stmt_bio->get_result();
if ($result_bio->num_rows > 0) {
    $bioInfo = $result_bio->fetch_assoc();
    $userBio = $bioInfo['bio'];
}
$stmt_bio->close();

// --- Recupera as redes sociais ---
$stmt_socials = $conn->prepare("SELECT `platform`, `link`, `icon_class` FROM `user_socials` WHERE `user_id` = ?");
$stmt_socials->bind_param("i", $user['id']);
$stmt_socials->execute();
$result_socials = $stmt_socials->get_result();
while ($social = $result_socials->fetch_assoc()) {
    $userSocials[] = $social;
}
$stmt_socials->close();

// --- Recupera as categorias ---
$stmt_categories = $conn->prepare("SELECT `name` FROM `categories` WHERE `user_id` = ?");
$stmt_categories->bind_param("i", $user['id']);
$stmt_categories->execute();
$result_categories = $stmt_categories->get_result();
while ($category = $result_categories->fetch_assoc()) {
    $userCategories[] = $category['name'];
}
$stmt_categories->close();

// --- Recupera a imagem de perfil ---
$stmt_image = $conn->prepare("SELECT `image_path` FROM `profile_images` WHERE `user_id` = ?");
$stmt_image->bind_param("i", $user['id']);
$stmt_image->execute();
$result_image = $stmt_image->get_result();
if ($result_image->num_rows > 0) {
    $imageInfo = $result_image->fetch_assoc();
    if (!empty($imageInfo['image_path'])) {
        $imagePath = htmlspecialchars($imageInfo['image_path']);
    }
}
$stmt_image->close();

// --- Recupera os cards do usuário ---
$stmt_cards = $conn->prepare("SELECT * FROM cards WHERE user_id = ?");
$stmt_cards->bind_param("i", $user['id']);
$stmt_cards->execute();
$result_cards = $stmt_cards->get_result();
while ($card = $result_cards->fetch_assoc()) {
    $cards[] = $card;
}
$stmt_cards->close();

// --- Para os cards do tipo "tipo3", recupera as imagens associadas ---
foreach ($cards as &$card) {
    if ($card['layout_type'] == "tipo3") {
        $stmtImages = $conn->prepare("SELECT image_path FROM card_images WHERE card_id = ?");
        $stmtImages->bind_param("i", $card['id']);
        $stmtImages->execute();
        $cardImages = $stmtImages->get_result()->fetch_all(MYSQLI_ASSOC);
        if (!empty($cardImages)) {
            $card['images'] = $cardImages;
        }
        $stmtImages->close();
    }
}

// --- Recupera a cor do título ---
$stmt_color = $conn->prepare("SELECT `title_color` FROM `user_preferences` WHERE `user_id` = ?");
$stmt_color->bind_param("i", $user['id']);
$stmt_color->execute();
$result_color = $stmt_color->get_result();
if ($result_color->num_rows > 0) {
    $colorInfo = $result_color->fetch_assoc();
    if (!empty($colorInfo['title_color'])) {
        $homeTitleColor = htmlspecialchars($colorInfo['title_color']);
    }
}
$stmt_color->close();

// --- Recupera as cores dos botões de categoria ---
$stmt_colors = $conn->prepare("SELECT `category_button_color`, `category_text_color` FROM `user_preferences` WHERE `user_id` = ?");
$stmt_colors->bind_param("i", $user['id']);
$stmt_colors->execute();
$result_colors = $stmt_colors->get_result();
if ($result_colors->num_rows > 0) {
    $colorInfo = $result_colors->fetch_assoc();
    if (!empty($colorInfo['category_button_color'])) {
        $categoryButtonColor = htmlspecialchars($colorInfo['category_button_color']);
    }
    if (!empty($colorInfo['category_text_color'])) {
        $categoryTextColor = htmlspecialchars($colorInfo['category_text_color']);
    }
}
$stmt_colors->close();

// --- Recupera as cores dos cards de trabalho ---
$stmt_work_card_colors = $conn->prepare("SELECT `work_card_bg_color`, `work_card_text_color` FROM `user_preferences` WHERE `user_id` = ?");
$stmt_work_card_colors->bind_param("i", $user['id']);
$stmt_work_card_colors->execute();
$result_work_card_colors = $stmt_work_card_colors->get_result();
if ($result_work_card_colors->num_rows > 0) {
    $colorInfo = $result_work_card_colors->fetch_assoc();
    if (!empty($colorInfo['work_card_bg_color'])) {
        $workCardBgColor = htmlspecialchars($colorInfo['work_card_bg_color']);
    }
    if (!empty($colorInfo['work_card_text_color'])) {
        $workCardTextColor = htmlspecialchars($colorInfo['work_card_text_color']);
    }
}
$stmt_work_card_colors->close();

// --- Função para converter link do YouTube em iframe embed ---
function convertLinkToEmbedIframe($link) {
    if (strpos($link, 'youtube.com') !== false || strpos($link, 'youtu.be') !== false) {
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $link, $matches)) {
            $video_id = $matches[1];
            return '<iframe width="100%" height="100%" src="https://www.youtube.com/embed/' . $video_id . '" title="Welinnk" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
        }
    }
    return false;
}

// Opcional: feche a conexão se não for mais necessária
// $conn->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
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

    
    <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>


    <!-- CSS do Bootstrap -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">



    <!--==================== CSS ====================-->
    <link rel="stylesheet" href="assets/css/dash.css">



    <title><?php echo htmlspecialchars($userTitle); ?></title>
</head>


<body style="background-color: <?php echo $backgroundColor; ?>;">



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

.work__item {
        background-color: <?php echo $categoryButtonColor; ?> !important;
        color: <?php echo $categoryTextColor; ?> !important;
        padding: 10px 20px;
        border-radius: 5px;
        font-weight: bold;
        text-transform: uppercase;
        cursor: pointer;
        border: none;
    }

    .work__item:hover {
        opacity: 0.8;
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

.home__title{
    margin-left: -1rem;
    padding-top: 1rem;
}


.home__data {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    width: 100%;
    max-width: 90%;
    margin: 0 auto;
}


.home__description {
    text-align: center;
    max-width: 80%;
    word-wrap: break-word;
    margin-top: 10px;
    line-height: 1.5;
    display: flex;
    justify-content: center;
}

.work__filters {
    display: flex;
    justify-content: center;
    gap: 15px; /* Espaço entre os botões */
    margin-top: 15px; /* Distância da bio */
}


/* Ajuste dos botões */
.work__item {
    background-color: <?php echo $categoryButtonColor; ?> !important;
    color: <?php echo $categoryTextColor; ?> !important;
    padding: 12px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: bold;
    text-transform: uppercase;
    cursor: pointer;
    border: none;
    transition: all 0.3s ease-in-out;
    display: inline-block;
    min-width: 120px;
    text-align: center;
}



.home__container {
    display: flex;
    flex-direction: column; /* Coloca os elementos em coluna */
    align-items: center; /* Centraliza na horizontal */
    justify-content: center; /* Centraliza na vertical */
    text-align: center; /* Centraliza o texto */
   
}

.home__description {
    text-align: center !important;
    max-width: 80% !important;
    margin: 0 auto !important;
    line-height: 1.5 !important;
    
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    
    min-height: 50px !important; /* Mantém altura mínima */
    width: 100% !important; /* Garante largura completa */
    word-wrap: break-word !important;
    white-space: normal !important;
    padding: 0.5rem !important; /* Um pouco de espaçamento para não ficar colado */
}

.home__data {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    width: 100% !important;
    min-height: 150px !important; /* Isso mantém um espaço adequado */
}

.section {
    padding: 1rem 0 1rem !important;
}



.work__filters {
    display: flex;
    flex-wrap: wrap; /* Impede que quebre a linha caso haja espaço suficiente */
    justify-content: center; /* Centraliza os botões */
    gap: 10px; /* Espaçamento entre os botões */
}

.work__item {
    white-space: nowrap; /* Impede a quebra de linha dentro do botão */
    padding: 10px 15px;
    border-radius: 8px;
    display: inline-block; /* Mantém os botões no mesmo nível */
    text-align: center;
}

@media (max-width: 768px) { /* Ajusta para telas menores */
    .work__filters {
        flex-wrap: wrap; /* Permite a quebra se necessário */
    }
}

 /* Estilização dos cards */
 .work__card {
    background-color: <?php echo $workCardBgColor; ?> !important;
    color: <?php echo $workCardTextColor; ?> !important;
   
    }

    .work__card h3 {
        <?php echo $workCardTextColor; ?> !important;
    }


   

/* Ajustar nome dentro do card */
.work__card h3 {
    font-size: 1rem;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    width: 90%;
}


.work__button {
    position: absolute;
    left: 308px;
    bottom: 2;
    /* border-radius: 50%; */
    /* text-decoration: none; */
    display: flex;
   
    justify-content: center;
    width: 36px;
    height: 80px;
   
}
   
/* Ajuste para evitar quebra de linha estranha */
.work__filter-wrapper {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
}

/* Responsividade para telas menores */
@media (max-width: 768px) {
    .work__item {
        min-width: 100px; /* Reduz tamanho mínimo em telas pequenas */
        font-size: 12px;
        padding: 10px 15px;
    }
}
 
.home__data {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center; /* Centraliza o texto */
    width: 100%;
    max-width: 80%; /* Define um limite para não ficar muito largo */
    margin: 0 auto; /* Centraliza horizontalmente */
}

.home__description {
    text-align: center; /* Garante que o texto fique centralizado */
    max-width: 80%; /* Limita a largura para manter leitura confortável */
    word-wrap: break-word; /* Evita que palavras longas estourem o layout */
    margin-top: 10px; /* Espaço superior para melhor espaçamento */
    line-height: 1.5; /* Melhora a legibilidade */
    display: flex;
    justify-content: center; /* Mantém o texto centralizado no contêiner */
}

/* Ajuste da bio para centralização */
/* Ajuste para a BIO */
.home__description {
    text-align: center !important;
    max-width: 90% !important; /* Mantém a bio mais larga */
    width: fit-content !important; /* Ajusta conforme o tamanho do texto */
    white-space: nowrap !important; /* Impede que o texto quebre */
    overflow: hidden !important; /* Evita rolagem */
    text-overflow: ellipsis !important; /* Caso o texto seja muito longo, adiciona "..." */
    padding: 1rem !important;
    margin: 0 auto !important;
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    min-height: 10px !important;
    word-wrap: normal !important;
    
}

.home__container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    width: 100%;
}
/* Ajuste do contêiner da bio */
.home__data {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    width: 100%;
}
/* Ajuste do contêiner de filtros */
.work__filters {
    display: flex;
    justify-content: center; /* Centraliza horizontalmente */
    flex-wrap: wrap; /* Permite que os botões quebrem linha, se necessário */
    gap: 10px; /* Espaçamento entre os botões */
}

/* Ajuste dos botões */
.work__item {
    padding: 10px 15px;
    border-radius: 8px;
    text-align: center;
    font-weight: bold;
    text-transform: uppercase;
    border: 2px solid #000;
    background-color: var(--category-button-bg-color);
    color: var(--category-text-color);
    white-space: nowrap; /* Evita quebra de linha nos botões */
}


.container {
    margin-left: 0 !important;
    margin-right: auto !important;
    width: 95% !important; /* Garante um espaço à direita */
}


/* Garante que toda a estrutura esteja mais à esquerda */
.container {
    margin-left: 0 !important;
    margin-right: auto !important;
    width: 95% !important; /* Diminui um pouco a largura */
}

/* Move o conteúdo da home para a esquerda */
.home__data {
    text-align: left !important;
    display: flex;
    flex-direction: column;
    align-items: flex-start !important;
    padding-left: 2.5rem !important; /* Ajuste fino */
}

@media screen and (max-width: 450px) {
    .home__img {
        display: flex
;
        margin-left: 6.5rem;
    }
}


/* Move os botões de categoria mais para a esquerda */
.work__filters {
    justify-content: flex-start !important;
    margin-left: 4rem !important;
}

/* Alinha os cards um pouco mais à esquerda */
.work__container {
    justify-content: flex-start !important;
    display: flex !important;
    flex-wrap: wrap !important;
    padding-left: 2.5rem !important;
}

/* Ajusta o alinhamento dos cards */


/* Garante que os botões do filtro e os cards fiquem alinhados */
.work__item {
    margin-left: 0 !important;
}
@media (max-width: 768px) {
    .home__description {
    width: 95% !important;
    font-size: 1rem !important;
    padding: 0rem !important;
    margin-left: 4rem !important;
    flex-wrap: wrap !important; /* Permite quebra de linha caso necessário */
}
}



</style>
<!--==================== HEADER ====================-->
<header class="header" id="header">
       
     
       
    </header>

   <!-- <i style="padding: 2rem 2rem; display: block; color: var(--cor-x);"  class="uil uil-moon change-theme" id="theme-button"></i>-->

<!--==================== MAIN ====================-->
<main  class="main">
  
    <!--==================== HOME ====================-->
    <section class="home section" id="home">
        <div class="home__container container grid">
            <div class="home__content grid">
                

            <div class="home__img">
    <?php
    $imagePath = 'assets/img/logo23.png'; // Imagem padrão caso não haja uma no banco

    if (!empty($user['id'])) {
        $stmt = $conn->prepare("SELECT `image_path` FROM `profile_images` WHERE `user_id` = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $imageInfo = $result->fetch_assoc();
            if (!empty($imageInfo['image_path'])) {
                $imagePath = htmlspecialchars($imageInfo['image_path']);
            }
        }
        $stmt->close();
    }
    ?>
    <img src="<?php echo $imagePath; ?>" class="home__blob-img" alt="Imagem de Perfil">
</div>



            <!-- Modal para Editar/Adicionar Imagem de Perfil -->

  
  
            
            <div class="home__data">
                
                
            
            <h1 class="home__title" style="color: <?php echo $homeTitleColor; ?>;">
    <?php echo htmlspecialchars($userTitle); ?>
</h1>


               <br>
             
</div>

  
  
<p class="home__description" style="color: <?php echo htmlspecialchars($homeTitleColor); ?>;">
    <?php echo htmlspecialchars($userBio); ?>
</p>

                <div class="modal fade" id="myModal2">
                    <div class="modal-dialog">
                      <div class="modal-content">
                  
                        <!-- Cabeçalho do Modal -->
                        <div class="modal-header">
                          <h4 class="modal-title">Editar Bio</h4>
                          <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                  
                        <!-- Corpo do Modal -->
                        <div class="modal-body">
                          <input type="text" class="form-control" placeholder="Seu texto" id="nomeInput">
                        </div>
                  
                        <!-- Rodapé do Modal -->
                        <div class="modal-footer">
                          <button style="border-radius: 0.40rem;" type="button" class="btn btn-primary" onclick="salvarNome()">Salvar</button>
                          <button style="border-radius: 0.40rem;" type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                        </div>
                  
                      </div>
                    </div>
                  </div>
                <div class="home__social">
                <?php
foreach ($userSocials as $social) {
    echo "<a href='" . htmlspecialchars($social['link']) . "' target='_blank' class='home__social-icon'>";
    echo "<i class='" . htmlspecialchars($social['icon_class']) . "' data-aos='fade-up' data-aos-duration='1000'></i>";
    echo "</a>";
}
?>


  
                   
                    
                </div>
                   <br>
               
                  
                
            </div>
        </div>

        <div class="home__scroll">
            <a href="#about" class="home__scroll-button button--flex">
                <i class="uil uil-mouse-alt home__scroll-mouse"></i>
                <span class="home__scroll-name">Arraste Para Baixo</span>
                <i class="uil uil-arrow-down home__scroll-arrow"></i>
            </a>
        </div>
    </div>
</section>





<section class="work section container grid" id="work">
      
    <h2 class="section__title">
        
        </h2>
    

  
        <div class="work__filters container">
    <div class="work__filter-wrapper">
        <!-- Botão padrão: Tudo -->
        <button class="work__item active-work" data-filter="all">
            TUDO
        </button>

        <?php
        // Loop para exibir cada categoria corretamente
        foreach ($userCategories as $categoryName) {
            $filterName = strtolower(str_replace(' ', '-', $categoryName)); // Evita espaços e mantém SEO-friendly
            echo "<button class='work__item' data-filter='." . htmlspecialchars($filterName) . "'>";
            echo htmlspecialchars($categoryName);
            echo "</button>";
        }
        ?>
    </div>
</div>


  
  </div>

  <div class="work__container container grid">
    <?php
    if (isset($user) && $user['id']):
        $stmt_cards = $conn->prepare("SELECT * FROM cards WHERE user_id = ?");
        $stmt_cards->bind_param("i", $user['id']);
        $stmt_cards->execute();
        $result_cards = $stmt_cards->get_result();
        $cards = $result_cards->fetch_all(MYSQLI_ASSOC);
        $stmt_cards->close();

        foreach ($cards as $card):
            $stmt_category = $conn->prepare("SELECT name FROM categories WHERE id = ?");
            $stmt_category->bind_param("i", $card['category_id']);
            $stmt_category->execute();
            $result_category = $stmt_category->get_result();
            $category = $result_category->fetch_assoc();
            $stmt_category->close();
            $category_name = $category ? $category['name'] : 'Categoria desconhecida';

            switch ($card['layout_type']) {
                case "tipo1":
                    echo "<div class='work__card grid-container mix " . htmlspecialchars($category_name) . "' style='background-color: $workCardBgColor; color: $workCardTextColor;'>";
                    echo "<h3 class='work__title' style='color: $workCardTextColor;'>" . htmlspecialchars($card['name']) . "</h3>";
                    echo "<a href='" . htmlspecialchars($card['link']) . "' class='work__button'>";
                    echo '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" id="plus" >';
                    echo "<path fill='#000' fill-rule='evenodd' d='M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10ZM11 8a1 1 0 1 1 2 0v3h3a1 1 0 1 1 0 2h-3v3a1 1 0 1 1-2 0v-3H8a1 1 0 1 1 0-2h3V8Z' clip-rule='evenodd'></path>";
                    echo "</svg></a>";
                    echo "</div>";
                    break;

                case "tipo2":
                    echo "<div class='work__card2 mix " . htmlspecialchars($category_name) . "' style='background-color: $workCardBgColor; color: $workCardTextColor;'>";
                    echo "<video width='100%' height='100%' controls>";
                    echo "<source src='" . htmlspecialchars($card['video_path']) . "' type='video/mp4'>";
                    echo "Seu navegador não suporta a tag de vídeo.";
                    echo "</video>";
                    echo "</div>";
                    break;
                
                case "tipo3":
                    $stmt_images = $conn->prepare("SELECT image_path FROM card_images WHERE card_id = ?");
                    $stmt_images->bind_param("i", $card['id']);
                    $stmt_images->execute();
                    $result_images = $stmt_images->get_result();
                    $images = $result_images->fetch_all(MYSQLI_ASSOC);
                    $stmt_images->close();

                    if ($images) {
                        echo "<div class='swiper-container work__card3 mix " . htmlspecialchars($category_name) . "' style='background-color: $workCardBgColor; color: $workCardTextColor;'>";
                        echo "<div class='swiper-wrapper'>";
                        
                        foreach ($images as $image) {
                            echo "<div class='swiper-slide'>";
                            echo "<img src='" . htmlspecialchars($image['image_path']) . "' class='work__img'>";
                            echo "</div>";
                        }
                        
                        echo "</div>";  // Fechar swiper-wrapper
                        
                        // Adicionar controles de navegação
                        echo "<div class='work__card2-button-next'></div>";
                        echo "<div class='work__card2-button-prev'></div>";
                        
                        echo "</div>";  // Fechar swiper-container
                    }
                    break;
                
                case "tipo4":
                    if (!empty($card['video_path'])) {
                        $videoLink = $card['video_path'];
                        $iframeCode = convertLinkToEmbedIframe($videoLink);

                        if ($iframeCode) {
                            echo "<div class='work__card2 mix " . htmlspecialchars($category_name) . "' style='background-color: $workCardBgColor; color: $workCardTextColor;'>";
                            echo $iframeCode;
                            echo "</div>";
                        } else {
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
 

</main>






<!--==================== FOOTER ====================-->



<!--==================== SCROLL TOP ====================-->
<a href="#" class="scrollup" id="scroll-up">
    <i class="uil uil-arrow-up scrollup__icon"></i>
   
</a>


<!--==================== SWIPER JS ====================-->
<script src="assets/js/swiper-bundle.min.js"></script>

 <!--=============== MIXITUP FILTER ===============-->
 <script src="assets/js/mixitup.min.js"></script>

 <!-- JS, Popper.js e jQuery do Bootstrap -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
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

    // Inicialização do Swiper para cards (tipo3)
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
</script>
</body>
</html>
