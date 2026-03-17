# World Parliament Experiment (WPE) - Copilot Instructions

## Project Overview

**The World Parliament Experiment (WPE)** is a Symfony-based web application for participatory democracy and global voting. It allows users to create initiatives, vote on proposals, delegate voting rights, and engage in democratic processes with features for comments, categories, and various voting mechanisms.

**Tech Stack:**
- **Language:** PHP 8.1+ (strict types, modern PHP features)
- **Framework:** Symfony 5.4.* LTS
- **Database:** PostgreSQL 15+ (via Doctrine ORM)
- **Frontend:** Twig templates with AssetMapper (no Webpack)
- **Key Bundles:** FOS User Bundle, FOS CKEditor, JMS Serializer, Doctrine Extensions, KnP Menu, APY Breadcrumb Trail

**Repository:** https://github.com/world-parliament-experiment/WPE

---

## 1. Build, Test & Lint Commands

### Setup & Installation

```bash
# Docker-based quick start (recommended)
docker-compose up -d --build
docker-compose exec web composer install
docker-compose exec web php bin/console doctrine:schema:create
docker-compose exec web php bin/console doctrine:fixtures:load --no-interaction

# Non-Docker setup
composer install
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load
```

### Key Commands

**Cache clearing** (must run after config/translation changes):
```bash
php bin/console cache:clear
```

**Database Migrations:**
```bash
php bin/console doctrine:migrations:migrate
php bin/console doctrine:migrations:diff
```

**Tests:**
```bash
# Run all tests
vendor/bin/phpunit

# Run a single test file
vendor/bin/phpunit tests/Controller/DefaultControllerTest.php

# Run a specific test method
vendor/bin/phpunit tests/Controller/DefaultControllerTest.php --filter testIndexAction

# Run with code coverage
vendor/bin/phpunit --coverage-html var/coverage
```

**Maker Bundle** (generate boilerplate):
```bash
php bin/console make:entity
php bin/console make:form
php bin/console make:controller
php bin/console make:command
```

**Custom Commands:**
```bash
# Scraper command (for importing initiatives from various countries)
php bin/console wpe:scrape <country_code> <user> <category> [--delete] [--update]
# Example: php bin/console wpe:scrape "United States" borchert "Human rights" --update

# Standard countries/categories: Australia, Brazil, Canada, France, UK, Indonesia, Italy, 
# Netherlands, Norway, Poland, Sweden, Thailand, US, Human rights, Security and Conflict Resolution

# AI-related commands
php bin/console wpe:ai-agent:manage [start|stop|status]
php bin/console wpe:ai-agent:action
php bin/console wpe:ai-avatar:generate

# Voting commands
php bin/console wpe:activate-votings
php bin/console wpe:evaluate-votings

# User management
php bin/console wpe:create-user <username> <email> [--admin]
php bin/console wpe:create-category <name> <description>
php bin/console wpe:encrypt-user-data  # Encrypt user personal data
```

### Docker Compose Commands

**Access the web container:**
```bash
docker-compose exec web bash
```

**View logs:**
```bash
docker-compose logs -f web
docker-compose logs -f database
```

**Stop services:**
```bash
docker-compose down
```

**Rebuild:**
```bash
docker-compose up -d --build
```

---

## 2. Architecture Overview

### What This Application Does

The WPE is a **participatory democracy platform** that enables:
- **Initiative Management:** Users create proposals in categories (e.g., "Human rights", "Security")
- **Voting System:** Support for direct voting and delegated voting (users can delegate votes to others)
- **Democracy Features:**
  - Direct voters submit votes on initiatives
  - Delegating voters can transfer voting power
  - Non-voters opt out
  - Vote encryption for privacy
- **Social Features:** Comments, favorites, user profiles, delegations
- **Admin Features:** Manage categories, initiatives, users, comments
- **AI Integration:** AI assistants for generating summaries and suggestions
- **Data Import:** Web scraper for importing initiatives from parliament websites

### Key Domain Models (Entities)

Located in `src/Entity/`:

