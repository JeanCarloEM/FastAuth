<?php
require_once 'func.php';

if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

  global $__cadastro, $__falha, $json_at;

  $__falha = false;
  $__cadastro = false;

/*
 * PROGRAMA ...
 */
if (strlen($from = Auth()) >= 8) {  
  /* SE PEDIDO, REGISTRA CODIGO DE AUTORIZAÇÃO */
  if (
          (array_key_exists("fastauth_add_inc", $_REQUEST)) &&
          (!empty($_REQUEST["fastauth_add_inc"])) &&
          /* VALIDANDO EMAIL */
          validateMail(trim($_REQUEST["fastauth_add_inc"]))
  ) {    
    $email = trim($_REQUEST["fastauth_add_inc"]);
    $titulo = "Convide Cadastramento";
    $headers = apache_request_headers();

    global $json_at;
    openAt();

    /* PROCURA VER SE O EMAIL JAH ESTA AUTORIZADO */
    $code = array_search($email, $json_at);

    if (!$code || (!is_string($code)) || (strlen($code) < 32)) {
      $code = ChaveSeguraAleatoria();
      $json_at[$code] = $email;
      file_put_contents(".at", json_encode($json_at));
    }

    $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $url = (strpos($url, '?') !== false) ? substr($url, 0, strpos($url, '?')) : $url;
    $url .= "?inc=" . $code;

    $data = date("d/m/Y, H:i:s");

    $html = <<<EOF
<!DOCTYPE html>
<html>
  <head>
    <title>{$titulo}</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  </head>
  <body style="font-family: 'sans-serif';">
    <div style="font-family: 'sans-serif';">
      <p style="font-family: 'sans-serif';">Olá,</p>
      <p style="font-family: 'sans-serif';">Você foi convidado / autorizado a registrar-se em <b>{$headers['Host']}</b>. Para efetivar seu cadastro acesse a url abaixo copiando-a no navegador:</p>
      <p style="font-family: 'sans-serif';"><big><b>{$url}</b></big></p>
      <p style="font-family: 'sans-serif';">Caso desconheça isto, por favor desconsidere este. Pode te havido algum erro de digitação.</p>
      <p style="font-family: 'sans-serif';">{$data}, Atenciosamente.</p>
    </div>
  </body>
</html>
EOF;

    mail($email, $titulo, str_replace("\n.", "\n..", $html), "From:$from");

    $html = <<<EOF
<!DOCTYPE html>
<html>
  <head>
    <title>Enviado: {$titulo}</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  </head>
  <body style="font-family: 'sans-serif';">
    <div style="font-family: 'sans-serif';">
      <p style="font-family: 'sans-serif';">Olá,</p>
      <p style="font-family: 'sans-serif';">Seu convite / autorização para acesso à <b>{$headers['Host']}</b>, foi enviado com sucesso à <big><b>{$email}</b></big>.</p>
      <p style="font-family: 'sans-serif';">Caso desconheça isto, por favor desconsidere este. Pode te havido algum erro de digitação.</p>
      <p style="font-family: 'sans-serif';">Atenciosamente, {$data}.</p>
    </div>
  </body>
</html>
EOF;

    mail($from, "Enviado: $titulo", str_replace("\n.", "\n..", $html), "From:$from");            
        
    die ("<p class='notify green'>Usuário autorizado e e-mail enviado ao mesmo. Redirecionando...</p><script>window.setTimeout('location = \"?\";', 7000);</script>");
  }
} else {    
  $auth = (array_key_exists('fastauth_auth', $_SESSION) ? $_SESSION['fastauth_auth'] : $_SESSION['fastauth_auth'] = []);
  $_SESSION['fastauth_auth'] = [];

  /* ABRE OS AUTORIZADOS A REGISTRAR  */
  openAt();

  if ((array_key_exists('fast_auth_form', $_POST)) && (!empty($_POST["fast_auth_form"]))){    
    $__falha = true;
            
    if (
            (array_key_exists('tkg-k', $auth)) && (!empty($auth["tkg-k"])) && ($_POST) &&
            (array_key_exists($auth['tkg-k'], $_COOKIE)) && (!empty($_COOKIE[$auth['tkg-k']])) &&
            (array_key_exists('tkg', $_POST)) && (!empty($_POST["tkg"])) &&          
            (otp())
    ) {
      /* VALIDACAO REALIZADA COM SUCESSO? */
      if ($auth[$_COOKIE[$auth['tkg-k']]] === $_POST['tkg']) {
        if (
                (array_key_exists('nm', $_POST)) && (!empty($_POST["nm"])) &&
                (array_key_exists('pwd', $_POST)) && (!empty($_POST["pwd"]))
        ) {
          if ((array_key_exists('act', $_POST) && ($_POST["act"] === 'inc')) || (array_key_exists('inc', $_POST) && (!empty($_POST["inc"])))) {
            Auth(trim($_POST['nm']), trim($_POST['pwd']), trim($_POST['inc']));

            if ($__cadastro) {
              header('location: ?fastauth_cadsucess');
            }
          } else if (Auth(trim($_POST['nm']), trim($_POST['pwd'])) === 0) {
            /* AUTENTICACAO REALIZADA COM SUCESSO */
            header('location: ?');
            return;
          }
        }       
      }

      /* ELIMINANDO COOKIE JAH USADO */
      setcookie($auth['tkg-k'], '', 1, null, null, ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443), true);
    }
  }
  

  /* A CHAVE COOKIE DIFERENTE PARA CADA TENTATIVA - ESTE COOKIE
   * CONTEM A CHAVE DE SESSION QUE CONTEM O TOKEN
   */
  $_SESSION['fastauth_auth']['tkg-k'] = (ChaveSeguraAleatoria(32));

  /* A CHAVE DE SESSAO QUE CONTEM O TOKEN PARA FORMULARIO HTML */
  $_CHK = (ChaveSeguraAleatoria(64));

  setcookie($_SESSION['fastauth_auth']['tkg-k'], $_CHK, time() + (2 * 60), null, null, ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443), true);

  /* O TOKEN PARA FORMULARIO HTML */
  $_SESSION['fastauth_auth'][$_CHK] = (ChaveSeguraAleatoria(96));
  ?><!DOCTYPE html>
  <html>
    <head>
      <title>Atenticação Necessária</title>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link href="https://fonts.googleapis.com/css?family=Noto+Sans" rel="stylesheet">
      <link href="lib/FastAuth/www/assets/css/login.css" rel="stylesheet">

      <?php
      $codenovo = false;

      if ((array_key_exists("fastauth_inc", $_REQUEST)) && (!empty($_REQUEST['fastauth_inc']))) {
        if (array_key_exists($_REQUEST['fastauth_inc'], $json_at)) {
          $codenovo = $json_at[$_REQUEST['fastauth_inc']];
        }
      }

      if ($codenovo) {
        ?>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <script type="text/javascript" src="lib\FastAuth\www\lib\Passmeter\src\Passmeter.js"></script>

        <script type="text/javascript">
          function dateCheck(form) {
            if (!Passmeter.meter($($('input[name=pwd]')[0]).val()).approved) {
              return false;
            }

            return true;
          }

          (function ($) {
            $(document).ready(function () {
              $($('input[name=pwd]')[0]).on('keyup', function () {
                if (Passmeter.meter($(this).val()).approved) {
                  $(this).removeClass('alert error');
                } else {
                  $(this).addClass('alert error');
                }
              });
            });
          })(jQuery);
        </script>
      <?php } ?>
    </head>
    <body>
      <div class="painel">
        <form class="lfm" method="post" onsubmit="return dateCheck(this);">
          <input type='hidden' name='fast_auth_form' value='fast_auth_form'>
          <input type='hidden' name='tkg' value='<?php echo $_SESSION['fastauth_auth'][$_CHK]; ?>'>
          <?php
          if ($codenovo && (!array_key_exists('fastauth_cadsucess', $_GET))) {
            echo ($__cadastro === 0) ? "<p class='notify'>Falha ao registrar usuário. Verifique se o email informado é o mesmo que recebeu o código e se a senha atende os requisitos.</p>" : "";
            ?>
            <input type='hidden' name='act' value='inc'>
            <input type='hidden' name='inc' value='<?php echo $_REQUEST['fastauth_inc']; ?>'>            
            <input type="email" placeholder="Usuário" name='nm' required />
            <input type="password" placeholder="Senha" name='pwd' required />
            
            <p>A senha deve se composta de pelo menos:</p>
            
            <ul>
              <li>8 digitos;</li>
              <li>Um número;</li>
              <li>Uma letra maiúscula;</li>
              <li>Uma letra minúscula;</li>              
              <li>Um símbolo;</li>
            </ul>
            
            <button>Ir</button>
            <?php
          } else {            
            echo ($__falha) ? "<p class='notify'>Nome de usuário e/ou senha invalido(s).</p>" : (array_key_exists('fastauth_cadsucess', $_GET) ? "<p class='notify green'>Cadastro realizado com sucésso!</p>" : "");
            ?>            
            <input type="email" placeholder="Usuário" name='nm' required />
            <input type="password" placeholder="Senha" name='pwd' required />
            <button>Logar</button>
            <?php
          }
          ?>
        </form>
                        
        <p class="about">Powered with <a href="https://github.com/JeanCarloEM/Passmeter"><b>Passmeter</b></a><br /><b><a href='https://github.com/JeanCarloEM/FastAuth'>FastAuth</a> | &copf; <a href='//jeancarloem.com'>Jean Carlo EM</a> | <a href='https://www.mozilla.org/en-US/MPL/2.0/'>MPL 2.0+</a></b></p>
      </div>
    </body>
  </html><?php
  die();
}