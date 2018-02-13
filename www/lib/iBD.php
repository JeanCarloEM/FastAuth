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

interface IBD {
  /*
   * RETORNA SE EXISTE USUARIO CADASTROS
   *
   * @return  BOOL            TRUE se houver usuarios cadastrados
   */

  public function hasUsers();

  /*
   * RETORNA A QUANTIDADE DE USUARIOS CADASTRADOS
   *
   * @return  int   o numero de usuario cadastrados
   */

  public function countPU();

  /*
   * OBTEM A SENHA DO USUARIO
   *
   * @param   string  $user   nome do usuário
   * @return  string          a senha do usuario
   */

  public function getPU($user);

  /*
   * SETA (CRIA) USUARIO E SENHA
   *
   * @param   string  $user   nome do usuário
   * @param   string  $pass   a senha (hash) do usuarío
   * @return  bool            TRUE se sucesso
   */

  public function setPU($user, $pass);

  /*
   * RETORNA SE EXISTE AUTORIZAZOES CADASTRADAS
   *
   * @return  BOOL            TRUE se houver autorizacoes cadastradas
   */

  public function hasATs();

  /*
   * RETORNA TRUE SE O EMAIL FORNECIDO JAH ESTA AUTORIZADO
   *
   * @param   string  $user   o hash do email autorizado
   * @return  mixed           FALSE SE NAO EXISTE, string com o código se
   *                          existe
   */

  public function hasATUser($user);

  /*
   * RETORNA A QUANTIDADE DE AUTORIZACOES CADASTRADOS
   *
   * @return  int   o numero de autorizacoes cadastradas
   */

  public function countAT();

  /*
   * OBTEM AUTORIZACAO PARA CADASTRAMENTO
   *
   * @param   string  $key    o codigo de autorizacao
   * @return  string          o hash do email autorizado
   */

  public function getAT($key);

  /*
   * SETA (CRIA) AUTORIZACAO PARA CADASTRAMENTO
   *
   * @param   string  $key    o codigo de autorizacao
   * @param   string  $user   o hash do email autorizado
   * @return  bool            TRUE se sucesso
   */

  public function setAT($key, $user);

  /*
   * REMOVE UMA AUTORIZACAO PARA CADASTRAMENTO
   *
   * @param   string  $key    o codigo de autorizacao
   * @return  bool            TRUE se sucesso
   */

  public function removeAT($key);

  /*
   * OBTEM UM PRIVILEGIO
   *
   * @param   string  $user   nome do usuário
   * @param   string  $code   um codigo de acesso
   * @return  mixed           indicativo de nivel ou bool para indicar ativo
   */

  public function getACL($user, $code);

  /*
   * SETA (CRIA) UM PRIVILEGIO
   *
   * @param   string  $user   nome do usuário
   * @param   string  $code   um codigo de acesso
   * @param   mixed   $vlr    indicativo de nivel ou bool para indicar ativo
   * @return  bool            TRUE se sucesso
   */

  public function setACL($user, $code, $vlr);

  /*
   * REMOVE UM PRIVILEGIO
   *
   * @param   string  $user   nome do usuário
   * @param   string  $code   um codigo de acesso
   * @return  bool            TRUE se sucesso
   */

  public function removeACL($user, $code);
}