| Entity | Purpose |
|--------|---------|
| **User** | FOSUserBundle-extended user with roles, country, encryption keys |
| **Initiative** | Proposal/bill with title, description, category, status (draft/active/finished/closed/deleted) |
| **Category** | Grouping for initiatives (e.g., "Health", "Climate") |
| **Voting** | Election/voting period for a specific initiative with start/end dates |
| **Vote** | Individual vote (value: -1/abstain/+1, encrypted if needed) |
| **Voter** | Abstract voter base; concrete implementations: DirectVoter, DelegatingVoter, NonVoter |
| **Delegation** | User A delegates voting power to User B for a specific category/initiative |
| **Comment** | Comments on initiatives with nested replies and reactions (liked/disliked/reported) |
| **Favourite** | User's favorite initiatives for quick access |
| **UserImage** | User profile/avatar images |
| **Group** | Groups of users for organizational purposes |

**Key Relationships:**
- `Initiative` ← `Category` (ManyToOne)
- `Initiative` ← `Voting` (OneToMany, voting periods)
- `Vote` ← `Voting` (ManyToOne)
- `Comment` ← `Initiative` (ManyToOne, can be nested)
- `Delegation` → User (ManyToOne voter delegate)
- `DirectVoter` / `DelegatingVoter` / `NonVoter` extend `Voter`

### Key Services

Located in `src/Service/`:

| Service | Purpose |
|---------|---------|
| **VotingManager** | Core voting logic: tally votes, check delegations for cycles, calculate results |
| **UserManager** | User creation, password reset, profile updates |
| **UserEncryptionService** | Encrypt/decrypt user PII (name, email) with AES |
| **VoteEncryptionService** | Encrypt/decrypt vote values for privacy |
| **Mailer** | Send emails (reset password, OTP, notifications) |
| **SendOtpVerificationService** | One-time password generation/validation for phone verification |
| **AvatarManager** | Upload/manage user profile images |
| **SocialmediaPoster** | Post initiative updates to Facebook, LinkedIn |
| **AiAssistantService** | Call Google Gemini API for AI-generated summaries/suggestions |

### Controllers & Routing

Located in `src/Controller/`:

**Main Controllers:**
- **DefaultController** (`/`) — Homepage, legal notices
- **CategoryController** (`/category/*`) — Browse categories, list initiatives
- **VoteController** (`/initiative/*`) — Vote, comment, delegation logic
- **UserController** (`/user/*`) — User profiles, favorites
- **ProfileController** (`/profile/*`) — User profile management
- **RegistrationController** — User registration
- **SecurityController** — Login/logout
- **SearchController** — Search initiatives by keyword
- **ChangePasswordController** — Password change
- **OtpVerificationController** — SMS-based OTP verification
- **AiAssistantController** — AI helper endpoints
- **WidgetController** — Embeddable widgets
- **Admin Controllers** (`/admin/*`)
  - `CategoryAdminController` — Manage categories
  - `InitiativeAdminController` — Manage initiatives
  - `UserAdminController` — Manage users
  - `CommentAdminController` — Moderate comments

**Routing Strategy:** PHP Attributes (Annotations)
```php
#[Route('/path', name: 'route_name', methods: ['GET', 'POST'])]
public function actionName(Request $request): Response
```

Example from `DefaultController`:
```php
#[Route("/", name="homepage")]
public function indexAction(Request $request): Response
```

Example from `VoteController`:
```php
#[Route("/initiative")]
class VoteController extends BaseController
{
    #[Route("/reply/{type}/{id}", methods: ["POST"], requirements: ["type" => "initiative|comment", "id" => "\d+"], name: "initiative_save_reply")]
    public function saveReplyAction(Request $request, $type, $id): Response
```

### Console Commands

Located in `src/Command/`:

