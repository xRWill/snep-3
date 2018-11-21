[![N|Solid](https://imgs.opens.com.br/docs/opens/img-snep-off.png)](https://snep.com.br)

<!-- TOC -->

- [Conceitos](#conceitos)
    - [Git](#git)
    - [Bitbucket](#bitbucket)
    - [Branches](#branches)
- [Repositório do SNEP 3](#repositório-do-snep-3)
    - [Os branches principais](#os-branches-principais)
    - [Os branches de apoio](#os-branches-de-apoio)
- [O Wiki (documentação)](#o-wiki-documentação)
- [Utilizando o repositório do SNEP](#utilizando-o-repositório-do-snep)
    - [1º Passo : Acesso ao repositório do SNEP no Bitbucket](#1º-passo--acesso-ao-repositório-do-snep-no-bitbucket)
    - [2º Passo : Realizando o Fork do repositório Snep](#2º-passo--realizando-o-fork-do-repositório-snep)
    - [3º Passo - Clonando o Fork em um diretório local](#3º-passo---clonando-o-fork-em-um-diretório-local)
        - [Linux / Mac OS](#linux--mac-os)
        - [Windows](#windows)
    - [4º Passo - Realizando um Pull Request](#4º-passo---realizando-um-pull-request)
- [Vinculando uma Issue à um Commit](#vinculando-uma-issue-à-um-commit)

<!-- /TOC -->

# Conceitos

## Git

Git pronunciado [git] é um sistema de controle de versão distribuído e um sistema de gerenciamento de código fonte, com ênfase em velocidade. O Git foi inicialmente projetado e desenvolvido por Linus Torvalds para o desenvolvimento do kernel Linux, mas foi adotado por muitos outros projetos.

Cada diretório de trabalho do Git é um repositório com um histórico completo e habilidade total de acompanhamento das revisões, não dependente de acesso a uma rede ou a um servidor central.

## Bitbucket 

É um serviço de hospedagem de projetos controlados através do Mercurial1 , um sistema de controle de versões distribuído. É similar ao GitHub (que utiliza Git, somente). 

## Branches

Um branch no Git é simplesmente um leve ponteiro móvel para um commit. O nome do branch padrão no Git é master. Como você inicialmente fez commits, você tem um branch principal **(master branch)** que aponta para o último commit que você fez. Cada vez que você faz um commit ele avança automaticamente.

# Repositório do SNEP 3

## Os branches principais

|Branch|Conceito   |
|------|-----------|
|**master**| É onde está o código final, liberado para o usuário final. Neste repositório não é feita/postada nenhuma alteração, com exceção de hotfixes.|
|**develop**| Tem origem no **master**. <br/> **Conceito:** é onde está a base do código para a próxima versão e/ou release. As features são sempre baseadas neste código. Quando o código fonte do **develop** se tornar estável e estiver pronto para ser liberado, todas as mudanças são **merged** com o **master** e então um novo número de versão ou release é criado.|

## Os branches de apoio

|Branch|Conceito   |
|------|-----------|
|**hotfixes**|Tem origem no: **master**. <br/> Faz merge em: **developer** e **master**. <br/> A convenção de nome é: hotfix_* . <br/> **Conceito:** é onde está o código do manter, mas que precisa de pequenos ajustes, como por exemplo uma correção gramatical.|
|**release branches**|Tem origem no : developer. <br/> Faz merge em: developer e master <br/> A convenção de nome é: release_* <br/> **Conceito:** é a etapa final antes de liberar uma versão de produção. É onde são feitos os ajustes finos da versão, como por exemplo a atualização dos arquivos descritores de módulo ou do core do SNEP.|
|**feature branches**| Tem origem no: develop <br/> Faz merge em: develop. <br/> A convenção de nome é: feature-TDS-*</br> **Conceito:** É onde são desenvolvidas novas features. Estes repositórios existem basicamente no repositório do desenvolvedor.|

# O Wiki (documentação)

O Wiki do repositório existe para que toda a documentação esteja padronizada e disponível para contribuições e ajustes. Para contribuir com a documentação, acesse o **Wiki do repositório**.

![N|Solid](https://opens-images.s3.amazonaws.com/snep/manual/wiki.png)

# Utilizando o repositório do SNEP

## 1º Passo : Acesso ao repositório do SNEP no Bitbucket

- Crie, caso não  possua, seu login no Bitbucket: https://bitbucket.org/account/signin/?next=/

- Para contribuir com o Projeto você precisará essencialmente:

    - Fazer uma "cópia" do Projeto em sua máquina local. Esta "cópia" pode ser um **fork** ou um **clone**. A diferença entre eles basicamente são permissões de acesso. Em qualquer um deles você conseguirá contribuir.
    - Depois de feito suas correções, você deve fazer uma requisição de envio, Pull Request, que será avaliada pelo time de desenvolvimento e poderá ser: aprovada, comentada ou rejeitada.

## 2º Passo : Realizando o Fork do repositório Snep

- Faça login no Bitbucket (https://bitbucket.org)
- No canto superior direito da tela, há um campo de pesquisa
    - Faça uma busca por "**snepdev/snep-3**"
- Acesse o repositório encontrado
- No canto esquerdo selecione a opção 'Fork'
    - Caso sua conta é membro de algum team no Bitbucket, a página irá conter um campo Proprietário. Já se você não pertencer a nenhum team, não haverá o campo Proprietário
- Mude o campo **Nome**, por exemplo, para Snep-Fork
- Adicione uma descrição apropriada
- Clique em 'Fork repository'

## 3º Passo - Clonando o Fork em um diretório local
 
### Linux / Mac OS

Se quiser participar do projeto , para poder clonar o seu Fork para um diretório local é necessário possuir uma chave SSH vinculada a sua conta no Bitbucket, caso já possua avance para o passo 13.

*Se quiser somente baixar os fontes, vá para o passo 13.*

- Acesse o terminal da sua máquina
- Crie uma chave SSH
- Digite no terminal o comando "ssh-keygen -t dsa"
- O console irá perguntar aonde você gostaria de salvar sua chave, por padrão fica localizada em '~/.ssh/'
- Digite uma senha para ser criptografada
- Digite novamente a senha para a criação de sua chave SSH
- Vá até o diretório onde sua chave foi gravada, por padrão o comando é 'cd ~/.ssh/' 
- De o comando 'cat id_dsa.pub' e copie o conteúdo do arquivo
- Acesse a página do seu repositório
- No canto superior direito clique no seu usuário e selecione "Manage account"
- Selecione no canto esquerdo a opção SSH keys, aparece uma tela aonde será cadastrado suas chaves SSH
- Clique em "Add key" e preencha o campo "Label" com uma descrição da sua chave e no campo "Key" cole as informações copiadas do arquivo "id_rsa.pub", clique no botão "Add key" para confirmar
- Volte para a página do seu repositório e clique no botão/menu 'Actions' no lado esquerdo da tela
- Selecione a opção "Clone" e copie o comando exibido na tela
Caso não possui o git instalado em sua máquina, instale-o utilizando o seu gerenciador de pacotes
- Acesse o diretório que você deseja fazer o clone e cole o comando copiado

### Windows

- Instale o aplicativo TortoiseHG Workbench, o download da aplicação pode ser feito em http://tortoisehg.bitbucket.org/.
- Abra o aplicativo.
- Selecione View > Show Repository Registry.
- Selecione File > Clone Repository.
- No campo Source entre com a URL do repositório que será feito o Fork. 
- Em Destination informe o endereço no seu sistema aonde o repositório local ficará gravado.
- Pressione Clone para finalizar.

## 4º Passo - Realizando um Pull Request

Caso você queira colaborar com o desenvolvimento do Snep, enviando alguma correção, alteração ou melhoria será necessário realizar um Pull Request. 

- Clique no botão 'Actions' e selecione a opção 'Create a Pull Request'
- Na caixa à **esquerda** selecione  branch onde estão as suas alterações
- Na caixa à **direita** selecione o repositório (**snepdev/snep-2**) e o branch destinado ao Pull Request (**develop**)
- Preencha os outros campos do formulário (quanto mais informações, mais rápida e clara será a liberação do pull request)
- Finalize clicando no botão 'Create pull request'
- Será enviado para a equipe de desenvolvimento do SNEP uma solicitação contendo sua request. Esta request  poderá ser aprovada ou não. Em caso de aprovação ela fará parte das próximas versões.
    - Você receberá um email informando se sua request foi aprovada ou não.

![N|Solid](https://opens-images.s3.amazonaws.com/snep/manual/fluxo_pullrequest.png)

# Vinculando uma Issue à um Commit

As Issues, Tickets do SNEP ficam no próprio repositório do [BitBucket](https://bitbucket.org/snepdev/snep-3/issues?status=new&status=open).

Quando você estiver trabalhando em um Commit que está relacionado à um Ticket destes você pode fechá-lo automaticamente ou fazer qualquer outra ação nela através do próprio commit.

Para isso siga este [passo à passo](https://confluence.atlassian.com/bitbucket/resolve-issues-automatically-when-users-push-code-221451126.html).

