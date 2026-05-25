# Sistema de Versionamento e Atualização

## Arquivos Importantes

| Arquivo | Função |
|---|---|
| `version.php` | Contém a versão atual instalada (`APP_VERSION`) |
| `update_config.php` | Token e dados do repositório GitHub |
| `app/Controllers/UpdateController.php` | Lógica de verificação e atualização |

---

## Regras para a IA (Antigravity/Gemini)

### Quando atualizar o `version.php`:
- **Sempre** que fizer alterações significativas no código (novos recursos, correções, mudanças de comportamento)
- **Incrementar** a versão seguindo o padrão semântico:
  - `X.0.0` → Mudança grande (nova funcionalidade principal)
  - `0.X.0` → Mudança média (recurso novo ou alteração importante)
  - `0.0.X` → Correção pequena (bugfix)
- **Exemplo:** se a versão atual é `1.0.0` e foi feita uma correção de bug, mudar para `1.0.1`

### Como atualizar:
Editar o arquivo `version.php` na raiz do projeto:
```php
define('APP_VERSION', '1.1.0'); // atualizar este número
```

### O que a IA NÃO faz:
- Criar Releases no GitHub (o dono do projeto faz isso manualmente)

---

## Fluxo de Atualização Completo

1. **IA edita o código** e atualiza o `version.php`
2. **Dono sobe os arquivos** para o GitHub
3. **Dono cria uma Release** no GitHub com a tag correspondente (ex: `v1.1.0`)
4. **Clientes** clicam "Verificar Agora" no painel → veem a nova versão → clicam "Atualizar"

---

## Como criar uma Release no GitHub

1. Acesse: `https://github.com/guiilherm94/membros-metodogo/releases/new`
2. **Choose a tag** → digitar `v1.1.0` → "Create new tag"
3. **Release title** → descrever o que mudou
4. **Publish release**

---

## Arquivos protegidos (nunca sobrescritos na atualização)
- `.env`
- `uploads/`
- `public/uploads/`
- `storage/`
- `update_config.php`
