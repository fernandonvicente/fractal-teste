# API de autenticação — Laravel 13

API REST com Laravel 13, SQLite e Laravel Sanctum. O Sanctum emite tokens pessoais usados como Bearer tokens nas rotas privadas.

## Estrutura implementada

- **Migrações** em `database/migrations/` criam as tabelas de usuários, cache, filas e tokens pessoais do Sanctum. A migração incremental renomeia `name` para `nome` e define `nome` e `email` como `TEXT`; o e-mail possui índice único.
- **Modelo** `app/Models/User.php` habilita tokens Sanctum, restringe atribuição em massa a `nome`, `email` e `password`, oculta senha e token de lembrança e aplica o cast de hash à senha.
- **Rotas** em `routes/api.php`: `POST /api/register`, `POST /api/login`, `GET /api/me`, `POST /api/logout` e `POST /api/logout-all`.
- **Autenticação e validação** em `app/Http/Controllers/Api/AuthController.php`. As rotas privadas usam `auth:sanctum`; o login usa `throttle:login`, configurado em `app/Providers/AppServiceProvider.php`.
- **Testes automatizados** em `tests/Feature/ApiAuthenticationTest.php`, executados com PHPUnit via `php artisan test`.

## Requisitos

- PHP 8.3 ou superior e extensões PDO/SQLite, OpenSSL, Mbstring e XML.
- Composer 2.

## Instalação e configuração

Execute os comandos a seguir na raiz do projeto (a pasta que contém `artisan`):

```bash
composer install
```

O `.env.example` contém apenas valores de configuração de exemplo, sem credenciais reais. Copie-o para criar sua configuração local. O `.env` e o arquivo SQLite são locais e não devem ser publicados no Git. Em uma cópia nova do projeto:

```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
```

Confirme no `.env` que o banco está configurado para SQLite:

```dotenv
DB_CONNECTION=sqlite
```

Defina uma chave de aplicação própria com `php artisan key:generate`. Não publique o `.env`, chaves ou credenciais.

Prepare o esquema do banco:

```bash
php artisan migrate
```

## Executar

```bash
php artisan serve
```

A API ficará disponível em `http://127.0.0.1:8000`. Execute os testes automatizados com:

```bash
php artisan test
```

## Rotas

Todas as respostas são JSON. Erros de validação retornam `422`; credenciais inválidas e tokens ausentes, inválidos ou revogados retornam `401`. O login aceita até cinco tentativas por minuto por endereço IP; o limite retorna `429`.

### Cadastro — `POST /api/register`

```bash
curl -X POST http://127.0.0.1:8000/api/register \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
    "nome": "Fulano da Silva",
    "email": "fulano@gmail.com",
    "password": "EDW999#senha",
    "password_confirmation": "EDW999#senha"
  }'
```

O cadastro retorna `201`, os dados públicos do usuário e um token. O nome da rota é `register` (em vez de `resgister`).

### Login — `POST /api/login`

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
    "email": "fulano@gmail.com",
    "password": "EDW999#senha"
  }'
```

Exemplo resumido de resposta:

```json
{
  "message": "Login realizado com sucesso.",
  "user": {
    "id": 1,
    "nome": "Fulano da Silva",
    "email": "fulano@gmail.com"
  },
  "token": "1|seu-token",
  "token_type": "Bearer"
}
```

### Consultar usuário autenticado — `GET /api/me`

Use o valor de `token` recebido no cadastro ou login:

```bash
curl http://127.0.0.1:8000/api/me \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer SEU_TOKEN'
```

### Revogar token atual — `POST /api/logout`

```bash
curl -X POST http://127.0.0.1:8000/api/logout \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer SEU_TOKEN'
```

O token revogado não pode ser reutilizado.

### Revogar todos os tokens — `POST /api/logout-all`

```bash
curl -X POST http://127.0.0.1:8000/api/logout-all \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer SEU_TOKEN'
```

Esta rota invalida todos os tokens Sanctum do usuário, inclusive o token usado para chamá-la.

## Banco de dados

O arquivo local é `database/database.sqlite`. Para recriar o esquema e apagar os dados locais:

```bash
php artisan migrate:fresh
```

Não execute esse comando em um banco que contenha dados que você queira preservar.

As migrações versionadas ficam em `database/migrations/`. Em uma instalação nova, `php artisan migrate` cria o esquema completo, incluindo a tabela `personal_access_tokens` utilizada pelo Sanctum. A tabela `users` contém `nome` e `email` como texto, e o e-mail tem restrição de unicidade.

## Testes automatizados

Os testes PHPUnit cobrem cadastro válido e duplicado, campos ausentes ou inválidos, login válido e inválido, proteção com token ausente ou inválido, acesso autenticado, revogação e reutilização de tokens e limite de tentativas de login:

```bash
php artisan test
```

## Plano

Consulte [PLANO-DE-DESENVOLVIMENTO.md](./PLANO-DE-DESENVOLVIMENTO.md) para as etapas implementadas e as decisões de segurança.