| Command | Purpose |
|---------|---------|
| **ScraperCommand** (`wpe:scrape`) | Import initiatives from parliament websites; supports multiple countries |
| **CreateUserCommand** (`wpe:create-user`) | CLI user creation with optional admin role |
| **CreateCategoryCommand** (`wpe:create-category`) | Create initiative categories |
| **ActivateVotingsCommand** (`wpe:activate-votings`) | Activate scheduled votings at correct time |
| **EvaluateVotingsCommand** (`wpe:evaluate-votings`) | Calculate voting results when votings end |
| **EncryptUserDataCommand** (`wpe:encrypt-user-data`) | Bulk encrypt user personal data |
| **AiAgentManageCommand** (`wpe:ai-agent:manage`) | Start/stop AI assistant agent |
| **AiAgentActionCommand** (`wpe:ai-agent:action`) | Execute AI actions (summary generation, etc.) |
| **AiAvatarGenerateCommand** (`wpe:ai-avatar:generate`) | Generate AI avatars via Gemini |
| **TokenRefreshCommand** (`wpe:token-refresh`) | Refresh API tokens |

### No Messenger/Async Patterns Found

The project **does not currently use Symfony Messenger for async processing**. The `doctrine-messenger` transport is configured in `.env.dev` but appears unused. All processing is synchronous, handled directly by services and command handlers.

---

## 3. Key Conventions & Patterns

### Routing Definition

**Convention:** PHP Attributes (not YAML)
```php
// In controller classes
use Symfony\Component\Routing\Annotation\Route;

#[Route('/path', name: 'route_name', methods: ['GET', 'POST'])]
public function actionName(): Response
```

**Breadcrumb Integration:**
```php
use APY\BreadcrumbTrailBundle\Annotation\Breadcrumb;

#[Breadcrumb('label', attributes: ['translate' => true])]
#[Route('/path')]
public function action(): Response
```

**Route configuration file:** `config/routes/annotations.yaml` (imports controllers with `type: annotation`)

### Database Configuration

**Location:** `config/packages/doctrine.yaml` and `.env` files

**Database URL format:**
```
DATABASE_URL="postgresql://user:password@host:5432/database?serverVersion=15&charset=utf8"
```

**Environment files:**
- `.env.dev` — Development (PostgreSQL in Docker)
- `.env.test` — Test environment configuration
- `.env` — Default (you must create locally)

**Docker setup:**
- PostgreSQL 15 Alpine runs in separate container
- Database credentials configurable via `.env`
- Schema creation: `php bin/console doctrine:schema:create`
- Migrations: `php bin/console doctrine:migrations:migrate`

### Base Classes & Traits

**BaseController:**
- **File:** `src/Controller/BaseController.php`
- **Purpose:** Extends Symfony's AbstractController with custom methods
- **Features:**
  - JMS Serializer integration (`$serializer`, `$_serializeGroups`)
  - Doctrine Manager access (`$managerRegistry`)
  - API response helpers: `respondForbiddenError()`, `respondNotFoundError()`, `createApiResponse()`
  - All custom controllers inherit from this

```php
class YourController extends BaseController
{
    public function __construct(SerializerInterface $serializer, ManagerRegistry $managerRegistry)
    {
        parent::__construct($serializer, $managerRegistry);
        $this->_serializeGroups = ["simple"]; // or ["default"]
    }
}
```

**Security Voter Pattern:**
- **File:** `src/Security/InitiativeVoter.php`
- **Pattern:** Uses Symfony's Voter interface for fine-grained access control
- **Attributes:** VIEW, EDIT, DELETE, PUBLISH, CLOSE, VOTE
- Usage: `$this->denyAccessUnlessGranted('view', $initiative)`

### Template Organization

**Location:** `templates/`

**Structure:**
```
templates/
├── base.html.twig          # Main layout with header, footer, menu
├── footer.html.twig        # Footer include
├── default/                # Homepage and landing pages
├── Category/               # Category listing
├── Vote/                   # Voting interface
├── User/                   # User profiles
├── Profile/                # Profile management
├── Admin/                  # Admin interface
├── FOSUserBundle/          # User bundle overrides
├── security/               # Login/logout
├── registration/           # Registration form
├── Helpers/                # Reusable template components
├── Menu/                   # Menu rendering
├── Delegation/             # Delegation UI
├── ChangePassword/         # Password change form
└── Widget/                 # Widget components
```

