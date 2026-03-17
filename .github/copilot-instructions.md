# World Parliament Experiment (WPE) — Copilot Instructions

## Project Overview

A Symfony 5.4 web application implementing an internet voting platform for a world parliament. Users sign up, propose **Initiatives** organised by **Category**, cast **Votes** (directly or via **Delegation**), and track results through **Votings**. An AI assistant layer (Gemini API) moderates content and generates avatar images.

## Tech Stack

- **PHP 8.1**, **Symfony 5.4**
- **PostgreSQL** via Doctrine ORM (annotations, not PHP 8 attributes — see below)
- **Twig** templates
- **Symfony Messenger** (async via Doctrine transport) for emails, SMS, and notifications
- **JMS Serializer** for JSON API responses
- **KnpMenu** for navigation, **FOSJsRouting** for client-side route generation

## Development Environment

All commands run inside Docker:

```bash
docker-compose up -d --build
docker-compose exec web composer install
docker-compose exec web php bin/console doctrine:schema:create
docker-compose exec web php bin/console doctrine:fixtures:load --no-interaction
```

App available at `http://localhost:8080`. Default admin: `borchert` / `test`.

## Build, Test, and Lint Commands

```bash
# Run the full test suite (inside container)
docker-compose exec web php bin/phpunit

# Run a single test file
docker-compose exec web php bin/phpunit tests/Controller/DefaultControllerTest.php

# Run a single test method
docker-compose exec web php bin/phpunit --filter testMethodName tests/Controller/DefaultControllerTest.php

# Clear cache (required after config/translation changes)
docker-compose exec web php bin/console cache:clear
```

## Architecture

### Domain Model (`src/Entity/`)

| Entity | Role |
|---|---|
| `User` | Table `fos_user`; roles: `ROLE_USER`, `ROLE_MODERATOR`, `ROLE_ADMIN`, `ROLE_SUPERADMIN` |
| `Initiative` | A proposal users can vote on; belongs to a `Category` |
| `Category` | Groups initiatives (e.g. "United States", "Human rights") |
| `Voting` | A voting period attached to an `Initiative` |
| `Vote` | A single cast vote; subtypes: `DirectVoter`, `DelegatingVoter`, `NonVoter` |
| `Delegation` | A user delegating their vote to another user |
| `Group` | User groups for collective voting |
| `Comment` | Comments on initiatives |
| `Favourite` | User-bookmarked initiatives |

### Key Services (`src/Service/`)

- `VotingManager` — orchestrates vote casting and tallying
- `UserManager` / `UserInterface` — user lifecycle management
- `VoteEncryptionService` / `UserEncryptionService` — encrypt sensitive fields (custom Doctrine type `encrypted_string`)
- `AiAssistantService` — Gemini API integration for content moderation and avatar generation
- `SocialmediaPoster` — posts to Facebook/LinkedIn after initiative events
- `Mailer` — wraps Symfony Mailer with a configured sender address

### Console Commands (`src/Command/`)

- `wpe:scrape <country_code> <user> <category>` — scrapes and imports initiatives
  - Standard user: `borchert`
  - Flags: `--delete` (remove old initiatives in category), `--update` (run scrape)
  - Common categories: `Australia`, `Brazil`, `Canada`, `France`, `United Kingdom`, `Indonesia`, `Italy`, `Netherlands`, `Norway`, `Poland`, `Sweden`, `Thailand`, `United States`, `Human rights`, `Security and Conflict Resolution`
- `wpe:activate-votings` — activates scheduled votings
- `wpe:evaluate-votings` — evaluates and closes finished votings
- `wpe:encrypt-user-data` — migrates user data to encrypted fields
- `wpe:ai-*` — AI agent commands (action, manage, repair, avatar generation)
- `wpe:create-user`, `wpe:create-category`, `wpe:token-refresh`

### Controllers

- `BaseController` — extends `AbstractController`; adds JMS Serializer helpers (`createApiResponse`, `serialize`, `setSerializeGroups`). All API-returning controllers extend this.
- `Admin/` — CRUD controllers for `ROLE_SUPERADMIN`; moderator actions at `ROLE_MODERATOR`
- `VoteController` — handles vote submission via AJAX (returns JSON)
- `AiAssistantController` — chatbot endpoint

## Key Conventions

### ORM Mapping

Entities use **Doctrine annotation syntax** (docblock `@ORM\...`), **not** PHP 8 attribute syntax. The doctrine config sets `type: annotation`. Example:

```php
/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="fos_user")
 */
class User implements UserInterface { ... }
```

### Routing

Controllers use **PHP 8 `#[Route]` attributes** (discovered via `config/routes/annotations.yaml`). Legacy routes (login, logout, FOS JS routing) are in `config/routes.yaml`. Example:

```php
#[Route('/initiative/{id}', name: 'initiative_show', methods: ['GET'])]
public function show(Initiative $initiative): Response { ... }
```

### Service Registration

All classes in `src/` are autowired by default. Parameter bindings (SMS credentials, social media tokens, API keys, encryption secrets) are declared in `config/services.yaml` under `_defaults.bind` — inject them by matching the `$paramName` in constructors.

### Enums (`src/Enum/`)

Plain PHP classes with constants (not PHP 8.1 backed enums). Used for Initiative, Voting, Comment, Delegation, Favourite status values.

### Custom Doctrine Type

`encrypted_string` — transparently encrypts/decrypts string columns using `UserEncryptionService`. Declare it via `@ORM\Column(type="encrypted_string")`.

### Doctrine Extensions (Gedmo)

`stof/doctrine-extensions-bundle` is active. `Initiative` uses `@Gedmo\Loggable` for audit trails and `@Gedmo\Slug` for URL slugs. The `gedmo_loggable` mapping is registered in `doctrine.yaml`.

### Fixtures

Data fixtures use **nelmio/alice** (YAML fixture files) alongside `doctrine/doctrine-fixtures-bundle`:

```bash
docker-compose exec web php bin/console doctrine:fixtures:load --no-interaction
```

### JMS Serializer Groups

Controllers call `$this->setSerializeGroups(['group1', 'group2'])` before `createApiResponse()`. Entities use `@JMSSerializer\Groups({"default"})` annotations.

### Security Roles

```
ROLE_SUPERADMIN ⊃ ROLE_ADMIN ⊃ ROLE_MODERATOR ⊃ ROLE_USER
```

`switch_user` impersonation is available to `ROLE_SUPERADMIN`.

### Migrations

```bash
docker-compose exec web php bin/console doctrine:migrations:migrate
# Generate a new migration after entity changes:
docker-compose exec web php bin/console doctrine:migrations:diff
```

### Branch Naming

Branch from `develop`. Use `feature/[description]` or `bug/[description]`.
