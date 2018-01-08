<?php

/**
 * Gera uma Chave aleatória usando OPENSSL - BINARIA
 * em caso de falha usa random_bytes
 *
 * @param  integer $tmh O TAMANHO DA CHAVE, PADRÃO 128, MINIMO 32
 * @return string       A STRING DA CHAVE
 */
function ChaveSeguraAleatoria($tmh = 64, $hex = true) {// 32 = 256BITS
  $tmh = (intval($tmh) < 8) ? 8 : intval($tmh);

  $retorno = false;

  // TENTA PELO MELHOR METODO
  if ((defined('PHP_VERSION_ID')) && (PHP_VERSION_ID >= 70000) && (function_exists('\random_bytes'))) {
    $retorno = (@random_bytes($tmh));
  }

  // DEPOIS TENTA PELO PSEUDO SSL
  if (!$retorno) {
    if (function_exists('\openssl_random_pseudo_bytes')) {
      $rnd = @\openssl_random_pseudo_bytes($tmh, $strong);

      # CONFERINDO SE É FORTE...
      if ($strong === true) {
        $retorno = ($rnd);
      }
    }
  }

  // POR ULTIMO POR MYCRIP
  if (!$retorno) {
    if ((function_exists('\mcrypt_create_iv'))) {
      $retorno = (@mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
    }
  }

  if ($retorno) {
    return $hex ? bin2hex($retorno) : $retorno;
  }

  // SE CHEGOU AQUI, HA PROBLEMA
  throw new Exception("Não foi possível gerar uma Chave Aleatória Segura. Obrigatório a presença de OpenSSL ou extenção Mcrypt.");
  return false;
}

/*
 * 
 */
function validateMail($email) {
  return (preg_match('/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD', $email) === 1);
}

/*
 * 
 */

function openAt() {
  global $json_at;

  if (!$json_at) {
    $json_at = [];

    if (file_exists(".at")) {
      $json_at = file_get_contents(".at");

      if (!empty($json_at)) {
        $json_at = json_decode($json_at, true);

        if ((empty($json_at)) || (json_last_error() !== JSON_ERROR_NONE)) {
          $json_at = [];
        }
      } else
        $json_at = [];
    }
  }
}

/*
 * 
 */

function otp($check = true) {
  global $_SESSION, $____jahverificadoOTP, $____jahverificadoOTP_vlr;

  if ($____jahverificadoOTP) {
    return $____jahverificadoOTP_vlr;
  } else {
    $____jahverificadoOTP = true;

    $otp = (array_key_exists('fastauth_OTP', $_SESSION) ? $_SESSION['fastauth_OTP'] : $_SESSION['fastauth_OTP'] = []);
    $_SESSION['fastauth_OTP'] = [];

    /* PRESUME FALHA */
    $retorno = false;

    if ($check) {
      if (
              (array_key_exists('tkg-k', $otp)) && (!empty($otp["tkg-k"])) &&
              (array_key_exists($otp['tkg-k'], $_COOKIE)) && (!empty($_COOKIE[$otp['tkg-k']])) &&
              (is_array($otp[$_COOKIE[$otp['tkg-k']]]))
      ) {
        /* FAZENDO AS VERIFICAÇÕES */
        if (
                (count($otp[$_COOKIE[$otp['tkg-k']]]) === 3) &&
                ((time() - $otp[$_COOKIE[$otp['tkg-k']]][0]) < (10 * 60)) &&
                ($otp[$_COOKIE[$otp['tkg-k']]][1] === $_SERVER['REMOTE_ADDR']) &&
                ($otp[$_COOKIE[$otp['tkg-k']]][2] == @$_SERVER['HTTP_X_FORWARDED_FOR'])
        ) {
          $retorno = true;
        }

        /* ELIMINANDO O COOKIE USADO */
        setcookie($otp['tkg-k'], '', 1, null, null, ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443), true);
      }
    }

    $_SESSION['fastauth_OTP']['tkg-k'] = (ChaveSeguraAleatoria(64));

    /* A CHAVE DE SESSAO QUE CONTEM O TOKEN OTP */
    $_CHK = (ChaveSeguraAleatoria(64));

    setcookie($_SESSION['fastauth_OTP']['tkg-k'], $_CHK, time() + (10 * 60), null, null, ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443), true);

    /* O TOKEN OTP */
    $_SESSION['fastauth_OTP'][$_CHK] = [time(), $_SERVER['REMOTE_ADDR'], @$_SERVER['HTTP_X_FORWARDED_FOR']];

    $____jahverificadoOTP_vlr = $check ? $retorno : true;
    return $____jahverificadoOTP_vlr;
  }
}

