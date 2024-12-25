# OcoMon - versão 6.x
## Data: Dezembro de 2024
## Autor: Flávio Ribeiro (flaviorib@gmail.com)

## Licença: GPLv3


## IMPORTANTE:

Se você deseja instalar o OcoMon por conta própria, é necessário que saiba o que é um servidor WEB e esteja familiarizado com o processo genérico de instalação de sistemas WEB. 

Para instalar o OcoMon é necessário ter uma conta com permissão de criação de databases no MySQL ou MariaDB e acesso de escrita à pasta pública do seu servidor web.

Antes de iniciar o processo de instalação ou atualização **leia esse arquivo até o final.**


## REQUISITOS:

+ Servidor web com Apache(***não testado com outros servidores***), PHP e MySQL (ou MariaDB):
    
    - MySQL a partir da versão **5.6** (Ou MariaDB a partir da versão **10.2**)
    - PHP a partir da versão **8.1** com:
        - PDO
        - pdo_mysql
        - mbstring
        - openssl
        - imap
        - curl
        - iconv
        - gd
        - fileinfo
        - ldap (se for autenticar via LDAP)

    - Para utilização da API para integração ou para possibilitar a abertura de chamados por email:
        - O Apache precisa permitir reescrita de URL (para poder direcionar as rotas da API via htaccess);
        - O módulo "mod_rewrite" precisa estar habilitado no Apache;
        - O Apache precisa de permissão de escrita na pasta "api/ocomon_api/storage".

<br>

## INSTALAÇÃO OU ATUALIZAÇÃO EM AMBIENTE DE PRODUÇÃO:


#### IMPORTANTE (em caso de atualização)

+ É fortemente **recomendado** fazer **BACKUP** da sua base de dados! **Faça isso primeiro** e evite eventuais dores de cabeça. 

+ Identifique qual é a **sua versão instalada**. Após isso vá direto para a seção, neste documento, correspondente às instruções de atualização específicas para a sua versão. Para cada versão do OcoMon há **apenas UM arquivo SQL específico** (ou nenhum) a ser importado para o seu banco de dados.

