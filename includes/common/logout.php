<?php 
 /*                        Copyright 2023 Flávio Ribeiro

         This file is part of OCOMON.

         OCOMON is free software; you can redistribute it and/or modify
         it under the terms of the GNU General Public License as published by
         the Free Software Foundation; either version 3 of the License, or
         (at your option) any later version.

         OCOMON is distributed in the hope that it will be useful,
         but WITHOUT ANY WARRANTY; without even the implied warranty of
         MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
         GNU General Public License for more details.

         You should have received a copy of the GNU General Public License
         along with Foobar; if not, write to the Free Software
         Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
  */
        session_start();

        require_once __DIR__ . "/" . "../functions/dbFunctions.php";
        require_once __DIR__ . "/" . "../config.inc.php";
        require_once __DIR__ . "/" . "../classes/ConnectPDO.php";
        use includes\classes\ConnectPDO;
        $conn = ConnectPDO::getInstance();

        $configs = getConfigValues($conn);

        unset($_SESSION);
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"],$params["httponly"]);
        }

        session_destroy();


        if ($configs['AUTH_TYPE'] == "OIDC") {
            header("Location: {$configs['OIDC_LOGOUT_URL']}");
            exit;
        }

        echo "<script>top.window.location = '../../login.php'</script>";
        header("Location: ../../login.php");
?>