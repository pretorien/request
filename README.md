# Readme

## Ajouter ces lignes dans la configuration de doctrine (doctrine.yaml) :

```yaml
    RequestBundle:
        is_bundle: true
        type: annotation
        dir: 'Entity'
        prefix: 'WTeam\RequestBundle\Entity'
        mapping: true
```

```yaml
    monolog:
        channels: ['db']
        handlers:
            db:
                channels: ['db']
                type: service
                id: monolog.db_handler
```

## La configuration du bundle est la suivante 

```yaml
request:
    proxy:
        nordvpn:
            username:             ~
            password:             ~
            api:                  'https://api.nordvpn.com/v1/servers/recommendations?filters\[servers_groups\]=5&filters\[servers_technologies\]=9&filters\[country_id\]=74'
    myip:
        uri:                  'https://api.myip.com' # Required
```