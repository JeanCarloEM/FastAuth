<?php

/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 *
 * ============================================================================
 *
 * FastAuth
 * FastAuth é um enxuto, simples e fácil de usar (easy-to-use) autenticador em
 * PHP. Seu principal objetivo é eliminar o máximo a configuração, sendo simples
 * de colocar em qualquer aplicação PHP.
 *
 * @author     Jean Carlo de Elias Moreira | https://www.jeancarloem.com
 * @license    MPL2 | http://mozilla.org/MPL/2.0/.
 * @copyright  © 2017 Jean Carlo EM
 * @git        https://github.com/JeanCarloEM/FastAuth
 * @site       https://opensource.jeancarloem.com/FastAuth
 * @dependency Passmeter | https://github.com/JeanCarloEM/Passmeter
 */

namespace jeancarloem\FastAut;

use jeancarloem\FastAut as fa;

require_once "lib/UnicaInstancia.php";
require_once 'lib/Serverdata.php';
require_once "i18n/i18n.php";
require_once "lib/iBD.php";


/*
 *
 *
 */

class FastAuth implements fa\IUnicaInstancia {

  use fa\TUnicaInstancia;
  use fa\ServerData;

  private
  /* IDENTIFICA SE O OTP JAH FOI VERIFICADO */
          $otpVerificado = false,
          /* ARMAZENA O VALOR DO OTPFORM VERIFICADO
           * ANTERIORMENTE PARA EVITAR REEXECUCAO
           */
          $otpFormVerificado,
          /* OBJETO BD que implementa iBD */
          $bd,
          $msgs = [],
          /* IDENTIFICA SE HOUVE UMA TENTATIVA DE CADASTRO COM SUCESSO */
          $cadastrado = false;
  public static $config;
  public $cfg = [
      'BD' => 'jsonBD',
      'BD-PARAM' => null,
      'OTP-TIME' => 10 * 60,
      'SESS-TIME' => 10 * 60,
  ];

  const __REQUEST_CONTIVE__ = "fastauth_resetuser",
          __REQUEST_CADASTRAR__ = "fastauth_cadastrar",
          __REQUEST_AUTHFORM__ = "fastauth_form",
          __CODIGO_CADASTRAMENTO__ = 32;

  public static function addMsg(int $tipo, $msg) {
    $func = array(self::getInstancia(), "_" . __FUNCTION__);
    $retorno = \call_user_func($func, $tipo, $msg);
    return $retorno;
  }

  /*
   * Cadastra uma Mensagem a ser exibida no formulario
   *
   * @param   int     $tipo   Para falha 0, -1 para aviso (warning) e 1 para OK
   * @param   string  $msg    O texto a ser exibido
   */

  public function _addMsg(int $tipo, $msg) {
    $this->msgs = is_array($this->msgs) ? $this->msgs : [];
    $this->msgs[] = [$tipo, $msg];
  }

  public static function getMsgs() {
    $func = array(self::getInstancia(), "_" . __FUNCTION__);
    $retorno = \call_user_func($func);
    return $retorno;
  }

  public function _getMsgs() {
    return $this->msgs;
  }

  /*
   * ALIAS NAME STATIC PARA 'getConfig'
   *
   * @param   string  $key    a chave (index) de configuracao
   * @return  mixed           o valor da chave
   */

  public static function getCfg($key) {
    $func = array(self::getInstancia(), "getConfig");
    $retorno = \call_user_func($func, $key);
    return $retorno;
  }

  /*
   * @param   string  $key    a chave (index) de configuracao
   * @return  mixed           o valor da chave
   */

  private function getConfig($key) {
    return ((is_array(static::$config)) && (array_key_exists($key, static::$config))) ? static::$config[$key] : (
            ((is_array($this->cfg)) && (array_key_exists($key, $this->cfg))) ? $this->cfg[$key] : null
            );
  }

  /*
   *
   */

  private function createBDObject() {
    $bd = trim($this->getConfig('BD'));

    $path = __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'BDs' . DIRECTORY_SEPARATOR . "$bd.php";

    if (file_exists($path)) {
      require_once $path;

      $this->bd = new $bd($this->getConfig('BD-PARAM'));

      if (!($this->bd instanceof fa\iBD)) {
        throw new \Exception("O índice 'BD' de \$FastAuth_config deve ser uma nome de classe que implemente 'iBD'.");
      }
    } else {
      throw new \Exception("O índice 'BD' de \$FastAuth_config deve ser uma nome de classe VÁLIDAe que implemente 'iBD' com arquivo existente dentro da pasta 'lib/BDs'.");
    }
  }

