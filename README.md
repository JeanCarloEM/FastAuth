FastAuth 0.1
========================================

FastAuth é um enxuto, simples e fácil de usar (easy-to-use) autenticador em PHP. Seu principal objetivo é eliminar o máximo a configuração, sendo simples de colocar em qualquer aplicação PHP.

## Recursos
* Conta com um formulário de login HTML, simples e limpo - facilmente personalizável;
* OTP token para validação de sessão (login);
* OTP token para validação de formulário HTML;
* Exigência de força de senha para cadastramentos com [Passmeter](https://github.com/JeanCarloEM/Passmeter);
* Usa **password_hash** PHP para armazenamento e verificação de senhas;
* Faz uso de ocultação do nome de usuário no banco de dados (JSON) através de **hash_hmac** o que aumenta um pouco a dificuldade do atacante em caso de comprometimento da base.
* Faz verificação **automática** do **status** da autenticação e, exige novo login se necessário;
* Não exige nenhum tipo de tratamento do login, de verificação de login e da situação do login. Nada!
* Verifique o usuários autenticado (e-mail) através da função **Auth()**;
* Base de dados em JSON - facilita o uso e manutenção (arquivos .pu e .at).

# Como usar

É simples:

1. Clone o projeto na raiz de seu site (public_html);
2. Em seu arquivo index.php, inclua o **login.php** no início, antes de qualquer coisa;
```php
require_once "[path-to-FastAuth]/login.php";
```
3. No arquivo login.php edite a linha ````<script type="text/javascript" src="lib\FastAuth\www\lib\Passmeter\src\Passmeter.js"></script>```` com a URL correta para o seu projeto;

Pronto! Se quiser saber qual usuário está autenticado, use a função **Auth** que retorna o nome do usuário (e-mail).

Para o **primeiro uso** faça os passos da sessão seguinte.

## Primeiro uso e BANCO de Dados

O banco de dados é composto de dois arquivos, um **.pu** para nomes de usuários e senhas e, um **.at** para autorizações de registro. Ambos os arquivos são JSON.

_NOTA: No primeiro uso não haverá usuário registrado, precisará fazê-lo manualmente, CRIANDO e editando o arquivo **.at**, OU o arquivo **.pu**._

 O conteúdo do **.pu** é assim:

```json
{
"[hash_hmac-nome-do-usuario]":"[password_hash-da-senha]"
}
```

O arquivo **.at** é composto assim:

```json
{
"[codigo-de-autorizacao-enviado-no-email]":"[email-autorizado-a-se-cadastrar]"
}
```

### Criar primeiro usuário
A princípio parece ser mais simples criar o arquivo **.at**, inventando um código qualquer para *[codigo-de-autorizacao-enviado-no-email]*, como “abc”.

O cadastramento do usuário é realizado acessando a URL  ````?fastauth_inc=[codigo-de-autorizacao-enviado-no-email]````. 

** _Note que este procedimento também permite refazer a senha! Portanto, sempre que necessário, basta reenviar um código. Mas também representa uma vulnerabilidade, já que não é implementando controle de privilégios, o que permite a qualquer usuário registrado solicitar o envio do código._

## Adição de usuários

Para adicionar um usuário acesse sua URL com ````?fastauth_add_inc=[e-mail a ser adicionado]````. Isso irá criar uma entrada no arquivo **.at**, além de enviar um e-mail com o código para cadastramento.

# Nota de Segurança
Considerando que o objeto do FastAuth é ser simples de usar, ele não deve ser usado sem uma prévia consideração quanto a segurança, já que existem questões de segurança NÃO implementadas, e as que foram implementadas NÃO estão livres de falhas. Melhoramentos serão liberados eventualmente, contribua também!

Note que o banco de dado está acessível via URL, embora não seja uma boa prática "terceirizar" a segurança de um software, neste caso, a proteção/bloqueio dos mesmos deverá ser feita no servidor.

** _Uso de senha para autenticação único fator, normalmente NÃO é uma boa escolha!_

# Sobre

Este projeto está licensiado sob [MPL 2.0+](https://www.mozilla.org/en-US/MPL/2.0/).