**Twig Extensions** (`src/Twig/`):
- `PageExtension.php` — Custom page helpers
- `WidgetExtension.php` — Widget rendering
- `TimeDiffExtension.php` — Time difference formatting
- `TextExtension.php` — Text manipulation filters

### Service Registration & Dependency Injection

**Convention:** Autowiring with autoconfigure

**Location:** `config/services.yaml`

**Key configurations:**
```yaml
_defaults:
  autowire: true         # Auto-inject dependencies
  autoconfigure: true    # Auto-register services (commands, event listeners, etc.)
  bind:                  # Environment variable bindings
    $apiKey: '%env(GEMINI_API_KEY)%'
    $userEncryptSecret: '%env(USER_ENCRYPT_SECRET)%'

App\:
  resource: '../src/'
  exclude:
    - '../src/DependencyInjection/'
    - '../src/Entity/'
    - '../src/Kernel.php'

App\Service\:
  resource: '../src/Service/'
  autowire: true

App\Command\:
  resource: '../src/Command/'
  autowire: true
```

**Custom service definitions:**
```yaml
App\Service\Mailer:
  class: App\Service\Mailer
  arguments:
    $senderEmail: '%env(SENDER_EMAIL)%'
  autowire: true
```

**Usage in controllers/services:**
```php
public function __construct(VotingManager $votingManager, Mailer $mailer)
{
    $this->votingManager = $votingManager;
    $this->mailer = $mailer;
}
```

### Forms & Validation

**Convention:** Symfony Form Types (not Symfony 6 form builder)

**Example from `src/Form/CommentForm.php`:**
```php
class CommentForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('message', CKEditorType::class, [
                'label' => 'comment.edit.message',
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'config' => ['uiColor' => '#ffffff']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => Comment::class]);
    }

    public function getBlockPrefix(): string
    {
        return 'app_bundle_comment_form';
    }
}
```

**CKEditor Integration:** FOS CKEditor Bundle for rich text editing in comments/initiatives

### Enums & Constants

**Convention:** Abstract classes with static constants (PHP 7.4/8.0 style, not native enums)

**Example from `src/Enum/InitiativeEnum.php`:**
```php
abstract class InitiativeEnum
{
    const TYPE_FUTURE = 0;
    const TYPE_CURRENT = 1;
    const TYPE_PAST = 2;
    const TYPE_PROGRAM = 3;

    const STATE_DRAFT = 0;
    const STATE_ACTIVE = 1;
    const STATE_FINISHED = 2;
    const STATE_CLOSED = 3;
    const STATE_DELETED = 4;

    protected static $typeName = [
        self::TYPE_FUTURE => "future",
        // ...
    ];

    public static function getTypeName($type): string
    {
        return self::$typeName[$type] ?? 'unknown';
    }
}
```

**Other Enums:** `VotingEnum`, `CommentEnum`, `DelegationEnum`, `CategoryEnum`, `FavouriteEnum`

### Entity Mapping

**Convention:** Doctrine Annotations (not attributes)

**Example from `src/Entity/Initiative.php`:**
```php
/**
 * @ORM\Entity(repositoryClass="App\Repository\InitiativeRepository")
 * @ORM\Table(name="initiative")
 * @JMSSerializer\ExclusionPolicy("all")
 * @Gedmo\Loggable()
 */
class Initiative
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMSSerializer\Expose
     * @JMSSerializer\Groups({"default", "simple"})
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="initiatives")
     * @ORM\JoinColumn(nullable=false)
     * @JMSSerializer\Expose
     * @JMSSerializer\Type("App\Entity\Category")
     */
    protected $category;
}
```

**Key patterns:**
- Doctrine ORM annotations for mapping
- JMS Serializer annotations for API serialization (groups: "default", "simple")
- Gedmo Loggable for audit trail
- Symfony Validator annotations for validation rules

### JMS Serializer Configuration

**Groups:** 
- `"default"` — Full data set (more fields)
- `"simple"` — Minimal data set (fewer fields for performance)

