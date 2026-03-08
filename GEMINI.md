# Project Context: World Parliament (WPE)
This is a modern Symfony PHP project. Use this context for all code generation and architectural decisions.

## Tech Stack
- **Language:** PHP 8.2+ (Use strict types, constructor property promotion, and readonly properties).
- **Framework:** Symfony 6.4/7.0+
- **Database:** PostgreSQL (via Doctrine ORM).
- **Frontend:** Twig templates + AssetMapper (no Webpack Encore unless specified).

## Coding Patterns
- **Attributes:** Always use PHP Attributes for Routing, ORM Mapping, and Dependency Injection. Never use YAML or XML.
- **Service Layer:** Keep controllers "thin." Logic belongs in Services or Command Handlers (Messenger).
- **Type Safety:** Every function must have parameter types and return types.
- **DTOs:** Use readonly classes for Data Transfer Objects.

## Terminal Instructions
- Always run `php bin/console cache:clear` after changing configuration or translations.
- Use `php bin/console make:*` commands for generating boilerplate.
- **Scraper:** Use `php bin/console wpe:scrape <country_code> <user> <category>`. 
  - Standard user for scraping: `borchert`.
  - Common categories: `Australia`, `Brazil`, `Canada`, `France`, `United Kingdom`, `Indonesia`, `Italy`, `Netherlands`, `Norway`, `Poland`, `Sweden`, `Thailand`, `United States`, `Human rights`, `Security and Conflict Resolution`.
  - Flags: `--delete` (removes old initiatives in category) and `--update` (runs the script).

## "Vibe Coding" Rules
- Be concise. Don't explain basic PHP concepts unless asked.
- If you need to see a service definition, use the `list_directory` tool to find the `src/Service` folder.
- **Important:** If I ask for a refactor, prioritize readability and Symfony "best practices."