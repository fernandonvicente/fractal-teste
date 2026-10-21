# Plano de desenvolvimento da API

## Objetivo

Criar uma API REST com Laravel 13, SQLite e Laravel Sanctum para cadastro e autenticação de usuários por Bearer token.

## Etapas

1. **Preparar o projeto e o banco**
   - [x] Instalar Laravel 13 e Sanctum.
   - [x] Configurar o SQLite local e a tabela de tokens do Sanctum.
2. **Implementar autenticação**
   - [x] Cadastro com validação de nome, e-mail único e confirmação da senha.
   - [x] Hash seguro de senha e respostas sem dados sensíveis.
   - [x] Login com mensagem genérica para credenciais inválidas e limite de tentativas.
   - [x] Rotas privadas para consultar o usuário, revogar o token atual ou revogar todos os tokens.
3. **Validar comportamento**
   - [x] Testar cadastro, login, validações, proteção das rotas e revogação de tokens com PHPUnit.
4. **Documentar e executar**
   - [x] Documentar configuração, execução, rotas e exemplos no README.

## Rotas previstas

| Método | Rota | Acesso | Descrição |
| --- | --- | --- | --- |
| POST | `/api/register` | Público | Cadastra usuário e devolve token |
| POST | `/api/login` | Público, limitado | Valida credenciais e devolve token |
| GET | `/api/me` | Bearer token | Devolve os dados públicos do usuário |
| POST | `/api/logout` | Bearer token | Revoga o token usado na requisição |
| POST | `/api/logout-all` | Bearer token | Revoga todos os tokens do usuário |

## Decisões de segurança

- Senhas são armazenadas com o hash configurado pelo Laravel.
- Respostas de usuário incluem apenas `id`, `nome` e `email`.
- Credenciais inválidas retornam a mesma resposta para e-mail inexistente e senha incorreta.
- Rotas privadas usam o middleware `auth:sanctum`.
- O login permite até cinco tentativas por minuto por endereço IP.
- O Eloquent e os parâmetros validados são usados para acesso ao banco; o modelo limita os campos atribuíveis.
