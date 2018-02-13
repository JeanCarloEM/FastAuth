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

trait Serverdata {
  /* PONTEIRO PARA UM INDICE DE $_SESSAO */

  private $___sess;

  private function initServerdata() {
    if (session_status() == PHP_SESSION_NONE) {
      session_start();
    }

    if (!is_array($_SESSION)) {
      $_SESSION = [];
    }

    $this->___sess = &$_SESSION[__NAMESPACE__];
    $this->___sess = is_array($this->___sess) ? $this->___sess : [];
    $this->___sess[__CLASS__] = array_key_exists(__CLASS__, $this->___sess) ? $this->___sess[__CLASS__] : [];
  }

  /*
   * RETORNA UM PONTEIRO PARA O INDICE DE $_SESSION, QUE ARMAZENARA AS
   * INFORMAÇÕES DA CLASSE QUE IMPLEMENTA ISTO
   */

  protected function &sess() {
    if (!is_array($this->___sess)) {
      throw new \Exception("[" . __CLASS__ . "]Sessao precisa ser um vetor na linha " . __LINE__ . " de " . __FILE__ . ".");
    }

    return $this->___sess[__CLASS__];
  }

  /*
   * @param string  $nome   a chave ID de cookie
   * @param mixed   $valor  padrão (FALSE), se FALSE, retorna o valor do cookie
   *                        se NULL elimina o COOKIE, qualquer outro valor,
   *                        grava o cookie
   * @param int     $TIME   contem o tempo de duracao do cookie,
   *                        padrão 10 minutos (600 segundos)
   *
   * @ return mixed         o valor do cookie
   */

  public static function cookie($nome, $valor = false, $TIME = 600) {
    if ($valor || $valor === null) {
      if (setcookie($nome, $valor === null ? '' : $valor, $valor === null ? 1 : ($TIME + ($TIME > time() ? 0 : time())), null, null, ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443), true)) {
        if ($valor === null) {
          unset($_COOKIE[$nome]);
        } else {
          $_COOKIE[$nome] = $valor;
        }

        return $valor;
      }
    } else {
      if (array_key_exists($nome, @$_COOKIE)) {
        return @$_COOKIE[$nome];
      } else {
        return null;
      }
    }
  }

}