  public static function BD() {
    $func = array(self::getInstancia(), "_" . __FUNCTION__);
    $retorno = \call_user_func($func);
    return $retorno;
  }

  public function &_BD() {
    return $this->bd;
  }

  /*
   *
   */

  public function __construct() {
    $this->initServerdata();
    $this->createBDObject();
  }

  /**
   * Gera uma Chave aleatória usando OPENSSL - BINARIA
   * em caso de falha usa random_bytes
   *
   * @param  integer $tmh O TAMANHO DA CHAVE, PADRÃO 128, MINIMO 32
   * @return string       A STRING DA CHAVE
   */
  public static function ChaveSeguraAleatoria($tmh = 64, $hex = true) {// 32 = 256BITS
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
    throw new \Exception("Não foi possível gerar uma Chave Aleatória Segura. Obrigatório a presença de OpenSSL ou extenção Mcrypt.");
    return false;
  }

  /*
   *
   *
   * @param   string  $email  o email a ser validados
   * @Return  bool            TRUE se validado
   */

  public static function validateMail($email) {
    return (preg_match('/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD', $email) === 1);
  }

  private function &otpFormData() {
    $this->sess()['OTP_FORM'] = array_key_exists('OTP_FORM', $this->sess()) ? $this->sess()['OTP_FORM'] : [];
    return $this->sess()['OTP_FORM'];
  }

  private function &otpData() {
    $this->sess()['OTP'] = array_key_exists('OTP', $this->sess()) ? $this->sess()['OTP'] : [];
    return $this->sess()['OTP'];
  }

  private function &userData() {
    $this->sess()['USER'] = array_key_exists('USER', $this->sess()) ? $this->sess()['USER'] : [];
    return $this->sess()['USER'];
  }

  /*
   */

  public static function OTPForm($check = false) {
    $func = array(self::getInstancia(), "_" . __FUNCTION__);
    $retorno = \call_user_func($func, $check);
    return $retorno;
  }

  /*
   * @param  string  $check  o token OTP recebido do formulario hidden
   * return  array           retorna um ARRAY
   *                          [0] = contem a informacao se o OTP eh valido
   *                                (se fornecido $check)
   *                                0     - para validacao com sucesso e,
   *                                FALSE - para falha
   *                          [1] = o token a ser informado no campo hidden
   *                                do formulario
   */

  public function _OTPForm($check = false) {
    $this->otpFormVerificado = (!$this->otpFormVerificado || !is_array($this->otpFormVerificado)) ? [null, null] : $this->otpFormVerificado;

    if (($this->otpFormVerificado[0] === null) && (is_string($check)) && (!empty($check))) {
      $auth = $this->otpFormData();

      if (
              (array_key_exists('tkg-k', $auth)) && (!empty($auth["tkg-k"])) &&
              (!empty(self::cookie($auth['tkg-k']))) &&
              (self::OTP())
      ) {
        $this->otpFormVerificado[0] = ($auth[self::cookie($auth['tkg-k'])] === $check) ? 0 : false;

        /* ELIMINANDO COOKIE JAH USADO */
        self::cookie($auth['tkg-k'], null);
      }
    }

    /* GERA UM NOVO TOKEN */
    if (strlen($this->otpFormVerificado[1]) !== 96) {
      /* A CHAVE COOKIE DIFERENTE PARA CADA TENTATIVA - ESTE COOKIE
       * CONTEM A CHAVE DE SESSION QUE CONTEM O TOKEN
       */
      $this->otpFormData()['tkg-k'] = self::ChaveSeguraAleatoria(32);

      /* A CHAVE DE SESSAO QUE CONTEM O TOKEN PARA FORMULARIO HTML */
      $_CHK = self::ChaveSeguraAleatoria(64);

      self::cookie($this->otpFormData()['tkg-k'], $_CHK, $this->getConfig('OTP-TIME'));

      /* O TOKEN PARA FORMULARIO HTML */
      $this->otpFormData()[$_CHK] = self::ChaveSeguraAleatoria(96);

      $this->otpFormVerificado = [$this->otpFormVerificado[0], $this->otpFormData()[$_CHK]];
    }

    return $this->otpFormVerificado;
  }

