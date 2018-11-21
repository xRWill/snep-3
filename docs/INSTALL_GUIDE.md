[![N|Solid](https://imgs.opens.com.br/docs/opens/img-snep-off.png)](https://snep.com.br)

<!-- TOC -->

- [1. Bem vindo](#1-bem-vindo)
- [2. Download dos fontes](#2-download-dos-fontes)
- [3. Procedimentos de instalação](#3-procedimentos-de-instalação)
  - [3.1. Requisitos](#31-requisitos)
  - [3.2. Instalando o Apache](#32-instalando-o-apache)
  - [3.3. Instalando o MySQL](#33-instalando-o-mysql)
  - [3.4. Instalando das dependências para o processo de compilação](#34-instalando-das-dependências-para-o-processo-de-compilação)
  - [3.5. Ajustando o PHP](#35-ajustando-o-php)
  - [3.6. Instalando do Asterisk](#36-instalando-do-asterisk)
    - [3.6.1. Pré-compilando o Asterisk](#361-pré-compilando-o-asterisk)
    - [3.6.2. Preparando e compilando o Asterisk](#362-preparando-e-compilando-o-asterisk)
    - [3.6.3. Criando o inicializador do Asterisk](#363-criando-o-inicializador-do-asterisk)
    - [3.6.4. Ajustando o inicializador do asterisk](#364-ajustando-o-inicializador-do-asterisk)
  - [3.7. Instalando o SNEP](#37-instalando-o-snep)
    - [3.7.1. Ajustando as permissões](#371-ajustando-as-permissões)
    - [3.7.2. Ajustando os diretórios](#372-ajustando-os-diretórios)
    - [3.7.3. Instalando arquivos default do SNEP para Asterisk](#373-instalando-arquivos-default-do-snep-para-asterisk)
    - [3.7.4. Ajustando configurações dos sons do Asterisk - MOH (Music on Hold)](#374-ajustando-configurações-dos-sons-do-asterisk---moh-music-on-hold)
    - [3.7.5. Ajustando sons do Asterisk com o SNEP](#375-ajustando-sons-do-asterisk-com-o-snep)
    - [3.7.6. Criando a base de dados, usuário e dados iniciais](#376-criando-a-base-de-dados-usuário-e-dados-iniciais)
  - [3.8. Ajustes finais](#38-ajustes-finais)
    - [3.8.1. Caso seu sistema seja 32 bits:](#381-caso-seu-sistema-seja-32-bits)
    - [3.8.2. Caso seu sistema seja 64 bits:](#382-caso-seu-sistema-seja-64-bits)
- [4. A Interface Web e a ITC](#4-a-interface-web-e-a-itc)
  - [4.1. Boas vindas da Intercomunexão (ITC)](#41-boas-vindas-da-intercomunexão-itc)

<!-- /TOC -->


# 1. Bem vindo

O SNEP é uma família de soluções capaz de transformar a comunicação das Empresas,integrando voz, dados e sistemas.

A função de um PBX IP Híbrido (digital, Analógico e IP), Gerenciador de Contact e Call Center, Monitoramento de qualidade de atendimento e Gateway de voz, o SNEP é um forte aliado para reduzir os custos com comunicação e aumentar a eficiência nas relações empresariais.

Agora você é mais um membro da Família SNEP e este manual irá auxiliá-lo a tirar o maior proveito do seu SNEP. Aqui você encontrará informações sobre configurações,
funcionamento de cada rotina, exemplos de uso, etc.

Para melhor compreensão, este manual foi dividido em capítulos ilustrados que exemplificam a utilização de cada item da Interface do Sistema.

A cada atualização do seu produto, seu manual será também atualizado de forma a mantê-lo sempre informado de como tirar o maior benefício da sua solução SNEP.

**Seja bem-vindo!**

# 2. Download dos fontes

Veja o documento:  [Como usar o repositório do SNEP 3](/docs/REPOSITORY_SNEP_GUIDE.md)

# 3. Procedimentos de instalação

## 3.1. Requisitos

- O processo de instalação está baseado em Linux Debian, versão 8 (Jessie)
- Caso use outra versão do Debian ou outra distro Linux, fique atento para o **diretório default do apache** e/ou o **usuário/grupo usado pelo apache**.

## 3.2. Instalando o Apache

```
apt-get install apache2
```

## 3.3. Instalando o MySQL

```
apt-get install mysql-server
```

## 3.4. Instalando das dependências para o processo de compilação

```
apt-get install unixodbc unixodbc-dev libmyodbc odbcinst1debian2 libcurl3 libncurses5-dev git && apt-get install php5 php5-cgi php5-mysql php5-gd php5-curl build-essential lshw libjansson-dev && apt-get install libssl-dev sox sqlite3 libsqlite3-dev libapache2-mod-php5 libxml2-dev uuid-dev
```

## 3.5. Ajustando o PHP

- Habilite o php-cgi para receber parâmetros. Insira **On** em *register_argc_argv* no arquivo */etc/php5/cgi/php.ini*. 
- Reinicie o apache em seguida: 
```
/etc/init.d/apache2 restart
```

## 3.6. Instalando do Asterisk

Acesse o diretório onde será instalado o Asterisk e inicie o download.

```
cd /usr/src/

wget http://downloads.asterisk.org/pub/telephony/asterisk/old-releases/asterisk-13.10.0.tar.gz    ou     wget -c http://dialplanreload.com/downloads/asterisk-13.10.0.tar.gz

tar xvf asterisk-13.10.0.tar.gz
``` 

### 3.6.1. Pré-compilando o Asterisk

```
cd /usr/src/asterisk*
./configure
```

### 3.6.2. Preparando e compilando o Asterisk

Execute o comando:
```
make menuselect
```
e então siga os seguintes passos:

- selecione a opção: Voicemail Build Options
- marque a opção: [*] ODBC_STORAGE
- tecle ESC 2 vezes
- tecle S para confirmar alterações

Em seguida, execute os comandos:
```
make
make install
```

### 3.6.3. Criando o inicializador do Asterisk

```
cd /usr/src/asterisk*
cp contrib/init.d/rc.debian.asterisk /etc/init.d/asterisk
chmod +X /etc/init.d/asterisk
update-rc.d asterisk defaults
```

### 3.6.4. Ajustando o inicializador do asterisk

Edite o arquivo /etc/init.d/asterisk e ajuste as seguintes linhas para o conteúdo descrito a seguir:

```
DAEMON=/usr/sbin/asterisk
ASTVARRUNDIR=/var/run/asterisk
ASTETCDIR=/etc/asterisk
```

## 3.7. Instalando o SNEP

```
cd /var/www/html
git clone __seu_fork_do_snep__ .

OU SE PREFERIR/NÃO TIVER CONTA NO BITBUCKET

wget -c https://sourceforge.net/projects/snep/files/snep/snep-3/snep_3.06.2.tar.gz
tar -xvf snep_3.06.2.tar.gz
mv snep-3 snep
```

Exemplo: **git clone https://bitbucket.org/snepdev/snep-3.git**.

Para mais informações sobre o git/clone veja o tutorial : Guia de contribuições para o SNEP 3.

### 3.7.1. Ajustando as permissões

```
cd /var/www/html
find . -type f  -exec chmod 640 {} \; -exec chown www-data:www-data {} \;
find . -type d  -exec chmod 755 {} \; -exec chown www-data:www-data {} \;
chmod +x /var/www/html/snep/agi/*
```

### 3.7.2. Ajustando os diretórios

```
mkdir /var/log/snep
cd /var/log/snep
touch ui.log
touch agi.log
chown -R www-data.www-data *
cd /var/www/html/snep/
ln -s /var/log/snep logs
cd /var/lib/asterisk/agi-bin/
ln -s /var/www/html/snep/agi/ snep
cd /etc/apache2/sites-enabled/
ln -s /var/www/html/snep/install/snep.apache2 001-snep
cd /var/spool/asterisk/
rm -rf monitor
ln -sf /var/www/html/snep/arquivos monitor
```

### 3.7.3. Instalando arquivos default do SNEP para Asterisk

```
cd /etc
rm -rf asterisk
cp -avr /var/www/html/snep/install/etc/asterisk .
cp /var/www/html/snep/install/etc/odbc* .
3.7.4 Ajustando configurações dos sons do Asterisk
cd  /var/www/html/snep/install/sounds
mkdir /var/lib/asterisk/sounds/en
tar -xzf asterisk-core-sounds-en-wav-current.tar.gz -C /var/lib/asterisk/sounds/en
tar -xzf asterisk-extra-sounds-en-wav-current.tar.gz  -C /var/lib/asterisk/sounds/en

mkdir /var/lib/asterisk/sounds/es
tar -xzf asterisk-core-sounds-es-wav-current.tar.gz  -C /var/lib/asterisk/sounds/es

mkdir /var/lib/asterisk/sounds/pt_BR
tar -xzf asterisk-core-sounds-pt_BR-wav.tgz -C /var/lib/asterisk/sounds/pt_BR

cd /var/lib/asterisk/sounds
mkdir -p es/tmp es/backup en/tmp en/backup pt_BR/tmp pt_BR/backup
chown -R www-data:www-data *
```

### 3.7.4. Ajustando configurações dos sons do Asterisk - MOH (Music on Hold)

```
cd /var/lib/asterisk/moh
mkdir tmp backup
chown -R www-data.www-data * 
rm -f *-asterisk-moh-opsound-wav
```

### 3.7.5. Ajustando sons do Asterisk com o SNEP
```
mkdir -p /var/www/html/snep/sounds
cd /var/www/html/snep/sounds/
ln -sf /var/lib/asterisk/moh/ moh
ln -sf /var/lib/asterisk/sounds/pt_BR/ pt_BR
```

### 3.7.6. Criando a base de dados, usuário e dados iniciais
```
cd /var/www/html/snep/install/database
mysql -u root -p < database.sql
mysql -u root -p snep < schema.sql
mysql -u root -p snep < system_data.sql
mysql -u root -p snep < core-cnl.sql
mysql -u root -p snep < ../../modules/billing/install/schema.sql
mysql -u root -p snep < /var/www/html/snep/modules/loguser/install/schema.sql
```

## 3.8. Ajustes finais

Após a instalação, é preciso efetuar as seguintes modificações no linux:

### 3.8.1. Caso seu sistema seja 32 bits:

| Arquivo | Modificação |
|------|-----------|
| /etc/odbcinst.ini	| em Driver, modificar caminho para: /usr/lib/i386-linux-gnu/odbc/libodbctxt.so. |
| /etc/odbcinst.ini	| em Setup, modificar caminho para: /usr/lib/i386-linux-gnu/odbc/libodbctxtS.so |
| /etc/odbc.ini	| em Driver, modificar caminho para: /usr/lib/i386-linux-gnu/odbc/libmyodbc.so |


### 3.8.2. Caso seu sistema seja 64 bits:

| Arquivo | Modificação |
|------|-----------|
| /etc/odbcinst.ini	| em Driver, modificar caminho para: /usr/lib/x86_64-linux-gnu/odbc/libodbctxt.so |
| /etc/odbcinst.ini	| em Setup, modificar caminho para: /usr/lib/x86_64-linux-gnu/odbc/libodbctxtS.so |
| /etc/odbc.ini	| em Driver, modificar caminho para: /usr/lib/x86_64-linux-gnu/odbc/libmyodbc.so|


# 4. A Interface Web e a ITC

Após finalizada a instalação, acesse a interface web do SNEP  através do seu browser e informe o usuário e senha padrões:

**Usuário:** admin

**Senha:** admin123

![N|Solid](https://opens-images.s3.amazonaws.com/snep/manual/login.PNG)

## 4.1. Boas vindas da Intercomunexão (ITC)

Após informar usuário e senha pela primeira vez, será a presentada a tela de boas vindas da Intercomunexão (ITC).

![N|Solid](https://opens-images.s3.amazonaws.com/snep/manual/register_snep.png)

onde:

|Opção | Descrição |
|----- | --------- |
|**Já sou cadastrado** | Permite que você utilize um usuário/senha já existente no [ITC](www.intercomunexao.com.br)|
|**Quero me registrar**|Permite que você crie sua conta diretamente na Intercomunexão |
|**Não gostaria de me registrar neste momento** |Mensagem opcional de acordo com o perfil/versão do SNEP. Quando o registro é considerado obrigatório esta mensagem não será exibida.|

---

**`ERROS`**

Caso não haja conexão de internet para com o ITC, será apresentado um erro, de acordo com o perfil/versão do SNEP, não será possível prosseguir/utilizar o SNEP.

Você pode tentar o registro manual seguindo este processo: [Problema no registro do Snep](/docs/REGISTER_ERROR.md).