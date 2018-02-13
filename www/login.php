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

require_once __DIR__ . DIRECTORY_SEPARATOR . 'FastAuth.php';

if (fa\FastAuth::carregarLogin()) {
  $codenovo = fa\FastAuth::requestCadCodigo();
  ?><!DOCTYPE html>
  <html>
    <head>
      <title><?php echo $codenovo ? fa\i18n::get("pagina", "titulocad") : fa\i18n::get("pagina", "tituloauth"); ?></title>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link href="https://fonts.googleapis.com/css?family=Noto+Sans" rel="stylesheet">
      <link href="lib/FastAuth/www/assets/css/login.css" rel="stylesheet">
      <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

      <?php if ($codenovo) { ?>
        <script type="text/javascript" src="<?php echo fa\FastAuth::getCfg("passmeter") ?? "lib\FastAuth\www\lib\Passmeter\src\Passmeter.js"; ?>"></script>

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

      <script type="text/javascript">
        (function ($) {
          $(document).ready(function () {
            $("form").on("submit", function (e) {
              $("body > div.loading").show();
              $("body > div.painel").hide();
            });
          });
        })(jQuery);
      </script>

    </head>
    <body>
      <div class="loading" style="display: none;">
        <div class="lds-ripple"><div></div><div></div></div>
      </div>

      <div class="painel">
        <form class="lfm" method="post"<?php echo ($codenovo ? 'onsubmit="return dateCheck(this);"' : ''); ?>>
          <?php
          foreach (fa\FastAuth::getMsgs() as $key => $msg) {
            echo "\n\t<p class='notify" . ($msg[0] === 1 ? " sucess" : ($msg[0] === -1 ? " alert" : "")) . "'>{$msg[1]}</p>";
          }

          /* EXIBIR INPUTS HIDDENS PADRAO */
          fa\FastAuth::getHTMLFormInputsHidden();

          if ($codenovo) {
            ?>
            <input type='hidden' name='act' value='inc'>
            <input type='hidden' name='inc' value='<?php echo $codenovo; ?>'>
          <?php } else { ?>
            <input type='hidden' name='act' value='auth'>
          <?php } ?>
          <input type="email" placeholder="<?php echo fa\i18n::get("form", "user"); ?>" name='nm' required />
          <input type="password" placeholder="<?php echo fa\i18n::get("form", "pass"); ?>" name='pwd' required />
          <?php if ($codenovo) { ?>
            <p><?php echo fa\i18n::get("senha", "titulo"); ?></p>

            <ul>
              <li><?php echo fa\i18n::get("senha", "8d"); ?></li>
              <li><?php echo fa\i18n::get("senha", "num"); ?></li>
              <li><?php echo fa\i18n::get("senha", "maiuscula"); ?></li>
              <li><?php echo fa\i18n::get("senha", "minuscula"); ?></li>
              <li><?php echo fa\i18n::get("senha", "simbolo"); ?></li>
              <li><?php echo fa\i18n::get("senha", "ponto"); ?></li>
            </ul>

            <button><?php echo fa\i18n::get("pagina", "ir"); ?></button>
          <?php } else { ?>
            <button><?php echo fa\i18n::get("pagina", "logar"); ?></button>
          <?php } ?>
        </form>

        <p class="about">Powered with <a href="https://github.com/JeanCarloEM/Passmeter"><b>Passmeter</b></a><br /><b><a href='https://github.com/JeanCarloEM/FastAuth'>FastAuth</a> | &copf; <a href='//jeancarloem.com'>Jean Carlo EM</a> | <a href='https://www.mozilla.org/en-US/MPL/2.0/'>MPL 2.0+</a></b></p>
      </div>
    </body>
  </html><?php
  die();
}