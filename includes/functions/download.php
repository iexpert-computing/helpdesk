<?php 
session_start();


require_once (__DIR__ . "/../include_basics_only.php");
require_once __DIR__ . "/" . "../classes/ConnectPDO.php";
use includes\classes\ConnectPDO;

if ($_SESSION['s_logado'] != 1) {
    exit;
}

$conn = ConnectPDO::getInstance();

	//Secure the user data by escaping characters
	// and shortening the input string
function clean($input, $maxlength) {
    $input = substr($input, 0, $maxlength);
	// $input = EscapeShellCmd($input);
    return $input;
}

$file = "";
$file = clean($_GET['file'], 4);

if (empty($file)) {
    exit;
}

if (isset($_GET['cod'])) {
	
	// if id is set then get the file with the id from database
	
	
    $cod = (int)noHtml($_GET['cod']);
    $query = "SELECT img_nome, img_tipo, img_size, img_bin FROM imagens WHERE img_cod = {$cod}";

    try {
        $result = $conn->query($query);
    } catch (Exception $e) {
        $erro = true;
		echo TRANS('MSG_ERR_GET_DATA');
		exit();
    }

    $row = $result->fetch();

    if ($row) {
        // Certifique-se de limpar os buffers de saída
        if (ob_get_length()) {
            ob_end_clean(); // Limpa o buffer de saída se houver
        }

        // Enviar os cabeçalhos apropriados para download da imagem
        header("Content-Length: " . $row['img_size']);
        header("Content-Type: " . $row['img_tipo']);
        header("Content-Disposition: attachment; filename=" . $row['img_nome']);

        // Enviar o conteúdo binário da imagem
        echo $row['img_bin'];
    } else {
        echo 'Imagem não encontrada.';
    }
	
    exit;
}
?>
