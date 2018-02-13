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

use jeancarloem\FastAut as fa;

require_once dirname(__DIR__) . "/iBD.php";

class jsonBD implements fa\iBD {

  private $at, $pu, $acl;

  /*
   * SALVA ARQUIVO JSON FORNECIDO EM $fpath
   *
   * @param   string  $fpath  filepath
   * @param   array   $fpath  o arrau (json)  a ser gravado
   * @return  mixed           TRUE se sucesso
   */

  protected function saveJson($fpath, $JSON) {
    if (file_put_contents($fpath, json_encode($JSON)) >= 2) {
      return (json_last_error() === JSON_ERROR_NONE);
    }
  }

  private function saveAT() {
    return $this->saveJson(".at", $this->at);
  }

  private function savePU() {
    return $this->saveJson(".pu", $this->pu);
  }

  private function saveACL() {
    return $this->saveJson(".acl", $this->acl);
  }

  /*
   * LE UM ARQUIVO JSON FORNECIDO EM $fpath
   *
   * @param   string  $fpath  filepath
   * @return  mixed           false se falha, array se sucesso
   */

  protected function loadJson($fpath) {
    if (!file_exists($fpath)) {
      return [];
    } else {
      $PREV = file_get_contents($fpath);

      if (empty($PREV)) {
        return [];
      }

      if (!empty($PREV)) {
        $retorno = json_decode($PREV, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
          return false;
        }

        return $retorno;
      }
    }

    return false;
  }

  public function __construct($path) {
    if (($this->at = $this->loadJson($path . '.at')) === false) {
      throw new \Exception("Falha ao abrir arquivo .at");
    }

    if (($this->pu = $this->loadJson($path . '.pu')) === false) {
      throw new \Exception("Falha ao abrir arquivo .pu");
    }

    if (($this->acl = $this->loadJson($path . '.acl')) === false) {
      throw new \Exception("Falha ao abrir arquivo .acl");
    }
  }

  /*
   * RETORNA A QUANTIDADE DE USUARIOS CADASTRADOS
   *
   * @return  int   o numero de usuario cadastrados
   */

  public function countPU() {
    return count($this->pu);
  }

  /*
   * RETORNA SE EXISTE USUARIO CADASTROS
   *
   * @return  BOOL            TRUE se houver usuarios cadastrados
   */

  public function hasUsers() {
    return ($this->countPU() > 0);
  }

  /*
   * OBTEM A SENHA DO USUARIO
   *
   * @param   string  $user   nome do usuário
   * @return  string          a senha do usuario
   */

  public function getPU($user) {
    if (array_key_exists($user, $this->pu)) {
      return $this->pu[$user];
    }

    return null;
  }

  /*
   * SETA (CRIA) USUARIO E SENHA
   *
   * @param   string  $user   nome do usuário
   * @param   string  $pass   a senha (hash) do usuarío
   * @return  bool            TRUE se sucesso
   */

  public function setPU($user, $pass) {
    $this->pu[$user] = $pass;
    return $this->savePU();
  }

  /*
   * RETORNA A QUANTIDADE DE AUTORIZACOES CADASTRADOS
   *
   * @return  int   o numero de autorizacoes cadastradas
   */

  public function countAT() {
    return count($this->at);
  }

  /*
   * RETORNA SE EXISTE AUTORIZAZOES CADASTRADAS
   *
   * @return  BOOL            TRUE se houver autorizacoes cadastradas
   */

  public function hasATs() {
    return ($this->countAT() > 0);
  }

  /*
   * RETORNA TRUE SE O EMAIL FORNECIDO JAH ESTA AUTORIZADO
   *
   * @param   string  $user   o hash do email autorizado
   * @return  mixed           FALSE SE NAO EXISTE, string com o código se
   *                          existe
   */

  public function hasATUser($user) {
    return array_search($user, $this->at);
  }

  /*
   * OBTEM AUTORIZACAO PARA CADASTRAMENTO
   *
   * @param   string  $key    o codigo de autorizacao
   * @return  string          o hash do email autorizado, false/null
   *                          caso nao exista
   */

  public function getAT($key) {
    if (array_key_exists($key, $this->at)) {
      return $this->at[$key];
    }

    return null;
  }

  /*
   * SETA (CRIA) AUTORIZACAO PARA CADASTRAMENTO
   *
   * @param   string  $key    o codigo de autorizacao
   * @param   string  $user   o hash do email autorizado
   * @return  bool            TRUE se sucesso
   */

  public function setAT($key, $user) {
    $this->at[$key] = $user;
    return $this->saveAT();
  }

  /*
   * REMOVE UMA AUTORIZACAO PARA CADASTRAMENTO
   *
   * @param   string  $key    o codigo de autorizacao
   * @return  bool            TRUE se sucesso
   */

  public function removeAT($key) {
    unset($this->at[$key]);
    return $this->saveAT();
  }

  /*
   * OBTEM UM PRIVILEGIO
   *
   * @param   string  $user   nome do usuário
   * @param   string  $code   um codigo de acesso
   * @return  mixed           indicativo de nivel ou bool para indicar ativo
   */

  public function getACL($user, $code) {
    if (array_key_exists($user, $this->acl) && is_array($this->acl[$user])) {
      if (array_key_exists($code, $this->acl[$user])) {
        return $this->acl[$user][$code];
      }
    }

    return null;
  }

  /*
   * SETA (CRIA) UM PRIVILEGIO
   *
   * @param   string  $user   nome do usuário
   * @param   string  $code   um codigo de acesso
   * @param   mixed   $vlr    indicativo de nivel ou bool para indicar ativo
   * @return  bool            TRUE se sucesso
   */

  public function setACL($user, $code, $vlr) {
    $this->acl[$user] = (array_key_exists($user, $this->acl) && is_array($this->acl[$user])) ? $this->acl[$user] : [];
    $this->acl[$user][$code] = $vlr;
    return $this->saveACL();
  }

  /*
   * REMOVE UM PRIVILEGIO
   *
   * @param   string  $user   nome do usuário
   * @param   string  $code   um codigo de acesso
   * @return  bool            TRUE se sucesso
   */

  public function removeACL($user, $code) {
    if (array_key_exists($user, $this->acl) && is_array($this->acl[$user])) {
      if (array_key_exists($code, $this->acl[$user])) {
        unset($this->acl[$user][$code]);
        return $this->saveACL();
      }
    }

    return true;
  }

}
