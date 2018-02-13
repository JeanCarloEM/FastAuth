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

class i18n implements fa\IUnicaInstancia {

  use fa\TUnicaInstancia;
  use fa\ServerData;

  private $json, $path, $fjson;

  public function __construct($dir = null) {
    $this->initServerdata();

    $this->path = realpath($dir ?? dirname(__FILE__));
    $this->_detectLanguage();

    if (file_exists($this->fjson)) {
      $this->json = self::readJson($this->fjson, true);
    } else {
      throw new \Exception("O caminho para o arquivo de linguagem não existe: '$path'.");
    }
  }

  public static function readJson($path, $array = false) {
    if ((!empty($path)) && (file_exists($path))) {
      $retorno = json_decode(file_get_contents($path), $array);
      return (json_last_error() === JSON_ERROR_NONE) ? $retorno : null;
    }

    return false;
  }

  /*
   * PERMITE SET/GET DE COOKIE
   */

  public static function detectLanguage() {
    $func = array(static::getInstancia(), "_" . __FUNCTION__);
    $retorno = \call_user_func($func);
    return $retorno;
  }

  /*
   */

  public function _detectLanguage() {
    $langs = [
        (
        /* VERIFICA SE HA MUDANCA DE IDIOMA */
        (array_key_exists("setlang", $_REQUEST) && !empty($_REQUEST["setlang"])) ? $_REQUEST["setlang"]

        /* OBTEM O IDIOMA ANTERIORMENTE SETADO */ : (array_key_exists("lang", $this->sess()) && !empty($this->sess()["lang"])) ? $this->sess()['lang']

        /* OBTEM O IDIOMA DO NAVEGADOR */ : substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2)
        ),
        /* IDIOMA DO NAVEGADOR */
        substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2),
        /* PT */
        "pt"
    ];

    do {
      $lang = array_splice($langs, 0, 1)[0];
      $path = $this->path . DIRECTORY_SEPARATOR . $lang . ".json";
    } while (!file_exists($path) && (count($langs) > 0));

    $this->sess()['lang'] = $lang;

    /* SET LANGUAGE */
    $this->fjson = $this->path . DIRECTORY_SEPARATOR . $this->sess()['lang'] . ".json";

    return $lang;
  }

  /*
   * OBTEM O KEY
   */

  public static function get($grupo, $key, $values = null) {
    $func = array(static::getInstancia(), "_" . __FUNCTION__);
    $retorno = \call_user_func($func, $grupo, $key, $values);
    return $retorno;
  }

  /*
   * OBTEM O KEY
   */

  public function _get($grupo, $key, $values = null) {
    $retorno = ((array_key_exists($grupo, $this->json)) && (array_key_exists($key, $this->json[$grupo]))) ? $this->json[$grupo][$key] : null;

    if ((is_array($values)) && (!empty($values))) {
      $retorno = preg_replace_callback(
              '|#{([^}]+)}|i', function ($matches) use ($values) {
        return (array_key_exists($matches[1], $values)) ? $values[$matches[1]] : ( (array_key_exists(strtolower($matches[1]), $values)) ? $values[strtolower($matches[1])] : ( (array_key_exists(strtoupper($matches[1]), $values)) ? $values[strtoupper($matches[1])] : $matches[0]
                )
                );
      }, $retorno
      );
    }

    return $retorno;
  }

}
