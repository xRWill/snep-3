[![N|Solid](https://imgs.opens.com.br/docs/opens/img-snep-off.png)](https://snep.com.br)

# Processo de tradução do Snep

* Processo para incluir novas strings de traducao no arquivo messages.
* Processo para gerar/atualizar o arquivo .mo da lingua desejada
* Desenvolvimento Opens - `desenvolvimento@opens.com.br`

### Procedimentos

1) É necessário a instalação do gettext no ambiente.
```sh
$ apt-get install gettext
```

2) Acesse o diretório bin da instalação do Snep;
```sh
$ cd /var/www/html/snep/bin
```

3) Execute o aplicativo "gettext.sh"
```sh
$ bash gettext.sh ../
```

4) Verifique se no diretório foi criado/atualizado o arquivo: **messages.po**. Este é o arquivo base para as traduções.
* O arquivo **pt_BR.po** é o arquivo para tradução das palavras para o português
* O arquivo **es.po** é o arquivo para traduções para o Espanhol

5) Execute com o Poedit o arquivo da língua que você deseja realizar a tradução.
Após abrir o programa, vá no menu `Catálogo >> Atualizar` pelo arquivo POT
Irá abrir uma caixa para seleção de um arquivo, então selecione o arquivo **messages.po**

6) Procure a palavra que deseja realizar a tradução, selecione e preencha sua tradução na segunda caixa de texto. Após realizar todas as alterações desejadas, salve as mudanças do arquivo.

7) Copie os arquivos `messages.mo`, `pt_BR.mo` e `es.mo` para o diretório `/var/www/html/snep/lang/`

8) Renomeie o arquivo `messages.mo` para `en.mo`;

9) Verifique suas alterações na Interface!