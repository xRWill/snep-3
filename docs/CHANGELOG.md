[![N|Solid](https://imgs.opens.com.br/docs/opens/img-snep-off.png)](https://snep.com.br)

# Changelog Snep #

Nesse documento é possível visualizar quais itens foram implementados em cada versão do Snep. 


# Tipos de desenvolvimento #

Cada item adicionado na versão poderá ser um dos seguintes tipos:

| Tipo | Descrição |
|------|-----------|
| **`NOVO`** | Quando há um desenvolvimento de uma nova funcionalidade no projeto. |
| **`MELHORIA`** | Quando é desenvolvida uma melhoria em uma funcionalidade do projeto. |
| **`CORREÇÃO`** | Quando há alguma correção de algum erro encontrado no projeto. |
----

# Versões #

## 3.07 ##
*21 de Novembro de 2018*

* **`NOVO`** - Opção de ativar desativar ramal.

* **`NOVO`** - Opção de criar troncos desativados.

* **`NOVO`** - Adicionado total de tarifação em chamadas do período.

* **`NOVO`** - Nova ação de grupo de captura para regras de fila.

* **`NOVO`** - Configuração BLF no ramal.

* **`NOVO`** - Recurso discar para ramal na ação da URA.

* **`NOVO`** - Funcionalidade de auditoria.

* **`NOVO`** - Opção de criar senhas seguras no cadastro de ramais. (Contribuicao:  Edgar Wesley Braga Mariano)

* **`MELHORIA`** - Verificação de senha forte no cadastro de ramais.

* **`MELHORIA`** - Melhoria no layout das listagens de crud.

* **`MELHORIA`** - Melhoria no simulador.

* **`MELHORIA`** - Adicionado novos campos na fucionalidade de exportação de dados.

* **`MELHORIA`** - Refeitos relatórios do Snep ue apresentavam erro de conexão.

* **`MELHORIA`** - Ajuste em extensions.conf e features.conf.

* **`MELHORIA`** - Envio de email para servidores próprios.

* **`MELHORIA`** - Melhoria de volume de consulta aos serviços cloud.

* **`MELHORIA`** - Não 'mais apresentado o menu quando usuário não possui permissão.

* **`CORREÇÃO`** - Novo formato de alias de expressão para celulares.

* **`CORREÇÃO`** - Lista de codecs na edição de extensões do IAX2.

* **`CORREÇÃO`** - Ligações não atendidas com gravação.

* **`CORREÇÃO`** - Problema de transferência quando a extensão destino não está disponível.

* **`CORREÇÃO`** - Visualização de placas Khomp do erro.

* **`CORREÇÃO`** - Menus Status IP mostra latência somente do primeiro tronco.

* **`CORREÇÃO`** - Validação de campo email no cadastro de contato.

* **`CORREÇÃO`** - Upload de áudios.

* **`CORREÇÃO`** - Erro ao salvar callerid quando se edita o ramal.

* **`CORREÇÃO`** - Importação de contatos via CSV com acentos.

* **`CORREÇÃO`** - Estacionamento de chamadas.

* **`CORREÇÃO`** - Correção para adicionar mais de um aliás de data.

----
## 3.06 ##
*13 de setembro de 2017*

* **`NOVO`** - Nova opção no filtro por contatos ou grupo de contatos no relatório de chamadas do período (Contribuicao: Fabio Theodoro - Ipcom).

* **`NOVO`** - Nova opção no filtro do relatórios de chamadas e ranking para troca do número pelo nome do contato ou ramal.

* **`NOVO`** - API para consulta de contatos.

* **`NOVO`** - Suporte a gravações no formato wav49.

* **`NOVO`** - Opção de escolher o dia do fechamento no controle de minutos no tronco por mês.

* **`CORREÇÃO`** - Controle do minutos no tronco.

* **`CORREÇÃO`** - Validação ao excluir tronco que é utilizado por alguma regra na origem.

* **`CORREÇÃO`** - Relatório de chamadas do período apresentava algumas chamadas atendidas como não atendidas.

* **`CORREÇÃO`** - Formatação do número de telefone.

* **`CORREÇÃO`** - Permissão de usuário para edição/duplicação de regras de negócio.

* **`CORREÇÃO`** - Melhorias de layout.

* **`CORREÇÃO`** - Ajuste para funcionamento do clicktocall.

* **`CORREÇÃO`** - Melhoria nas notificações do Snep.

* **`CORREÇÃO`** - Correção de erro quando gera relatórios com mudança de porta.

* **`CORREÇÃO`** - Adicionado nono dígito na expressão regular pra celular por default.

* **`CORREÇÃO`** - Não estava mostrando a informação do S.O quando o sistema é baseado em RedHat.

* **`CORREÇÃO`** - Tratamento quando ramal efetuava siga-me para ele próprio.

----
## 3.05 ##
*04 de dezembro de 2016*

* **`NOVO`** - Adicionado monitoramento dos membros para a opcao RINGINUSE funcionar corretamente (Contribuicao:  Willian Mazzardo - Sysvoip)

* **`NOVO`** - Escolha do Idioma na tela de Login

* **`CORREÇÃO`** - Problemas com menu no celular.

* **`CORREÇÃO`** - Valor do directmedia do ramal possuia valor 'nonat' ao invés de 'no'

* **`MELHORIA`** - snep-features.conf retirando chamadas de funções inexistentes(pauseQueueMember e unpauseQueueMember)

* **`CORREÇÃO`** - Layout do botão de login

* **`CORREÇÃO`** - Retirado funcionalidade de grupo de filas

* **`CORREÇÃO`** - Não mostrava valor inserido no campo "Usuários podem entrar na Fila mesmo sem Agentes presentes?" ao editar uma fila

* **`CORREÇÃO`** - Ajuste em tradução de opção de configuração da fila para pt_BR

* **`CORREÇÃO`** - Verifica se o peer SIP é um Tronco ou Ramal. Se for ramal, não adiciona a configuração fromuser , o que acaba dando problema de CallerID no destino, pois seta o cabeçalho SIP FROM igual ao TO . (Contribuicao:  Willian Mazzardo - Sysvoip)

* **`CORREÇÃO`** - Alguns ramais registram com ; depois do IP e outros com :, ajustado para exibir os dois (Contribuicao:  Lucas Peruchi)

* **`CORREÇÃO`** - Alteracao na Adicao e Edicao de Ramais, sem necessidade de ter o Ramal junto com o Nome (Contribuicao:  Lucas Peruchi)

* **`CORREÇÃO`** - Ajustado para mostrar que o Ramal nao term Permissao para Sigame no Log (Contribuicao:  Lucas Peruchi)

* **`CORREÇÃO`** - Alteracao na Adicao e Edicao de Ramais, sem necessidade de ter o Ramal junto com o Nome (Contribuicao:  Lucas Peruchi)

* **`CORREÇÃO`** - correção serviço de download dos arquivos de gravação via url direta: http://IP_SNEP/snep/arquivos/load.php?id=[USERFIELD]

* **`CORREÇÃO`** -  multiadd.phtml Editado o número mínimo de caracteres no campo do range de ramais a serem criados, passando de 8 para 6 (Contribuicao:  Willian Mazzardo - Sysvoip)

* **`CORREÇÃO`** - Ajuste na frase de exibicao do WHOAMI (Contribuicao:  Lucas Peruchi)

* **`CORREÇÃO`** - Ajuste para mostrar o ip na tela janela Status IP (Contribuicao:  Lucas Peruchi)

* **`CORREÇÃO`** - Ajustado o aviso: The 'username' field for sip peers has been deprecated in favor of the term 'defaultuser' (Contribuicao:  Lucas Peruchi)

* **`CORREÇÃO`** - Inserido o "IF NOT EXISTS" em duas tabelas, para não dar conflito se existir (Contribuicao:  Lucas Peruchi)

* **`CORREÇÃO`** - Ajustes strings de tradução diversas

* **`CORREÇÃO`** - Desabilitado realtime no arquivo extconfig.conf - SIP peers sao usados pelo Asterisk a partir dos arquivos snep-sip.conf e snep-sip-trunks.conf

* **`CORREÇÃO`** - Ajustes no layout e css do menu vertical

----
## 3.04 ##
*18 de agosto de 2016*

* **`NOVO`** Adicionado arquivos de som : en,es e pt-BR (removidos por engano na vesão 3.03)

* **`NOVO`** Adiconados arquivos de som pt_BR, en, es removidos por engano na versão 3.03

* **`NOVO`** - Troncos tipo SNEPSIP e SNEPIAX agora permite escolha de CODECS preferenciais

* **`NOVO`** - Utilização dos gráficos do google no relatório de chamadas do período

* **`NOVO`** - Nova lib bootstrap para selecionar mais de um item no campo select

* **`NOVO`** - Acrescimo de exceções especiais no vinculo do usuário

* **`MELHORIA`** - extensions.conf para corrigir Regex descontinuada do asterisk (de *. para _X) (Contribuição: Jean Eduardo - Datasolve Tecnologia)

* **`MELHORIA`** - extensions.conf para corrigir ordem de carregamento de .conf's do snep (Contribuição: Daian Conrad - Opens Tecnologia)

* **`MELHORIA`** - sip.conf para prevenir desconexão de ramais sip (Contribuição: Carlos Eduardo - Opens Tecnologia)

* **`MELHORIA`** - Melhorias no layout das listagens dos cadastros

* **`MELHORIA`** - Melhorias nas traduções

* **`CORREÇÃO`** - snep-features.conf para remover tentatva de carregamento do arquivo snep-agentes.conf (Contribuição: Anderson Freitas  Opens Tecnologia)

* **`CORREÇÃO`** - Consulta de hierarquia de centro de custos no relatorio de chamadas do período

* **`CORREÇÃO`** - Adicionado getTech e getHost para chamadas com troncos sem autenticacao ( Contribuição: Renato dos Santos)

----
## 3.03 ##
*30 de maio de 2016*


* **`NOVO`** - identificacao do modelo do aparelho autenticado (contribuição: Heros do Amaral)

* **`NOVO`** - controle de ramais ocupados quando fazem parte de uma fila

* **`NOVO`** - Parametrizacao do nome do arquivo de gravacao

* **`NOVO`** - Permitir portas diferentes da 80 para configurar servidores web (Apache lighttp, etc)

* **`MELHORIA`** - Filas de atendimento - novo campo para identificar status do ramal

* **`MELHORIA`** - Tela grupo de Captura - visualizar número de ramais em cada grupo

* **`CORREÇÃO`** - problema de identificação do chamador no display de telefones IP (Heros)

----
## 3.0, 3.01 e 3.02 ##

* **`NOVO`** - Webservice snep/arquivos/load.php - Busca por um arquivo de gravação a partir do ID

* **`NOVO`** - Adicionadas funcionalidades para Pausar/Tirar de Pausa os Ramais de Filas de Atendimento

* **`NOVO`** - Identificação visual para Esconder/Mostrar Regras desabilitadas em Regras de Negocio >> Rotas

* **`NOVO`** - Parametrizado (Configuracoes >> Parametros) o número de dígitos no Cadastro do Ramal

* **`NOVO`** - Filtros para separar Regras de Negócio em Entradas, Saidas, Outras

* **`MELHORIA`** - Dados gravados no campo 'name' da tabela peers - preparado para o módulo Billing

* **`MELHORIA`** - Ordem de exibição na lista de filas de atendimento : alfabetica do nome da fila

* **`MELHORIA`** - Strings de tradução pt_BR

* **`CORREÇÃO`** - Perda de autenticacao dos ramais SIP

* **`CORREÇÃO`** - Permitir multipla selecao de NAT para Troncos

* **`CORREÇÃO`** - Erros ao adiconar arquivos de Musicas de espera e removes seçoes de musica de espera

* **`CORREÇÃO`** - Erros de CSS em Rotas >> Configuração padrão

* **`CORREÇÃO`** - AGI DiscarRamal para usar o calleridname da origem ao invés de definir = ao numero


----
## Release - betha-rc3 #

* **`NOVO`** - Criada view para o menu de Informações

* **`NOVO`** - Cadastro usuário portal ITC agora permite qualquer caracter para a senha - compatibilizar com portal ITC

* **`NOVO`** - Status do Sistema -  Refatorada toda a rotina usando interações com AMI/Actions do Asterisk

* **`NOVO`** - Removido do Dashboard : Atalho para "buscar gravacoes" (record-report)

* **`NOVO`** - Ajustes em strings de tradução

* **`MELHORIA`** - Ajustes para remover diversos Warning e Notices do console do apache

* **`CORREÇÃO`** - Corrigir os ícones para enable/disable das permissões do usuário

* **`CORREÇÃO`** - Problema que exibia mensagem de erro abaixo da view em execução

* **`CORREÇÃO`** - Rever/redefinir conceitos/funcionalidades para Grupos de Ramais : + de 1 grupo por ramal

* **`CORREÇÃO`** - Erro na apresentacao do espaço usado em disco para HD's com mais de 1T

* **`CORREÇÃO`** - Erros na exibicao de status e latencia de troncos em Status >> Status IP

* **`CORREÇÃO`** - ERRO no Cadastro troncos que não obrigava a definir o tipo de tronco (peer,user,friend)

* **`CORREÇÃO`** - % de uso da CPU nao esta sendo exibido

* **`CORREÇÃO`** - Menu Usuário fica "sob" Status do sistema. Tem que ser "sobre"

----
## Release Betha-rc2 ##

* **`NOVO`** - Arquivos de som: Limpar a lista (fisica) de arquivos - muitos duplicados. (wav e gsm)

* **`NOVO`** - Arquivo de som - descricao dos arquivos no banco de dados - Sincronizado - descricões zeradas

* **`NOVO`** - Funcionalidade: Cadastros diversos com senha - opcao para mostrar senha

* **`MELHORIA`** - Contatos: Alterada base de leitura de cidades e estados, retirada a obrigatoriedade de cidade e estado do cadastro.

* **`MELHORIA`** - Ajustes na exibição do status do sistema / controle do temporizador para atualização do status

* **`CORREÇÃO`** - Ramais cadastrados como IAX sendo exibidos como SIP ao alterar

* **`CORREÇÃO`** - Salas de conferencias x Menu - problemas na visualização

* **`CORREÇÃO`** - Lista de ramais disponíveis no Grupo de Captura exibe referencias do Grupo de Ramal

* **`CORREÇÃO`** - comportamento do asterisk (AGI's) quando usa/altera Language (exige ajustes em extensions.conf e snep-features.conf)

* **`CORREÇÃO`** - Erro quando usa/habilita cadeado no ramal


----
## Release Betha-rc1 ##

* **`NOVO`** - Relatório de serviços: incluir cadeado, agenda, etc

* **`NOVO`** - Adicionado controle para exibição ou não de regras desabilitadas (Parâmetros)

* **`NOVO`** - Novas opções para NAT e DirectMedia em ramais/troncos SIP

* **`MELHORIA`** - Ajustes diversos no SQL inicial e dados iniciais

* **`MELHORIA`** - Ajustes para leitura do arquivo de áudio de acordo com language selecionada


* **`MELHORIA`** - Removidas bibliotecas e arquivos não utilizados

* **`MELHORIA`** - Inseridos comentários de LIcenca GPL em todos os arquivos .PHP

* **`MELHORIA`** - Renomeada pasta imagens para images

* **`MELHORIA`** - Ajustes nas Strings de tradução

* **`MELHORIA`** - A opção NAT pode ser combinada (checkbox). Não é única (radiobox).

* **`MELHORIA`** - Estacionamento - Ajustado: Não funciona como está documentado (700). Novo padrão: #72

* **`CORREÇÃO`** - Status do Sistema - Corrigido: não está exibindo o status dos troncos corretamente

* **`CORREÇÃO`** - Parâmetros: Não altera a variável "country-code"

* **`CORREÇÃO`** - Centro de Custos: descrição com 30 casas somente (aumentar) / Ver tabela BD

* **`CORREÇÃO`** - Grupo de Contatos:   Trunca o nome (view x tabela BD)

* **`CORREÇÃO`** - Musica de espera: Erro no Banco de dados, Erro ao gravar arquivo/criar sessão

* **`CORREÇÃO`** - Relatorio de chamadas:  não está paginando, css para ouvir gravações/fazer download está desalinhado

* **`CORREÇÃO`** - Regra de negócio padrão na instalação: Não cria ações para a Regra "Internas - Ramal a Ramal"

* **`CORREÇÃO`** - Asterisk: quando parado não dá mensagem de erro, a tela fica em branco somente (Ex. Cadastro Ramais, troncos, etc)

* **`CORREÇÃO`** - Ramais Cadastro: Usa grupo Ramal = Usuários mas exibe sempre como "Administradores"

* **`CORREÇÃO`** - Tronco SNEPSip:  não funciona

* **`CORREÇÃO`** - Cadeado: Ao incluir senha e marcar checkbox não funciona (ERRO de AGI). Ao alterar desmarcando o checkbox e deixando somente a senha, funciona normalmente

* **`CORREÇÃO`** - Filas: Não funciona os anuncios ao chamador (tipo: Você é o proximo a ser atendido....")

----
## Release Alpha ##

* **`NOVO`** - Nova interface

* **`NOVO`** - Conectado com a ITC

* **`NOVO`** - Novo sistema de controle de usuarios e permissões de acesso

* **`NOVO`** - Novo sistema de visualização de logs


* **`NOVO`** - Preparado para multi-idiomas

* **`NOVO`** - Novo sistema para atualização do CNL (Cadastro Nacional de Localidades / Anatel)

* **`MELHORIA`** - Padronização do código fonte (jQuery, Zend, Bootstrap, etc)
