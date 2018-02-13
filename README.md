FastAuth 0.2
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
* Saiba qual o usuário está autenticado (e-mail) através da função **jeancarloem\FastAut\Auth()**;
* Controle de Acesso (ACL) via códigos.
* Totalmente compatível com [Manipuladores de Sessão Personalizados](https://secure.php.net/manual/pt_BR/session.customhandler.php). Ver sessão específica.
* Internacionalização de idioma (i18n) por meio de arquivos json. Na atualidade, apenas PT disponível;
* Base de dados em JSON - facilita o uso e manutenção (arquivos .pu, .at e .acl);
* Permite utilizar outros Bancos de Dados, tais como MySQL, Maria DB e Postgree, mediante plugin _(mais informação no título "Banco de Dados")_.

# Como usar

É fácil:

1. Clone o projeto na raiz de seu site (como public_html);
2. Em seu arquivo index.php, inclua o **login.php** no início, antes de qualquer coisa;
```php
require_once "[path-to-FastAuth]/login.php";
```
3. Defina `jeancarloem\FastAut\FastAuth::$config["passmeter"]` com a url para Passmeter, caso necessário, por padrão irá apontar "lib\FastAuth\www\lib\Passmeter\src\Passmeter.js";

Pronto! Se quiser saber qual usuário está autenticado, use a função **Auth** que retorna o nome do usuário (e-mail).

Para o **primeiro uso** e configuração inicial vá para o título Configuração..

# Banco de Dados
O banco de dados padrão (em json) é composto de três arquivos, um **.pu** para nomes de usuários e senhas, um **.at** para autorizações de registro e um **.acl** para controle de acesso; todos arquivos são JSON. Eles devem ser encontrados no mesmo diretório do seu arquivo "index.php".

_NOTA: No primeiro uso NÃO haverá usuário registrado, precisará fazê-lo manualmente, CRIANDO e editando o arquivo **.at**, OU o arquivo **.pu**._

 A composição do arquivo **.pu**:

```json
{
  "[hash_hmac-nome-do-usuario]":"[password_hash-da-senha]"
}
```

A composição do arquivo **.at**:

```json
{
  "[codigo-de-autorizacao-enviado-no-email]":"[hash_hmac-nome-do-usuario]"
}
```

A composição do arquivo **.acl**:

```json
{
  "[hash_hmac-nome-do-usuario]":{
    "[codigo-do-acesso]":"[conteudo-mixed]"
  }
}
```

_** NOTA: o Código de acesso "FastAuthAdd" é reservado para FastAuth, seu conteúdo é booleano e, concede acesso para adição (convite) de usuários._

### Armazenamento do Nome de Usuário (e-mail)

O hash do nome do usuário (e-mail) é obtido da seguinte forma:

```php
strtoupper(hash_hmac('sha512', $username, hash('whirlpool', $username)))
```

### Armazenamento da senha

A senha NÃO é armazenada, mas apenas o hash, obtido pelo comando password_hash, com custo mínimo 12, usando PASSWORD_DEFAULT.

## Acesso e Personalização do Banco de Dados

Para acesso ao Banco de Dados, FastAuth utiliza uma interface denominada **iBD**. Por isso é muito simples criar um novo plugin de Banco de Dados que habilite Mysql, SQLite, MariaDB, Postgree, dentre outros.

O arquivo da interface está localizado em `lib/iBD.php`, confirá lá! Para criar um novo plugin, declare uma Classe que Implemente a Interface iBD, salvando esta classe em um arquivo arquivo PHP com o mesmo nome da classe, e coloque-o na pasta `lib/BDs/[nome-da-minha-classe].php`. Pronto!

Agora, para ativar o seu plugin, você precisa setar `jeancarloem\FastAut\FastAut::$config["BD"]` para o nome da sua classe. Lembre que PHP é caso sensível:

```php
jeancarloem\FastAut\FastAut::$config["BD"] = "MyClassPLuginBD";
```

_** NOTA: A implementação personalizada NÃO deve fazer qualquer tratamento no nome de usuário e senha, pois os tratamentos necessários já são realizados no FastAuth._

**IMPORTANTE:** É possível ter acesso direto (sem barreiras) ao objeto de banco de dados atraves do método `jeancarloem\FastAut\FastAut::BD()`. Use com moderação [rs].

# Configuração

Se você estiver usando FastAuth com o Banco de Dados padrão em json, abaixo segue orientações de configuração para primeiro acesso.

### Criar Primeiro Usuário
A forma mais simples de criar o primeiro usuário é criando o arquivo **.at**, inventando um código qualquer para `[codigo-de-autorizacao-enviado-no-email]`, como “abc” ou "123".

O cadastramento do usuário é realizado acessando a URL  ````?fastauth_cadastrar=[codigo-de-autorizacao-enviado-no-email]````.

** _Note que este procedimento também permite refazer a senha! Portanto, sempre que necessário, basta reenviar um código. Este procedimento somente é permitido para usuários não autenticados._

## Adição de usuários e Reset de Senhas

Para adicionar um usuário, após o cadastramento do primeiro e, estando logado, acesse sua URL com ````?fastauth_resetuser=[e-mail-a-ser-adicionado]````. Isso criará uma entrada no arquivo **.at**, além de enviar um e-mail com o código de cadastramento a ser usado na URL `?fastauth_cadastrar` mencionada do título anterior.

Este comando somente é permitido para usuários cadastrados que possuam código de acesso **FastAuthAdd**, ou para usuários não autenticados - como forma de recuperar de senha - desde que neste caso, o e-mail exista no cadastro **.pu**.

_** NOTA: Caso o e-mail não exista o programa se comportará de forma semelhante, mitigando identificação de e-mail cadastrados por força bruta._

## Configuração personalizada
Toda configuração passível de personalização é configurada no array `jeancarloem\FastAut\FastAuth::$config`.
As seguintes chaves (índices) estão habilitados:

1. **passmeter** define a URL do passmeter, por padrão `lib\FastAuth\www\lib\Passmeter\src\Passmeter.js`.
2. **BD** define o nome da classe (plugin) de controle de banco de dados personalizado. Ao setá-la, criando uma implementação própria, é possível fazer FastAuth funcionar com Mysql, SQlite, XML, dentre outros.
3. **BD-PARAM** um parâmetro a ser fornecido ao construtor da classe de Banco de Dados.
4. **OTP-TIME** define o tempo de validade (em segundos) do Token OTP, por padrão 10 minutos (600 segundos).
5. **OTP-DISABLE** booleano que define se o recurso OTP deve ser desabilitado. Por padrão **false**. _NOTA: por questões de segurança, a desabilitação deste NÃO impacta na verificação de token de formulário HTML_.
6. **SESS-TIME** define o tempo de validade (em segundos) da Sessão Autenticada, por padrão 10 minutos (600 segundos).

## Manipuladores de Sessão Personalizados
Os [Manipuladores de Sessão Personalizados](https://secure.php.net/manual/pt_BR/session.customhandler.php) são suportado, porem tenha em mente que eles devem ser implementados e inicializados antes do primeiro uso de FastAuth no programa, já que na construção do objeto, FastAuth iniciará a sessão invocando o comando `session_start()` - caso a mesma ainda tenha sido inicializada.

# Nota de Segurança e Privacidade
Considerando que o objetivo do FastAuth é ser simples de usar, ele não deve ser usado sem uma prévia consideração quanto a segurança, já que existem questões de segurança NÃO implementadas, e as que foram implementadas NÃO estão livres de falhas. Melhoramentos serão liberados eventualmente, contribua também!

Note que o banco de dado está acessível via URL, embora não seja uma boa prática "terceirizar" a segurança de um software, neste caso, a proteção/bloqueio do mesmo deverá ser implementada no servidor. Exemplos a seguir (não testados):

#### Apache
Direto na configuração:
````
<FilesMatch "\.?.+$">
 order allow,deny
 deny from all
</FilesMatch>
````
Via .htaccess:
````
RewriteRule /?\..+$ [F,L]
````

#### Nginx

````
   location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }
````

_** Uso de senha para autenticação único fator, normalmente NÃO é uma boa escolha!_
_** Uso de cookie e sessão é necessário para funcionamento deste software._

# Contribuição

Dê preferência por usar Netbeans como editor, por causa da formatação. Todos os arquivos foram formatados com a formatação automática fornecida por este aplicativo, em `Código Fonte -> Formatar`. Isso facilita a diferenciação e identificação de mudanças.

Ao adicionar recursos, procure sempre que possível manter o estilo de programação, para compatibilidade e não deixe de comentar.

# Sobre

Um projeto de [Jean Carlo EM](https://jeancarloem.com). Licensiado sob [Mozilla Public License 2.0+](https://www.mozilla.org/en-US/MPL/2.0/).

Eu agradeço se ao utilizar este software comunicar formalmente com detalhes do uso e URL para acesso. E uma doação sempre será bem vinda. Obrigado! :)