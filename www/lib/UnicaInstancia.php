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

/*
 *
 */

interface IUnicaInstancia {

  public static function getInstancia();
}

/*
 *
 */

trait TUnicaInstancia {
  /*
   * A INSTÂNCIA REAL DO OBJETO
   * COMO ESTA É UMA VARIÁVEL STATICA, SEU VALOR É ATRELADO À CLASSE
   * E NÃO AO OBJETO, ASSIM, QUANDO ATRIBUIMOS UM VALOR À ELA, ATRAVÉS DE CÓDIGO
   * SEU VALOR PASSA A SER ÚNICO INDEPENDENDE DO OBJETO OU DO LOCAL
   * OU SEJA, SEU VALOR É GUARDADO NA CLASSE E NÃO NO OBJETO
   * BASTA USAR: SELF:: PARA ACESSAR O OBJETO CRIADO DINAMICAMENTE ANTERIORMENTE
   */

  PROTECTED static $InstanciaReal;

  /**
   *
   * @var ReflectionClass
   */
  protected static $reflection;

  /*
   *
   */

  public function __construct() {

  }

  /*
   *  Retorna a Instância de Session
   *  A sessão é automaticamente inicializada se for o caso
   *
   *  @return    Objeto
   */

  public static function &getInstancia() {
    # SE A INSTANCIA NÃO EXISTIR, CRIAMOS
    if (!static::__FuiInstanciada()) {
      # CRIANDO UMA SESSÃO PROTEGIDA
      $novo = new static();

      if (!static::__FuiInstanciada())
        static::$InstanciaReal = &$novo;
    }

    # RETORNANDO A SESSÃO
    return static::$InstanciaReal;
  }

  /*
   * VERIFICA SE ESTA CLASSE JAH FOI INCIALIZADA/INSTANCIADA
   */

  protected static function __FuiInstanciada() {
    return ((isset(static::$InstanciaReal)) && (is_object(static::$InstanciaReal)));
  }

}