  /*
   * RETORNA O OTP CODIGO A SER COLOCADO NO FORM HMTL
   */

  public static function htmlOTPCode() {
    $func = array(self::getInstancia(), "_" . __FUNCTION__);
    $retorno = \call_user_func($func);
    return $retorno;
  }

  /*
   * RETORNA O OTP CODIGO A SER COLOCADO NO FORM HMTL
   */

  public function _htmlOTPCode() {
    return $this->otpFormVerificado[1];
  }

  public static function getHTMLFormInputsHidden() {
    echo "<input type='hidden' name='" . self::__REQUEST_AUTHFORM__ . "' value='auth'><input type='hidden' name='tkg' value='" . self::htmlOTPCode() . "'>";
  }

  /*
   * STATIC VERSAO: Abre o banco de Dados AT
   */

  public static function OTP($check = true) {
    $func = array(self::getInstancia(), "_" . __FUNCTION__);
    $retorno = \call_user_func($func, $check);
    return $retorno;
  }

  /*
   * @param   bool  $check  se TRUE faz a verificacao
   * @RETURN  BOOL          TRUE se OTP VALIDADOS
   */

  public function _OTP($check = true) {
    if ($this->getConfig("OTP-DISABLE") === true) {
      return true;
    }

    /* SE AINDA NAO FEZ A EXECUCAO ... */
    if (!$this->otpVerificado) {
      /* CLONING ARRAY */
      $otp = json_decode(json_encode($this->otpData()), true);

      /* PRESUME FALHA */
      $retorno = false;

      if ($check) {
        if (
                (array_key_exists('tkg-k', $otp)) && (!empty($otp["tkg-k"])) &&
                (!empty(self::cookie($otp['tkg-k']))) &&
                (array_key_exists('tks', $otp)) && (is_array($otp["tks"])) &&
                (array_key_exists(self::cookie($otp['tkg-k']), $otp["tks"]))
        ) {
          /* FAZENDO AS VERIFICAÇÕES */
          if (
                  (count($otp["tks"][self::cookie($otp['tkg-k'])]) === 3) &&
                  ((time() - $otp["tks"][self::cookie($otp['tkg-k'])][0]) <= ($this->getConfig('OTP-TIME'))) &&
                  ($otp["tks"][self::cookie($otp['tkg-k'])][1] === $_SERVER['REMOTE_ADDR']) &&
                  ($otp["tks"][self::cookie($otp['tkg-k'])][2] == @$_SERVER['HTTP_X_FORWARDED_FOR'])
          ) {
            $retorno = true;
          }

          /* ELIMINANDO O OTP USADO */
          $this->otpData()["tks"] = [];
          self::cookie($otp['tkg-k'], null);
        }
      }

      $this->otpData()['tkg-k'] = self::ChaveSeguraAleatoria(32);

      /* A CHAVE DE SESSAO QUE CONTEM O TOKEN OTP */
      $_CHK = self::ChaveSeguraAleatoria(64);

      self::cookie($this->otpData()['tkg-k'], $_CHK, $this->getConfig('OTP-TIME'));

      /* O TOKEN OTP */
      $this->otpData()["tks"][$_CHK] = [time(), $_SERVER['REMOTE_ADDR'], @$_SERVER['HTTP_X_FORWARDED_FOR']];

      $this->otpVerificado = $check ? $retorno : true;
    }

    return $this->otpVerificado;
  }

  /*
   * FAZ O HASH PADRAO DO EMAIL ($user)
   *
   * @param   string  $user   o email do usuario
   * @return  string          o HASH_HAMC do usuArio
   */

  public static function hashEmail($user) {
    $user = trim($user);
    return strtoupper(hash_hmac('sha512', $user, hash('whirlpool', $user)));
  }

