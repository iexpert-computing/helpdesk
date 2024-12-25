<?php 
session_start();

	require_once (__DIR__ . "/../include_basics_only.php");
	require_once __DIR__ . "/" . "../classes/ConnectPDO.php";
	use includes\classes\ConnectPDO;

	if ($_SESSION['s_logado'] != 1) {
		exit;
	}


	$conn = ConnectPDO::getInstance();
	
	// Secure the user data by escaping characters 
	// and shortening the input string
	function clean($input, $maxlength) {
		$input = substr($input, 0, $maxlength);
		// $input = EscapeShellCmd($input);
		// $input = escapeshellcmd($input);
		return ($input);
	}

	$file = "";
	$file = clean($_GET['file'], 4);

	if (empty($file))
	exit;

	if (isset($_GET['cod'])) {
		$cod = (int)noHtml($_GET['cod']);
		
		$query = "SELECT * FROM imagens WHERE  img_cod = {$cod}";
	
		try {
			$result = $conn->query($query);
		}
		catch (Exception $e) {
			$erro = true;
			message('danger', 'Ooops!', TRANS('MSG_ERR_GET_DATA'), '', '', 1);
			return;
		}
		
		// $data = @ mysql_fetch_array($result);
		$data = $result->fetch();

		if (!empty($data["img_bin"])) {
			
			// Certifique-se de limpar os buffers de saída
			if (ob_get_length()) {
				ob_end_clean(); // Limpa o buffer de saída se houver
			}
			
			// Saída MIME header
			header("Content-Type: {$data["img_tipo"]}");
			// Saída da imagen
			echo $data["img_bin"];
		}
	}

	
?>