**Usage in controllers:**
```php
class BaseController extends AbstractController
{
    public $_serializeGroups = ["simple"];
    
    protected function createApiResponse($data, $statusCode = 200)
    {
        $json = $this->serializer->serialize($data, 'json', 
            SerializationContext::create()->setGroups($this->_serializeGroups)
        );
        return new JsonResponse($json, $statusCode, [], true);
    }
}
```

### Existing AI Instructions

**File:** `GEMINI.md` (already exists)

This project has **explicit AI coding guidelines** in `GEMINI.md`:
- Use PHP 8.2+ with strict types and modern features
- Always use PHP Attributes (not YAML) for routing, ORM, DI
- Keep controllers thin; logic in Services or Command Handlers
- Use readonly DTO classes
- Always include parameter and return types
- For configuration changes, run `php bin/console cache:clear`
- Scraper command syntax: `php bin/console wpe:scrape <country_code> <user> <category> [--flags]`

**Check `GEMINI.md` before requesting code generation to understand the "vibe".**

---

## 4. Project Structure Reference

### File Organization

```
/mnt/c/Users/dkRobSel/world-parliament/WPE/
├── src/
│   ├── Annotation/              # Custom attributes/annotations
│   ├── Command/                 # Console commands
│   ├── Controller/              # HTTP controllers
│   │   └── Admin/              # Admin controllers
│   ├── DataFixtures/            # Test data (Alice/Faker)
│   ├── DependencyInjection/     # Custom DI configuration
│   ├── Doctrine/                # Doctrine event listeners/subscribers
│   ├── Entity/                  # Domain models
│   ├── Enum/                    # Constant enumerations
│   ├── EventListener/           # Doctrine/Symfony event listeners
│   ├── EventSubscriber/         # Symfony event subscribers
│   ├── Form/                    # Symfony form types
│   ├── Menu/                    # KnP menu builder
│   ├── Repository/              # Doctrine repositories
│   ├── Security/                # Voters, security helpers
│   ├── Service/                 # Business logic services
│   ├── Twig/                    # Twig extensions
│   ├── Util/                    # Utility classes
│   └── Kernel.php               # Symfony kernel
├── config/
│   ├── services.yaml            # Service definitions
│   ├── routes.yaml              # Route configuration (minimal)
│   ├── routes/
│   │   └── annotations.yaml    # Controller routing via attributes
│   ├── packages/                # Bundle configuration
│   │   ├── doctrine.yaml
│   │   ├── security.yaml
│   │   ├── messenger.yaml
│   │   ├── jms_serializer.yaml
│   │   └── ... (other bundles)
│   ├── bundles.php              # Bundle registration
│   ├── preload.php
│   └── ai_personas.json         # AI assistant definitions
├── templates/                   # Twig templates
├── migrations/                  # Doctrine migrations
├── public/                      # Web root
│   ├── index.php
│   └── assets/                  # JavaScript, CSS, images
├── tests/                       # PHPUnit tests
├── var/                         # Cache, logs, uploads
├── bin/
│   └── console                  # Symfony console entry point
├── docker/                      # Docker configuration
├── docker-compose.yml           # Docker Compose setup
├── docker-compose.override.yml  # Local overrides
├── phpunit.xml.dist             # PHPUnit configuration
├── composer.json                # PHP dependencies
├── composer.lock
├── symfony.lock                 # Symfony version lock
├── README.md                    # Main documentation
├── GEMINI.md                    # AI coding guidelines
├── contributing.md              # Contribution guidelines
├── .env.dev                     # Development environment
├── .env.test                    # Test environment
└── .gitignore
```

### Key Configuration Files

**`config/services.yaml`** — Service definitions and dependency injection bindings
**`config/packages/doctrine.yaml`** — Database configuration, migrations
**`config/packages/security.yaml`** — Authentication, authorization, voters
**`config/packages/jms_serializer.yaml`** — Serialization groups and metadata
**`config/packages/messenger.yaml`** — Message bus (currently unused)
**`src/Kernel.php`** — Minimal kernel with MicroKernelTrait

### Testing

**Location:** `tests/Controller/`

**Bootstrap:** `tests/bootstrap.php`

**Configuration:** `phpunit.xml.dist`