  public static function Auth($user = null, $pass = null, $cad = false) {
    $func = array(self::getInstancia(), "_" . __FUNCTION__);
    $retorno = \call_user_func($func, $user, $pass, $cad);
    return $retorno;
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
   * @param     $pass   string  a senha o usuario (opicional)
   * @param     $cad    string  o codigo de autorizacao enviado ao email
   *                            se for cadastrar novo usuario ou refazer senha
   *
   * @return            mixed   retorna 0 se autenticao foi bem sucedida,
   *                            o nome do usuario se usuario autenticado,
   *                            false se usuario nao autenticado ou falha
   *                            TRUE se cadastro foi realizado com sucesso
   */

  public function _Auth($user = null, $pass = null, $cad = false) {
    /* OBTEM SE OTP EH VALIDO, E REGENERA UM NOVO OTP */
    $motp = self::OTP(true);

    if ((!empty($user)) && (!empty($pass))) {
      $user = trim($user);
      $pass = trim($pass);
      $userHASH = self::hashEmail($user);
      $userPASS = $this->bd->getPU($userHASH);

      if ($cad && (!empty($AThasMail = $this->bd->getAT($cad)))) {
        $__cadastro = 0;

        /* VERIFICA SE O EMAIL SOLICITADO CONFERE COM O EMAIL AUTORIZADO
         * A COMPARACAO E FEITA CONTA O HASH DO NOME DO USUARIO, JAH QUE
         * O QUE EH ARMAZENADO NA CHAVE EH O HASH
         *
         * EXCEPCIONALMENTE PARA O PRIMEIRO ACESSO - PRIMEIRO USUARIO "empty($userPASS)"
         * A SER REGISTRADO - PERMITE O CONTEUDO DO EMAIL NAO ESTEJA HASHEADO
         * OU SEJA, APENAS COMPARA A CHAVE DIRETAMENTE COM O EMAIL FORNECIDO
         */
        $firstUser = (!$this->bd->hasUsers() && (trim($this->bd->getAT($cad)) === $user));

        if (($AThasMail === $userHASH) || $firstUser) {
          require_once 'lib\Passmeter\src\Passmeter.php';

          if (\Passmeter::meter($pass, 'PT-BR')["approved"] && self::validateMail($user)) {
            $timeTarget = 0.7; // 700 milliseconds

            $cost = 12; /* CUSTO MINIMO */

            do {
              $cost++;
              $start = microtime(true);
              password_hash("test", PASSWORD_DEFAULT, ["cost" => $cost]);
              $end = microtime(true);
            } while (($end - $start) < $timeTarget);

            if ($this->bd->setPU($userHASH, password_hash($pass, PASSWORD_DEFAULT, [
                        'cost' => $cost
                    ]))) {
              $this->bd->removeAT($cad);

              if ($firstUser) {
                $this->bd->setACL($userHASH, "FastAuthAdd", true);
              }

              return true;
            }
          }
        }
      } else
      /*
       * FAZ A AUTENTICACAO
       */
      if (password_verify($pass, $userPASS)) {
        /* ATUALIZA O TEMPO DA ULTIMA CHECAGEM */
        $this->userData()["lastchk"] = time();
        $this->userData()['usr'] = $user;

        return 0;
      }
    } else if (
            ($motp) &&
            (is_array($this->userData())) &&
            array_key_exists('usr', $this->userData()) &&
            (strlen(trim($this->userData()['usr'])) >= 8) &&
            array_key_exists('lastchk', $this->userData()) &&
            ((time() - $this->userData()["lastchk"]) < $this->getConfig('SESS-TIME'))
    ) {
      /* ATUALIZA O TEMPO DA ULTIMA CHECAGEM */
      $this->userData()["lastchk"] = time();

      return $this->userData()['usr'];
    }

    return false;
  }

  public static function requestCadCodigo() {
    $func = array(self::getInstancia(), "_" . __FUNCTION__);
    $retorno = \call_user_func($func);
    return $retorno;
  }

  /*
   * VERIFICA SE FOI RECEBIDO UM CODIGO PARA CADASTRAMENTO RETORNANDO-DO
   * CASO CONTRARIO RETORNA FALSE
   */

  public function _requestCadCodigo() {
    if ((!$this->cadastrado) && (array_key_exists(self::__REQUEST_CADASTRAR__, $_REQUEST)) && (!empty($_REQUEST[self::__REQUEST_CADASTRAR__]))) {
      return $_REQUEST[self::__REQUEST_CADASTRAR__];
    }

    return false;
  }

  /*
   */

  public function __invoke() {
    return $this->Auth();
  }

  /*
   *
   */

  public function convidar($fingido = false) {
    $email = strtolower(trim($_REQUEST[self::__REQUEST_CONTIVE__]));

    /* VALIDANDO EMAIL */
    if (self::validateMail($email)) {
      if (!$fingido) {
        /* OBTEM O CODIGO DE AUTORIZACAO CASO O EMAIL JAH ESTEJA AUTORIZADO */
        $code = $this->bd->hasATUser(self::hashEmail($email));

        /* SE O EMAIL NAO ESTIVER AUTORIZADO, CRIA O CODIGO DE AUTORIZACAO */
        if (!$code || (!is_string($code)) || (strlen($code) < 32)) {
          $code = self::ChaveSeguraAleatoria(self::__CODIGO_CADASTRAMENTO__, true);
          $this->bd->setAT($code, self::hashEmail($email));
        }

        $from = self::Auth();
        $headers = \apache_request_headers();

        /* A URL PARA CADASTRAMENTO */
        $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $url = (strpos($url, '?') !== false) ? substr($url, 0, strpos($url, '?')) : $url;
        $url .= "?" . self::__REQUEST_CADASTRAR__ . "=" . $code;

        $data = date("d/M/Y, H:i:s");

        $headers_ = "From:$from\r\n" .
                "MIME-Version: 1.0\r\n" .
                "Reply-To:no-reply\r\n" .
                "Content-Type: text/html; charset=UTF-8\r\n";

        $oi = i18n::get("email-convite", "oi");
        $cumprimento = i18n::get("email-convite", "cumprimento");
        $desculpa = i18n::get("email-convite", "desculpaspam");

        $titulo = i18n::get("email-convite", "titulo", [
                    "host" => $headers['Host']
        ]);

        $texto = i18n::get("email-convite", "destino", [
                    "host" => $headers['Host']
        ]);

        $html = <<<EOF
<!DOCTYPE html>
<html>
  <head>
    <title>{$titulo}</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  </head>
  <body style="font-family:'sans-serif';">
    <div style="font-family:'sans-serif';">
      <p style="font-family:'sans-serif';">{$oi},</p>
      <p style="font-family:'sans-serif';">$texto</p>
      <p style="font-family:'sans-serif';"><big><b>{$url}</b></big></p>
      <p style="font-family:'sans-serif';">$desculpa</p>
      <p style="font-family:'sans-serif';">{$data}, $cumprimento</p>
    </div>
  </body>
</html>
EOF;

        header("Content-Type: text/html; charset=UTF-8");

        $texto = i18n::get("email-convite", "origem", [
                    "host" => $headers['Host'],
                    "email" => $from
        ]);

        if (mail($email, $titulo, str_replace("\n.", "\n..", $html), $headers_)) {
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
        <p style="font-family:'sans-serif';">{$oi},</p>
        <p style="font-family:'sans-serif';">$texto</p>
        <p style="font-family:'sans-serif';">$desculpa</p>
        <p style="font-family:'sans-serif';">{$data}, $cumprimento</p>
      </div>
    </body>
  </html>
EOF;

          mail($from, "Enviado: $titulo", str_replace("\n.", "\n..", $html), $headers_);
        } else {
          usleep(rand(200, 800));
        }

        self::reload('?', 5, ["email-convite", "sucesso"]);
      } else {
        self::reload('?', 5, ["email-convite", "falha"]);
      }
    }
  }

  /*
   * PROCEDIMENTOS A SEREM EXECUTADOS QUANDO O USUARIO JAH ESTA LOGADO
   */

  public function procedimentosLogado() {
    /* SE CONVITE DE USUARIO */
    if (
            (array_key_exists(self::__REQUEST_CONTIVE__, $_REQUEST)) &&
            (!empty($_REQUEST[self::__REQUEST_CONTIVE__]))
    ) {
      if (!$this->bd->getACL(self::hashEmail(self::Auth()), "FastAuthAdd")) {
        self::reload('?', 0, ["geral", "reloadacess"]);
        die();
      }

      $this->convidar();
    }

    return false;
  }

  /*
   * Recarrega/Redireciona a pagina
   */

  public static function reload($url = false, $tempo = 0, $msgCode = '') {
    $tempo = ($tempo > 0 ? $tempo : 1);
    $msgCode = i18n::get(is_array($msgCode) ? $msgCode[0] : "geral", (is_array($msgCode) ? $msgCode[1] : "reload"));

    if (!$url) {
      /* AUTENTICACAO REALIZADA COM SUCESSO */
      header("Refresh:" . $tempo);
    } else {
      die("<p>$msgCode</p><script>window.setTimeout('location = \"$url\";', " . ($tempo * 1000) . ");</script>");
    }

    die($msgCode);
  }

  /*
   * RETORNA TRUE SE FOR PARA CARREGAR A PAGINA DE LOGIN
   */

  public function procedimentosNaoLogado() {
    /* FORMULARIO DE AUTENTICACAO/CADASTRAMENTO RECEBIDO */
    if ((array_key_exists(self::__REQUEST_AUTHFORM__, $_POST)) && (!empty($_POST[self::__REQUEST_AUTHFORM__]))) {
      /* SE OTP FORM INVALIDO */
      if (!((array_key_exists('tkg', $_POST)) && (!empty($_POST["tkg"])) && (self::OTPForm($_POST["tkg"])))) {
        if ((array_key_exists('tkg', $_POST)) && (!empty($_POST["tkg"]))) {
          self::addMsg(-1, i18n::get("otp", "formvencido"));
        }

        return true;
      }

      /* SE OS DADOS RECEBIDOS ESTAO PREENCHIDOS */
      if (!( (array_key_exists('nm', $_POST)) && (!empty($_POST["nm"])) && (array_key_exists('pwd', $_POST)) && (!empty($_POST["pwd"])) && (array_key_exists('act', $_POST)) && (!empty($_POST["act"])) )) {
        self::addMsg(0, i18n::get("form", "naopreenchido"));
        return true;
      }

      switch (strtolower($_POST["act"])) {
        case "inc":
          if ((!array_key_exists('inc', $_POST)) || (empty($_POST["inc"]))) {
            self::addMsg(0, i18n::get("cadastro", "cdinvalido"));
            return true;
          }

          if (self::Auth($_POST['nm'], $_POST['pwd'], trim($_POST['inc']))) {
            /* CADASTRO REALIZADO COM SUCESSO */
            self::addMsg(1, i18n::get("cadastro", "ok"));
            $this->cadastrado = true;
          } else {
            self::addMsg(0, i18n::get("cadastro", "emailsenha"));
          }

          break;

        default:
          /* TENTA AUTENTICAR */
          if (self::Auth($_POST['nm'], $_POST['pwd'], false) === 0) {
            /* AUTENTICACAO REALIZADA COM SUCESSO */
            self::reload();
          } else {
            self::addMsg(0, i18n::get("form", "emailsenha"));
          }

          break;
      }
    }

    /* SE PEDIDO RESET DE USUARIO (A MESMA QUE CONVITE)
     * ENTAO VERIFICA PRIMEIRO SE O USUARIO EXISTE E, APENAS SE O USUARIO EXISTE
     * DAH PROSEGUIMENTO COM ENVIO DE CODIGO AO EMAIL PARA RESET DA SENHA
     */
    if (
            (array_key_exists(self::__REQUEST_CONTIVE__, $_REQUEST)) &&
            (!empty($_REQUEST[self::__REQUEST_CONTIVE__]))
    ) {
      $this->convidar(($this->bd->getPU(self::hashEmail($_REQUEST[self::__REQUEST_CONTIVE__]))));
    }


    return true;
  }

  public static function carregarLogin() {
    $func = array(self::getInstancia(), "_" . __FUNCTION__);
    $retorno = \call_user_func($func);
    return $retorno;
  }

  /*
   * Execita os procedimento necessários para cada situação
   * Para não logado e para logado, conforme o tipo de solicitação
   */

  public function _carregarLogin() {
    /* SE OTP INVALIDO */
    if (!self::OTP(true)) {
      if ((array_key_exists(self::__REQUEST_AUTHFORM__, $_POST)) && (!empty($_POST[self::__REQUEST_AUTHFORM__]))) {
        self::addMsg(-1, i18n::get("otp", "vencido"));
      }

      return true;
    }

    /* VERIFICA SE ESTA AUTENTICADO */
    if (strlen(self::Auth()) >= 8) {
      return $this->procedimentosLogado();
    } else {
      $r = $this->procedimentosNaoLogado();

      /* GARANTE QUE GERARAH UM NOVO OTP FORM QUANDO ESTE NAO FOR VERIFICADO */
      self::OTPForm();

      return $r;
    }
  }

}
