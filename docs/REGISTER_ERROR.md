[![N|Solid](https://imgs.opens.com.br/docs/opens/img-snep-off.png)](https://snep.com.br)

# Problemas no SNEP Register

Se você está tendo problemas para registrar seu SNEP, talvez você possa fazê-lo manualmente.

Neste artigo vamos ver como passar o cadastro manualmente.

## Guia passo a passo

Obtendo suas chaves e credenciais:

- Obtendo seu SNEP UUID
```
mysql -usnep -psneppass snep -e "select uuid from itc_register"
```

- Com seu UUID, obtenha seu client_key e api_key na ITC API

```
curl https://api.opens.com.br/api/v1/auth/login -X POST -H "Content-type: application/json" -d '{"device_uuid":"YOUR_UUID","email":"YOUR_ITC_EMAIL","password":"YOUR_ITC_PASSWD"}'
```

**IMPORTANTE**:  Talvez você precise instalar **curl** pelo commando:  **apt-get install curl**(para sistemas Debian).

Este comando retornará assim:

```
{
    "code":200,
    "status":"Ok",
    "details":{
        "token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOjcyLCJleHAiOjE0ODM0NzI2ODI2NjN9.jTsUn8beWv3-t_F9eD0TddfWbBFK0bIk433Uk91hZr8",
        "id":72,
        "email":"douglas.conrad@opens.com.br",
        "first_name":"Douglas",
        "last_name":"Conrad","client_key":"$2a1$2H0$02AFrsCQp4jGqMIuwbLOy4bFrzWcsb/7wQPzupkBtEEB7agE7Zu","api_key":"ec087a5d-5606-4d4e-9daa-c4362c96523864dc7e12-d51d-4af1-a1dc-ab74a7688c0",
        "settings":[
            {
                "service":{
                    "id":2,
                "   name":"portability"
                },
                "key":"low_credit_amount",
                "value":"50","id":47
            },
            {
                "service":{
                    "id":2,
                    "name":"portability"
                },
                "key":"limited",
                "value":"true",
                "id":48
            }
        ]
    }
}
``` 

Aqui você tem os objetos: **client_key** e **api_key** que você precisa.

- Com seu UUID, CLIENT_KEY e API_KEY, salve as informações no banco de dados snep:

```
mysql -u snep -psneppass snep -e "update itc_register set client_key='YOUR_CLIENT_KEY' and api_key='YOUR_API_KEY' and registered_itc='1';"
```

```
mysql -usnep -psneppass snep -e "insert into itc_consumers (id_distro, id_service, name_service) values ('1', '1', 'intercomunexao');"
```

- Agora você precisa limpar o seu navegador "cookies e informações do site / plugins"
- Feito!