**Test environment:** `.env.test` with `APP_ENV=test` and `KERNEL_CLASS='App\Kernel'`

**Running tests:**
```bash
vendor/bin/phpunit
vendor/bin/phpunit --filter=testMethodName
vendor/bin/phpunit tests/Controller/DefaultControllerTest.php
```

---

## 5. Common Development Workflow

### Creating a New Initiative Category

```bash
php bin/console wpe:create-category "Climate Action" "Initiatives related to climate change"
```

### Importing Initiatives via Scraper

```bash
php bin/console wpe:scrape "United States" borchert "Human rights" --update
# OR with delete:
php bin/console wpe:scrape "United Kingdom" borchert "Security" --delete --update
```

### Creating a Test User

```bash
php bin/console wpe:create-user testuser testuser@example.com
# Add admin role:
php bin/console wpe:create-user admin admin@example.com --admin
```

### Running Database Migrations

```bash
# Generate migration from entity changes:
php bin/console doctrine:migrations:diff

# Run migrations:
php bin/console doctrine:migrations:migrate

# Rollback last migration:
php bin/console doctrine:migrations:migrate prev
```

### Adding a New Route/Controller

```bash
php bin/console make:controller MyNewController
```

### Adding a New Entity

```bash
php bin/console make:entity MyEntity
# Then generate migration:
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### Clearing Cache After Config Changes

```bash
php bin/console cache:clear
```

---

## 6. Important Notes for Contributors

1. **Always run `php bin/console cache:clear`** after modifying configuration, translations, or services.

2. **Use Attributes (not YAML)** for routing, ORM mapping, and DI when adding new code. See `GEMINI.md`.

3. **Thin Controllers:** Business logic belongs in Services or Repositories, not controllers. Keep controllers for request handling only.

4. **Type Safety:** Every function must have parameter types and return types (PHP 8.0+ requirement).

5. **Database Encryption:** User PII and votes can be encrypted. See `UserEncryptionService` and `VoteEncryptionService`.

6. **JMS Serializer Groups:** Use "default" for complete data, "simple" for minimal/performance data. Always annotate entities with groups.

7. **Test Everything:** Run tests regularly with `vendor/bin/phpunit`. Current test coverage focuses on Controller tests.

8. **Environment Variables:** Configuration like API keys, database URL, and encryption secrets are in `.env` files. Never commit `.env` files with real secrets.

9. **Docker Development:** Use Docker Compose for consistent development environment. All PHP/Symfony commands run inside the `web` container.

10. **Voting Math:** The `VotingManager` class contains complex delegation resolution and vote tallying. Review thoroughly before modifying voting logic.

---

## 7. Quick References

### Login Credentials (Docker Demo)

- **Username:** `borchert`
- **Password:** `test`
- **Role:** Admin

### Important Environment Variables

```
DATABASE_URL          # PostgreSQL connection string
APP_SECRET            # Symfony secret key
GEMINI_API_KEY        # Google Gemini API for AI features
USER_ENCRYPT_SECRET   # Encryption key for user PII
FB_PAGE_TOKEN         # Facebook integration token
LKIN_ACCESS_TOKEN     # LinkedIn integration token
SENDER_EMAIL          # Email for notifications
SMS_AUTHORIZATION     # SMS provider authentication
```

### Useful URLs (when running locally)

- Home: `http://localhost:8080`
- Login: `http://localhost:8080/login`
- Admin: `http://localhost:8080/admin`

### Git Branching Convention

- Create branches from `develop` (not `main`)
- Format: `feature/feature-description` or `bug/bug-description`
- Always open an issue first for features/bugs

---

## 8. Additional Resources

- **Official Symfony Documentation:** https://symfony.com/doc/5.4/
- **Project Website:** https://www.world-parliament.org
- **GitHub Repository:** https://github.com/world-parliament-experiment/WPE
- **Related Organization:** https://www.democracywithoutborders.org/
- **Contribution Guidelines:** See `contributing.md`
- **AI Coding Guidelines:** See `GEMINI.md`

---

**This copilot guide is current as of March 2024. For the latest updates, check the repository and GEMINI.md file.**
