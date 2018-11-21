[![N|Solid](https://imgs.opens.com.br/docs/opens/img-snep-off.png)](https://snep.com.br)


A partir da versão 3 do SNEP, as configurações de ramais no Asterisk foram migradas para o Realtime, o que quer dizer que o asterisk não busca mais as configurações de ramais no arquivo **/etc/asterisk/snep/snep-sip.conf** e sim diretamente no banco.

Porém foi detectado um problema no Stack SIP do asterisk para realtime peers na série 13.

O problema causa um travamento de todo o Stack SIP de vez em quando em diferentes cenários.

Se você estiver enfrentando este problema a solução é desabilitar o Realtime e voltar a configuração de ramais para os arquivos de configuração.

Para isso você deverá aplicar um patch no SNEP caso esteja usando alguma versão até a 3.04, e também mudar as configurações do asterisk quanto ao realtime.

A seguir um passo à passo para fazer isso.

## Guia passo à passo

### Patch do SNEP
```
wget -c http://pacotes.opens.com.br/packages/patch-realtime.tar.gz
tar xzf patch-realtime.tar.gz -C /var/www/html/snep/
```

Para completar este passo, edite qualquer Ramal no SNEP e salve.

###Desabilitando o Realtime no Asterisk
```
cd /var/www/html/snep/scripts
./disable_realtime.sh
asterisk -rx "module reload extconfig"
```

Feito.