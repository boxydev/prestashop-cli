# Prestashop CLI

We like tooling in Symfony or modern PHP framework who can improve DX.
With this tool, maybe we can retrieve the same DX in Prestashop like Symfony...

## Usage

You need to enable PHP Zip extension.
Tool is in development at the moment but we can launch the first command :

```
php bin/console prestashop:install
```

## TODO

- Make prestashop:install with awesome options like : Choose version, choose installation location, ask for a database host, username, password for install prestashop with native CLI.
- Configure workflow (CodeSniffer, Coverage) and tests with Travis because we want to use this tool on REAL projects and maybe for the production.
