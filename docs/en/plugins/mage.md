Plugin Mage
===========

Triggers a deployment of the project to run via [Mage](https://github.com/andres-montanez/Magallanes)

Configuration
-------------

### Options

* **env** [required, string] - The environment name

### Examples

```yaml
deploy:
    mage:
        env: production
```

### Options for config.yml

* **bin** [optional, string] - The mage executable path

### Examples

```yaml
mage:
    bin: /usr/local/bin/mage
```