/*
 * Esta funcao tem duas finalidade, se fornecidos os dois paramentros ela
 * verifica o usuario e o autentica, se nao, apenas verifique se o usuario jah
 * esta autenticado
 *
 * retorna 1. O nome do usuario (username/email) se jah estiver logado
 *         2. ZERO se passados os paramentros e a autenticacao funcioneou
 *            usamos zero para garantir que nao haja erro de checagem,
 *            com um falso false, assim use ===
 *         3. False para nenhum dos cados acima
 *
 * @param     $user   string  o nome do usuario (opicional)
 * @param     #pass   string  a senha o usuario (opicional)
 *
 * @return            mixed   retorna zerao de $user foi fornecido e conseguio
 *                            registrar a autenticacao
 */

function Auth($user = null, $pass = null, $cad = false) {
  global $__cadastro, $json_at;

  if ((!empty($user)) && (!empty($pass))) {
    $user = trim($user);
    $pass = trim($pass);

    if ((file_exists(".pu")) || $cad) {
      $json = (file_exists(".pu")) ? file_get_contents(".pu") : [];

      if (!empty($json) || $cad) {
        $json = (file_exists(".pu")) ? json_decode($json, true) : $json;

        if (((!empty($json)) && (json_last_error() === JSON_ERROR_NONE)) || $cad) {
          $key = strtoupper(hash_hmac('sha512', $user, hash('whirlpool', $user)));

          if ($cad) {
            $__cadastro = 0;           

            if (array_key_exists($cad, $json_at) && (trim($json_at[$cad]) === trim($user))) {            
              require_once 'lib\Passmeter\src\Passmeter.php';
              
              if (Passmeter::meter($pass, 'PT-BR')["approved"] && validateMail($user)) {                
                $timeTarget = 0.5; // 50 milliseconds

                $cost = 12;

                do {
                  $cost++;
                  $start = microtime(true);
                  password_hash("test", PASSWORD_BCRYPT, ["cost" => $cost]);
                  $end = microtime(true);
                } while (($end - $start) < $timeTarget);                
                
                $json[$key] = password_hash($pass, PASSWORD_DEFAULT, [
                    'cost' => $cost
                ]);
                
                
                if ((file_put_contents(".pu", json_encode($json)) > 2) && (json_last_error() === JSON_ERROR_NONE)) {                  
                  $__cadastro = true;

                  unset($json_at[$cad]);                  
                  
                  file_put_contents(".at", json_encode($json_at));                  

                  $__cadastro = 1;
                }
              }
            }
          } else {
            /*
             * FAZ A AUTENTICACAO
             */
            if (array_key_exists($key, $json)) {
              if (password_verify($pass, $json[$key])) {
                /* ATUALIZA O TEMPO DA ULTIMA CHECAGEM */
                $_SESSION['fastauth_auth']["lastchk"] = time();
                $_SESSION['fastauth_auth']['usr'] = $user;
                otp(false);

                return 0;
              }
            }
          }
        }
      }
    }
  } else if (
          array_key_exists('fastauth_auth', $_SESSION) &&
          array_key_exists('usr', $_SESSION['fastauth_auth']) &&
          (strlen(trim($_SESSION['fastauth_auth']['usr'])) >= 8) &&
          array_key_exists('lastchk', $_SESSION['fastauth_auth']) &&
          ((time() - $_SESSION['fastauth_auth']["lastchk"]) < (10 * 60)) &&
          (otp())
  ) {    
    /* ATUALIZA O TEMPO DA ULTIMA CHECAGEM */
    $_SESSION['fastauth_auth']["lastchk"] = time();

    return $_SESSION['fastauth_auth']['usr'];
  }

  return false;
}