+ Confira as novidades da versão em [https://ocomon.com.br/site/changelog-incremental/](https://ocomon.com.br/site/changelog-incremental/) para identificar novas possibilidades de uso e novas configurações.


### Atualização de versão:


#### Se a sua versão atual já é da série 6 - (PREVIEW TO SUPPORTERS):

Alguns usuários tiveram acesso à prévia da versão 6 antes do lançamento oficial. Se este for o seu caso, entre em contato pelo ocomon.oficial@gmail.com para avaliarmos se é necessário atualizar a sua base de dados (dependendo da série da versão pode ser necessário atualizar base de dados).


#### Se a sua versão atual é da série 5.x

**IMPORTANTE:** Sempre é recomendado realizar o **BACKUP** tanto dos scripts da versão em uso quanto do banco de dados atualmente em uso pelo sistema.

**IMPORTANTE 2:** Caso a sua versão tenha histórico de atualizações a partir de versões mais antigas, pode ser necessário atualizar o engine  de algumas tabelas que estejam em "MYISAM" para o engine "InnoDB". Portanto, **antes de iniciar o processo de atualização** confira os engines das tabelas do OcoMon para ter certeza de que estejam ok.

1. Importe o arquivo de atualização do banco de dados "02-UPDATE_FROM_5.x.sql" (em install/6.x/): <br>

	Ex. via linha de comando:
```shell
mysql -u root -p [database_name] < /caminho_para_o_ocomon_6.x/install/6.x/02-UPDATE_FROM_5.x.sql
```
Onde: [database_name]: É o nome do banco de dados do OcoMon

PS: Se preferir, você pode utilizar um gerenciador de banco de dados como o phpMyAdmin, por exemplo, para importar o script SQL.

2. Sobrescreva os scripts da sua versão pelos scripts da versão 6 (recomendado: mantenha apenas o seu arquivo de configurações "config.inc.php" e remova (ou mova para um diretório de backup) todos os demais scripts);

3. Por questões de segurança, após a importação do SQL, remova a pasta install. Pronto! Basta ajustar as novas configurações da versão diretamente via interface administrativa.




#### Se a sua versão atual é da série 4.x:

**IMPORTANTE:** Sempre é recomendado realizar o **BACKUP** tanto dos scripts da versão em uso quanto do banco de dados atualmente em uso pelo sistema.

**IMPORTANTE 2:** Caso a sua versão tenha histórico de atualizações a partir de versões mais antigas, pode ser necessário atualizar o engine  de algumas tabelas que estejam em "MYISAM" para o engine "InnoDB". Portanto, **antes de iniciar o processo de atualização** confira os engines das tabelas do OcoMon para ter certeza de que estejam ok.

1. Importe o arquivo de atualização do banco de dados "03-UPDATE_FROM_4.x.sql" (em install/6.x/): <br>

	Ex. via linha de comando:
```shell
mysql -u root -p [database_name] < /caminho_para_o_ocomon_6.x/install/6.x/03-UPDATE_FROM_4.x.sql
```

Onde: [database_name]: É o nome do banco de dados do OcoMon

PS: Se preferir, você pode utilizar um gerenciador de banco de dados como o phpMyAdmin, por exemplo, para importar o script SQL.

2. Sobrescreva os scripts da sua versão pelos scripts da versão 6 (recomendado: mantenha apenas o seu arquivo de configurações "config.inc.php" e remova (ou mova para um diretório de backup) todos os demais scripts);

3. Por questões de segurança, após a importação do SQL, remova a pasta install. Pronto! Basta ajustar as novas configurações da versão diretamente via interface administrativa.<br>


#### Se a sua versão atual é a 3.3:

**IMPORTANTE:** Sempre é recomendado realizar o **BACKUP** tanto dos scripts da versão em uso quanto do banco de dados atualmente em uso pelo sistema.

**IMPORTANTE 2:** Caso a sua versão tenha histórico de atualizações a partir de versões mais antigas, pode ser necessário atualizar o engine  de algumas tabelas que estejam em "MYISAM" para o engine "InnoDB". Portanto, **antes de iniciar o processo de atualização** confira os engines das tabelas do OcoMon para ter certeza de que estejam ok.

1. Importe o arquivo de atualização do banco de dados "04-DB-UPDATE-FROM-3.3.sql" (em install/6.x/): <br>

	Ex. via linha de comando:
```shell
mysql -u root -p [database_name] < /caminho_para_o_ocomon_6.x/install/6.x/04-DB-UPDATE-FROM-3.3.sql
```

Onde: [database_name]: É o nome do banco de dados do OcoMon

PS: Se preferir, você pode utilizar um gerenciador de banco de dados como o phpMyAdmin, por exemplo, para importar o script SQL.

2. Sobrescreva os scripts da sua versão pelos scripts da versão 6 (recomendado: mantenha apenas o seu arquivo de configurações "config.inc.php" e remova (ou mova para um diretório de backup) todos os demais scripts);

3. Por questões de segurança, após a importação do SQL, remova a pasta install. Pronto! Basta ajustar as novas configurações da versão diretamente via interface administrativa.<br>


#### Se a sua versão atual é a 3.2 ou 3.1 ou 3.1.1:

**IMPORTANTE:** Sempre é recomendado realizar o **BACKUP** tanto dos scripts da versão em uso quanto do banco de dados atualmente em uso pelo sistema.

**IMPORTANTE 2:** Caso a sua versão tenha histórico de atualizações a partir de versões mais antigas, pode ser necessário atualizar o engine  de algumas tabelas que estejam em "MYISAM" para o engine "InnoDB". Portanto, **antes de iniciar o processo de atualização** confira os engines das tabelas do OcoMon para ter certeza de que estejam ok.

1. Importe o arquivo de atualização do banco de dados "05-DB-UPDATE-FROM-3.2.sql" (em install/6.x/): <br>

	Ex. via linha de comando:
```shell
mysql -u root -p [database_name] < /caminho_para_o_ocomon_6.x/install/6.x/05-DB-UPDATE-FROM-3.2.sql
```

Onde: [database_name]: É o nome do banco de dados do OcoMon

PS: Se preferir, você pode utilizar um gerenciador de banco de dados como o phpMyAdmin, por exemplo, para importar o script SQL.

2. Sobrescreva os scripts da sua versão pelos scripts da versão 6 (recomendado: mantenha apenas o seu arquivo de configurações "config.inc.php" e remova (ou mova para um diretório de backup) todos os demais scripts);

3. Por questões de segurança, após a importação do SQL, remova a pasta install. Pronto! Basta ajustar as novas configurações da versão diretamente via interface administrativa.


#### Se a sua versão atual é a 3.0 (release final):

**IMPORTANTE:** Sempre é recomendado realizar o **BACKUP** tanto dos scripts da versão em uso quanto do banco de dados atualmente em uso pelo sistema.

**IMPORTANTE 2:** Caso a sua versão tenha histórico de atualizações a partir de versões mais antigas, pode ser necessário atualizar o engine  de algumas tabelas que estejam em "MYISAM" para o engine "InnoDB". Portanto, **antes de iniciar o processo de atualização** confira os engines das tabelas do OcoMon para ter certeza de que estejam ok.

1. Importe o arquivo de atualização do banco de dados "06-DB-UPDATE-FROM-3.0.sql" (em install/6.x/): <br>

	Ex. via linha de comando:
```shell
mysql -u root -p [database_name] < /caminho_para_o_ocomon_6.x/install/6.x/06-DB-UPDATE-FROM-3.0.sql
```

Onde: [database_name]: É o nome do banco de dados do OcoMon

PS: Se preferir, você pode utilizar um gerenciador de banco de dados como o phpMyAdmin, por exemplo, para importar o script SQL.

2. Sobrescreva os scripts da sua versão pelos scripts da versão 6 (recomendado: mantenha apenas o seu arquivo de configurações "config.inc.php" e remova (ou mova para um diretório de backup) todos os demais scripts);

3. Por questões de segurança, após a importação do SQL, remova a pasta install. Pronto! Basta ajustar as novas configurações da versão diretamente via interface administrativa.<br>


#### Se a sua versão atual é qualquer uma das releases candidates(rc) da versão 3.0 (rc1, rc2, rc3):

**IMPORTANTE:** Sempre é recomendado realizar o **BACKUP** tanto dos scripts da versão em uso quanto do banco de dados atualmente em uso pelo sistema.

**IMPORTANTE 2:** Caso a sua versão tenha histórico de atualizações a partir de versões mais antigas, pode ser necessário atualizar o engine  de algumas tabelas que estejam em "MYISAM" para o engine "InnoDB". Portanto, **antes de iniciar o processo de atualização** confira os engines das tabelas do OcoMon para ter certeza de que estejam ok.

1. Importe o arquivo de atualização do banco de dados "07-DB-UPDATE-FROM-3.0rcx.sql" (em install/6.x/): <br>

	Ex. via linha de comando:
```shell
mysql -u root -p [database_name] < /caminho_para_o_ocomon_6.x/install/6.x/07-DB-UPDATE-FROM-3.0rcx.sql
```

Onde: [database_name]: É o nome do banco de dados do OcoMon

PS: Se preferir, você pode utilizar um gerenciador de banco de dados como o phpMyAdmin, por exemplo, para importar o script SQL.

2. Sobrescreva os scripts da sua versão pelos scripts da versão 6 (recomendado: mantenha apenas o seu arquivo de configurações "config.inc.php" e remova (ou mova para um diretório de backup) todos os demais scripts);

3. Por questões de segurança, após a importação do SQL, remova a pasta install. Pronto! Basta ajustar as novas configurações da versão diretamente via interface administrativa.<br><br>


#### Se a sua versão atual é a versão 2.0 final (**Não é a versão 2.0RC6**)

+ Realize o **BACKUP** tanto dos scripts da versão em uso quanto do banco de dados atualmente em uso pelo sistema.

- **IMPORTANTE:** Entenda que a série 2.x do OcoMon funciona em PHP 5 e a versão atual só funciona em **PHP a partir da versão 8.1**. Portanto, o ambiente do OcoMon também precisará ser atualizado.

- **IMPORTANTE 2:** Atualize o engine de todas as tabelas do OcoMon que estejam com "MYISAM" e altere para "InnoDB"; Essa alteração é fundamental antes de realizar a importação do arquivo de atualização.

+ **IMPORTANTE 3:** Leia com atenção o arquivo changelog-3.0.md (*em /changelog*) para conferir as principais mudanças e principalmente sobre as **funções removidas de versões anteriores** e algumas novas **configurações necessárias** bem como mudanças de retorno sobre o tempo de SLAs para chamados pré-existentes.


+ O processo de atualização descrito a seguir considera que a versão corrente é a 2.0 (**release final**), portanto, se a sua versão for a 2.0RC6, vá para a seção relacionada.

+ **IMPORTANTE:** Dependendo da configuração do seu banco de dados quanto ao "case sensitive", será necessário renomear as seguintes tabelas (caso possuam a nomenclatura com a letra "X" em caixa alta): "areaXarea_abrechamado", "equipXpieces" para: "areaxarea_abrechamado", "equipxpieces". Isso **DEVE** ser feito **ANTES** de importar o arquivo SQL de atualização.

+ Para atualizar a partir da versão 2.0 (release final), basta sobrescrever os scripts da sua pasta do OcoMon pelos scripts da nova versão (recomendado: mantenha apenas o seu arquivo de configurações "config.inc.php" e remova (ou mova para um diretório de backup) todos os demais scripts) e importar para o MySQL o arquivo de atualização: 07-DB-UPDATE-FROM-2.0.sql (em /install/5.x/). <br><br>
1. Importe o arquivo de atualização do banco de dados "08-DB-UPDATE-FROM-2.0.sql" (em install/6.x/): <br>

	Ex. via linha de comando:
```shell
mysql -u root -p [database_name] < /caminho_para_o_ocomon_6.x/install/6.x/08-DB-UPDATE-FROM-2.0.sql
```

Onde: [database_name]: É o nome do banco de dados do OcoMon

PS: Se preferir, você pode utilizar um gerenciador de banco de dados como o phpMyAdmin, por exemplo, para importar o script SQL.

2. Sobrescreva os scripts da sua versão pelos scripts da versão 6 (recomendado: mantenha apenas o seu arquivo de configurações "config.inc.php" e remova (ou mova para um diretório de backup) todos os demais scripts);

3. Por questões de segurança, após a importação do SQL, remova a pasta install. Pronto! Basta ajustar as novas configurações da versão diretamente via interface administrativa.<br>

<br>

#### Se a sua versão atual é a versão 2.0RC6

+ Realize o **BACKUP** tanto dos scripts da versão em uso quanto do banco de dados atualmente em uso pelo sistema.

- **IMPORTANTE:** Entenda que a série 2.x do OcoMon funciona em PHP 5 e a versão atual só funciona em **PHP a partir da versão 8.1**. Portanto, o ambiente do OcoMon também precisará ser atualizado.

- **IMPORTANTE 2:** Atualize o engine de todas as tabelas do OcoMon que estejam com "MYISAM" e altere para "InnoDB"; Essa alteração é fundamental antes de realizar a importação do arquivo de atualização.

+ **IMPORTANTE 3:** Leia com atenção o arquivo changelog-3.0.md (*em /changelog*) para conferir as principais mudanças e principalmente sobre as **funções removidas de versões anteriores** e algumas novas **configurações necessárias** bem como mudanças de retorno sobre o tempo de SLAs para chamados pré-existentes.

+ O processo de atualização descrito a seguir considera que a versão corrente é a 2.0RC6 (**versão oficial**), portanto, se a sua versão possuir qualquer customização essa ação de **atualização não é recomendada**.

+ **IMPORTANTE:** Dependendo da configuração do seu banco de dados quanto ao "case sentitive", será necessário renomear as seguintes tabelas (caso possuam a nomenclatura com a letra "X" em caixa alta): "areaXarea_abrechamado", "equipXpieces" para: "areaxarea_abrechamado", "equipxpieces". Isso **DEVE** ser feito **ANTES** de importar o arquivo SQL de atualização.

+ Para atualizar a partir da versão 2.0RC6, basta sobrescrever os scripts da sua pasta do OcoMon pelos scripts da nova versão (recomendado: mantenha apenas o seu arquivo de configurações "config.inc.php" e remova (ou mova para um diretório de backup) todos os demais scripts) e importar para o MySQL o arquivo de atualização: 08-DB-UPDATE_FROM_2.0RC6.sql (em /install/5.x/). <br><br>
1. Importe o arquivo de atualização do banco de dados "09-DB-UPDATE_FROM_2.0RC6.sql" (em install/6.x/): <br>

	Ex. via linha de comando:
```shell
mysql -u root -p [database_name] < /caminho_para_o_ocomon_6.x/install/6.x/09-DB-UPDATE_FROM_2.0RC6.sql
```

Onde: [database_name]: É o nome do banco de dados do OcoMon

PS: Se preferir, você pode utilizar um gerenciador de banco de dados como o phpMyAdmin, por exemplo, para importar o script SQL.

2. Sobrescreva os scripts da sua versão pelos scripts da versão 6 (recomendado: mantenha apenas o seu arquivo de configurações "config.inc.php" e remova (ou mova para um diretório de backup) todos os demais scripts);

3. Por questões de segurança, após a importação do SQL, remova a pasta install. Pronto! Basta ajustar as novas configurações da versão diretamente via interface administrativa.<br>

<br/><br/>
### Primeira instalação:

O processo de instalação é bastante simples e pode ser realizado seguindo 3 passos:

1. **Instalar os scripts do sistema:**

Descompacte o contéudo do pacote do OcoMon_6x no diretório público do seu servidor web (*o caminho pode variar dependendo da distribuição ou configuração, mas de modo geral costuma ser **/var/www/html/***).

As permissões dos arquivos podem ser as padrão do seu servidor (exceto para a pasta api/ocomon_api/storage, que precisa permitir escrita pelo usuário do Apache).

2. **Criação da base de dados:**<br>
#### SISTEMA HOSPEDADO LOCALMENTE
(**localhost** - Se o sistema será instalado em um servidor externo pule para a seção [SISTEMA EM HOSPEDAGEM EXTERNA]):
    
Para a criação de toda a base do OcoMon, você precisa importar um único arquivo de instruções SQL:
    
O arquivo é:
`01-DB_OCOMON_6.x-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql (em /install/6.x/)`

Linha de comando (caso tenha acesso ao terminal):

```shell
mysql -u root -p < /caminho_para_o_ocomon_6.x/install/6.x/01-DB_OCOMON_6.x-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql
```

O sistema irá solicitar a senha do usuário root (ou de qualquer outro usuário que tenha sido fornecido ao invés de root no comando acima) do MySQL.

O comando acima irá criar o usuário "ocomon_6" com a senha padrão "senha_ocomon_mysql", e a base de dados "ocomon_6" com todas as informações necessárias para a inicialização do sistema.

**É importante alterar essa senha do usuário "ocomon_6" no MySQL logo após a instalação do sistema.**

Caso prefira, você pode realizar a importação do arquivo SQL utilizando qualquer gerenciador de banco de dados de sua preferência, neste caso, não é necessário utilizar o terminal.

Caso queira que a base e/ou usuario tenham outro nome (ao invés de "ocomon_6"), edite diretamente no arquivo (*identifique as entradas relacionadas ao nome do banco, usuário e senha no início do arquivo*):

`01-DB_OCOMON_6.x-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql`

antes de realizar a importação do mesmo. Utilize essas mesmas informações no arquivo de configurações do sistema (passo **3**).
    
**Após a importação, é recomendável a exclusão da pasta "install".**<br><br>


#### SISTEMA EM HOSPEDAGEM EXTERNA:

Se o sistema será instalado em um servidor externo, nesse caso, em função de eventuais limitações de criação para nomenclatura de databases e usuários (geralmente o provedor estipula um prefixo para os databases e usuários), é recomendado utilizar o nome de usuário oferecido pelo próprio serviço de hosting ou então criar um usuário específico (se a sua conta de usuário permitir) diretamente pela sua interface de acesso ao banco de dados. Sendo assim:

- **Crie** uma database específica para o OcoMon (você define o nome);
- **Crie** um usuário específico para acesso à database do OcoMon (ou utilize seu usuário padrão);
- **Altere** o script "01-DB_OCOMON_6.x-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql" **removendo** as seguintes linhas do início do arquivo:

```sql
CREATE DATABASE /*!32312 IF NOT EXISTS*/`ocomon_6` /*!40100 DEFAULT CHARACTER SET utf8 */;

CREATE USER 'ocomon_6'@'localhost' IDENTIFIED BY 'senha_ocomon_mysql';
GRANT SELECT , INSERT , UPDATE , DELETE ON `ocomon_6` . * TO 'ocomon_6'@'localhost';
GRANT Drop ON ocomon_6.* TO 'ocomon_6'@'localhost';
FLUSH PRIVILEGES;

USE `ocomon_6`;
```

Após isso basta importar o arquivo alterado e seguir com o processo de instalação.

Caso tenha acesso ao terminal:

```shell
mysql -u root -p [database_name] < /caminho_para_o_ocomon_6.x/install/6.x/01-DB_OCOMON_6.x-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql
```

Onde: [database_name] é o nome da database que foi criada manualmente.<br>

PS: Ao invés de importar o arquivo via terminal, você pode utilizar um gerenciador de banco de dados como o phpMyAdmin, por exemplo.



3. **Criar o arquivo de configurações:**

Faça uma cópia do arquivo config.inc.php-dist (*/includes/*) e renomeie para config.inc.php. Nesse novo arquivo, confira e revise as informações relacionadas à conexão com o banco de dados (*servidor, base de dados, usuário e senha*).<br><br>


## VERSÃO PARA TESTES:


Caso queira testar o sistema antes de instalar, você pode rodar um container Docker com o sistema já funcionando com alguns dados já populados. Se você já possui o Docker instalado em seu ambiente, então basta executar o seguinte comando em seu terminal:

```shell
docker run -it -p 8080:80 flaviorib/ocomon:6.0 /start_ocomon
```

Em seguida basta abrir o seu navegador e acessar pelo seguinte endereço:

`localhost:8080`

E pronto! Você já está com uma instalação do OcoMon prontinha para testes com os seguintes usuários cadastrados:<br>


| usuário   | Senha    | Descrição                           |
| :-------- | :------- | :---------------------------------- |
| admin     | admin    | Nível de administração do sistema   |
| operador  | operador | Operador padrão – nível 1           |
| abertura  | abertura | Apenas para abertura de ocorrências |


Caso não tenha o Docker, acesse o site e instale a versão referente ao seu sistema operacional:

[https://docs.docker.com/get-docker/](https://docs.docker.com/get-docker/)<br>

Ou então assista a esse vídeo para ver como é simples testar o OcoMon sem precisar de nenhuma instalação:
[https://www.youtube.com/watch?v=Wtq-Z4M9w5M](https://www.youtube.com/watch?v=Wtq-Z4M9w5M)<br>



## PRIMEIROS PASSOS


ACESSO

usuário: admin
    
senha: admin (Não esqueça de alterar esse senha tão logo tenha acesso ao sistema!!)

Novos usuários podem ser criados no menu Admin -> Usuários
<br><br>


## CONFIGURAÇÕES GERAIS DO SISTEMA


Algumas configurações precisam ser ajustadas dependendo da intenção de uso para o sistema:

- Arquivo de configuração: /includes/config.inc.php
    - nesse arquivo estão as informações de conexão com o banco;


Alguns serviços precisam estar habilitados no agendador de tarefas do sistema (cron no Linux). Na pasta `install/crontab` há o arquivo `crontab.txt` com o exemplo de configuração para o `crontab`.

- sendEmail.php: serviço responsável pelo gerenciamento de fila de e-mails
* update_auto_approval.php: serviço responsável pela autoaprovação de atendimentos;
* update_auto_close_due_inactivity.php: serviço responsável pelo autoencerramento nos casos configurados;
* getMailAndOpenTicket.php: serviço responsável pela abertura de chamados por e-mail
* getMailAndOpenTicketAzure.php: serviço responsável pela abertura de chamados por e-mail nos casos Azure


Para possibilitar o controle de quantidade de requisições, caso se esteja utilizando a API diretamente ou por meio da abertura de chamados por email, é necessário que o usuário do Apache tenha permissão de escrita no diretório "api/ocomon_api/storage".


As demais configurações do sistema são todas acessíveis por meio do menu de administração diretamente na interface do sistema. 
<br><br>


## INTEGRAÇÃO:

Acesse a documentação em [https://ocomon.com.br/site/integracao/](https://ocomon.com.br/site/integracao/)

## DOCUMENTAÇÃO:


Toda a documentação do OcoMon está disponível no site do projeto e no canal no Youtube:

+ Site oficial: [https://ocomon.com.br/site/](https://ocomon.com.br/site/)

+ Changelog: [https://ocomon.com.br/site/changelog-incremental/](https://ocomon.com.br/site/changelog-incremental/)

+ Canal no Youtube: [https://www.youtube.com/c/OcoMonOficial](https://www.youtube.com/c/OcoMonOficial)




## Doações

Amigos, como podem imaginar, o desenvolvimento e manutenção de um software livre para a comunidade é uma atividade dispendiosa que requer muita dedicação, motivação e esforço para que o projeto se mantenha relevante e continue agregando boas funcionalidades e evoluindo em diversos aspectos.

Conseguem imaginar a quantidade de tempo que é investido em planejamento, desenvolvimento, criação de material e suporte gratuito para a comunidade? Garanto que não é pouco.

Tudo isso ocorre pela crença na causa do Software Livre. Acreditar no software livre também é acreditar que juntos somos mais fortes e que dessa forma podemos obter realizações e fazermos a diferença nessa sociedade tão desigual.

Se o OcoMon lhe tem sido útil, poupado seu trabalho e lhe permitido direcionar seus recursos para outros investimentos, considere contribuir para a continuidade e crescimento do projeto: [https://ocomon.com.br/site/doacoes/](https://ocomon.com.br/site/doacoes/)

<br>Tenho convicção de que o OcoMon tem potencial para ser a ferramenta que lhe será indispensável na organização e gerência de sua área de atendimento liberando seu precioso tempo para outras realizações.

Bom uso!! :)

## Entre em contato:
+ E-mail: [ocomon.oficial@gmail.com](ocomon.oficial@gmail.com)

Flávio Ribeiro
[flaviorib@gmail.com](flaviorib@gmail)

