[![SonarQube Cloud](https://sonarcloud.io/images/project_badges/sonarcloud-highlight.svg)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)

[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=reliability_rating)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)

[![Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=vulnerabilities)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=bugs)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)
[![Technical Debt](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=sqale_index)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=code_smells)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)

[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=coverage)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)
[![Duplicated Lines (%)](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=duplicated_lines_density)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)
[![Lines of Code](https://sonarcloud.io/api/project_badges/measure?project=devitools_serendipity&metric=ncloc)](https://sonarcloud.io/summary/new_code?id=devitools_serendipity)

---

# Serendipity

**O componente que faltava no Hyperf**

Serendipity é uma biblioteca PHP que estende o framework Hyperf com funcionalidades avançadas de Domain-Driven Design (
DDD), validação inteligente, serialização automática e infraestrutura robusta para aplicações de alta performance.

## 🍿 Visão Geral

Serendipity preenche as lacunas do ecossistema Hyperf, oferecendo uma camada de abstração poderosa que combina os
melhores padrões de desenvolvimento com a performance assíncrona do Hyperf. Utilizando
o [Constructo](https://github.com/devitools/constructo) como base, oferece metaprogramação avançada para resolver
dependências e formatar dados de forma flexível.

### Principais Características

- **🏗️ Arquitetura DDD**: Estrutura completa seguindo Domain-Driven Design
- **⚡ Assíncrono por Padrão**: Totalmente compatível com corrotinas do Hyperf
- **🔍 Validação Inteligente**: Sistema de validação baseado em atributos e regras
- **📊 Serialização Automática**: Conversão inteligente de entidades para diferentes formatos
- **🎯 Type Safety**: Tipagem forte com suporte a generics
- **🧪 Testabilidade**: Ferramentas completas para testes unitários e de integração
- **📈 Observabilidade**: Logging estruturado e monitoramento integrado

## 🚀 Instalação

### Pré-requisitos

- PHP 8.3+
- Extensões: ds, json, mongodb, pdo, swoole
- Hyperf 3.1+
- Docker 25+ (para desenvolvimento)
- Docker Compose 2.23+

### Instalação via Composer

```bash
composer require devitools/serendipity
```

### Configuração Básica

**Registre o ConfigProvider** no seu `config/config.php`:

```php
<?php

return [
    'providers' => [
        Serendipity\ConfigProvider::class,
    ],
];
```

**Configure as dependências** em `config/autoload/dependencies.php`:

```php
<?php

return [
    \Constructo\Contract\Reflect\TypesFactory::class => 
        \Serendipity\Hyperf\Support\HyperfTypesFactory::class,
    \Constructo\Contract\Reflect\SpecsFactory::class => 
        \Serendipity\Hyperf\Support\HyperfSpecsFactory::class,
];
```

## 🎯 Funcionalidades Principais

### Entidades com Tipagem Forte

Crie entidades robustas com validação automática e serialização inteligente:

```php
<?php

use Constructo\Support\Reflective\Attribute\Managed;
use Constructo\Support\Reflective\Attribute\Pattern;
use Constructo\Type\Timestamp;

class Game extends GameCommand
{
    public function __construct(
        #[Managed('id')]
        public readonly string $id,
        #[Managed('timestamp')]
        public readonly Timestamp $createdAt,
        #[Managed('timestamp')]
        public readonly Timestamp $updatedAt,
        #[Pattern('/^[a-zA-Z]{1,255}$/')]
        string $name,
        #[Pattern]
        string $slug,
        Timestamp $publishedAt,
        array $data,
        FeatureCollection $features,
    ) {
        parent::__construct(
            name: $name,
            slug: $slug,
            publishedAt: $publishedAt,
            data: $data,
            features: $features,
        );
    }
}
```

### Coleções Tipadas

Trabalhe com coleções type-safe que garantem integridade dos dados:

```php
<?php

use Constructo\Type\Collection;

/**
 * @extends Collection<Feature>
 */
class FeatureCollection extends Collection
{
    public function current(): Feature
    {
        return $this->validate($this->datum());
    }

    protected function validate(mixed $datum): Feature
    {
        return ($datum instanceof Feature)
            ? $datum
            : throw $this->exception(Feature::class, $datum);
    }
}
```

### Validação de Input Inteligente

Sistema de validação integrado com Hyperf que suporta regras complexas:

```php
<?php

use Serendipity\Presentation\Input;

final class HealthInput extends Input
{
    public function rules(): array
    {
        return [
            'message' => 'sometimes|string|max:255',
            'level' => 'required|in:debug,info,warning,error',
            'metadata' => 'array',
        ];
    }
}
```

### Actions com Injeção de Dependência

Crie actions limpas com injeção automática de dependências:

```php
<?php

readonly class HealthAction
{
    public function __invoke(HealthInput $input): array
    {
        return [
            'method' => $input->getMethod(),
            'message' => $input->value('message', 'Sistema funcionando perfeitamente!'),
            'timestamp' => time(),
            'status' => 'healthy'
        ];
    }
}
```

## 🏗️ Arquitetura de Projeto com Serendipity

Estrutura recomendada para projetos que utilizam Serendipity, baseada em projetos reais em produção:

```
/
├── app/                   # Código fonte da aplicação
│   ├── Application/       # Casos de uso da aplicação
│   │   ├── Exception/     # Exceções de aplicação
│   │   └── Service/       # Serviços de aplicação
│   ├── Domain/            # Lógica de negócio pura
│   │   ├── Entity/        # Entidades do domínio
│   │   ├── Enum/          # Enums do domínio
│   │   ├── Provider/      # Provedores de domínio
│   │   ├── Repository/    # Contratos de repositório
│   │   ├── Service/       # Serviços de domínio
│   │   ├── Support/       # Utilitários do domínio
│   │   └── Validator/     # Validadores de negócio
│   ├── Infrastructure/    # Implementações de infraestrutura
│   │   ├── Exception/     # Exceções de infraestrutura
│   │   ├── Parser/        # Parsers de dados
│   │   ├── Repository/    # Implementações de repositório
│   │   ├── Service/       # Serviços de infraestrutura
│   │   ├── Support/       # Utilitários de infraestrutura
│   │   └── Validator/     # Validadores de infraestrutura
│   └── Presentation/      # Camada de apresentação
│       ├── Action/        # Controllers/Actions
│       ├── Input/         # Validação de entrada
│       └── Service/       # Serviços de apresentação
├── bin/                   # Scripts executáveis
│   ├── hyperf.php         # Script principal do Hyperf
│   └── phpunit.php        # Script de testes
├── compose.yml           # Configuração principal do Docker Compose
├── composer.json         # Dependências do Composer
├── composer.lock         # Lock das dependências
├── config/               # Configurações da aplicação
│   └── autoload/         # Configurações carregadas automaticamente
├── deptrac.yaml          # Configuração de análise de dependências
├── Dockerfile            # Configuração do Docker
├── LICENSE               # Licença do projeto
├── makefile              # Comandos de desenvolvimento
├── migrations/           # Migrações do banco de dados
├── phpcs.xml            # Configuração do PHP CodeSniffer
├── phpmd.xml            # Configuração do PHP Mess Detector
├── phpstan.neon         # Configuração do PHPStan
├── phpunit.xml          # Configuração do PHPUnit
├── psalm.xml            # Configuração do Psalm
├── README.md            # Documentação principal
├── rector.php           # Configuração do Rector
├── runtime/             # Arquivos temporários e cache
├── sonar-project.properties # Configuração do SonarQube
├── storage/             # Armazenamento local
└── tests/               # Testes automatizados
    ├── Application/     # Testes de aplicação
    ├── Domain/          # Testes de domínio
    ├── Infrastructure/  # Testes de infraestrutura
    └── Presentation/    # Testes de apresentação
```

### Organização das Camadas

**Application Layer** - Casos de uso e orquestração

- **Service/**: Coordenam operações entre domínio e infraestrutura
- **Exception/**: Exceções específicas da camada de aplicação

**Domain Layer** - Lógica de negócio pura

- **Entity/**: Entidades principais do negócio
- **Enum/**: Enumerações e constantes do domínio
- **Repository/**: Interfaces para persistência
- **Service/**: Regras de negócio complexas
- **Validator/**: Validações de regras de negócio

**Infrastructure Layer** - Implementações técnicas

- **Repository/**: Implementações concretas dos repositórios
- **Service/**: Integrações com APIs externas
- **Parser/**: Processamento e transformação de dados
- **Support/**: Utilitários técnicos

**Presentation Layer** - Interface com o mundo externo

- **Action/**: Endpoints HTTP e handlers
- **Input/**: Validação e sanitização de entrada
- **Service/**: Formatação de resposta

### Exemplo de Estrutura de Action

```php
<?php

namespace App\Presentation\Action;

use App\Presentation\Input\ProcessLeadInput;
use App\Application\Service\LeadProcessorService;

readonly class ProcessLeadAction
{
    public function __construct(
        private LeadProcessorService $processor
    ) {}

    public function __invoke(ProcessLeadInput $input): array
    {
        $result = $this->processor->process($input->validated());
        
        return [
            'success' => true,
            'data' => $result->toArray(),
        ];
    }
}
```

## 📋 Exemplos Práticos

### Entidade User com Validação

```php
<?php

namespace App\Domain\Entity;

use Constructo\Support\Reflective\Attribute\Managed;
use Constructo\Support\Reflective\Attribute\Pattern;
use DateTime;

readonly class User
{
    public function __construct(
        #[Managed('id')]
        public int $id,
        #[Pattern('/^[a-zA-Z\s]{2,100}$/')]
        public string $name,
        public DateTime $birthDate,
        public bool $isActive = true,
        public array $tags = [],
    ) {
    }

    public function getAge(): int
    {
        return $this->birthDate->diff(new DateTime())->y;
    }

    public function isAdult(): bool
    {
        return $this->getAge() >= 18;
    }

    public function addTag(string $tag): array
    {
        return [...$this->tags, $tag];
    }
}
```

### Input de Validação para User

```php
<?php

namespace App\Presentation\Input;

use Serendipity\Presentation\Input;

final class CreateUserInput extends Input
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:100|regex:/^[a-zA-Z\s]+$/',
            'birth_date' => 'required|date|before:today',
            'is_active' => 'sometimes|boolean',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|max:50',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'O nome deve conter apenas letras e espaços',
            'birth_date.before' => 'A data de nascimento deve ser anterior a hoje',
            'email.unique' => 'Este email já está em uso',
            'password.confirmed' => 'A confirmação da senha não confere',
        ];
    }
}
```

### Action para Criação de User

```php
<?php

namespace App\Presentation\Action;

use App\Domain\Entity\User;
use App\Presentation\Input\CreateUserInput;
use App\Domain\Service\UserService;
use DateTime;
use Psr\Log\LoggerInterface;

readonly class CreateUserAction
{
    public function __construct(
        private UserService $userService,
        private LoggerInterface $logger
    ) {}

    public function __invoke(CreateUserInput $input): array
    {
        $userData = $input->validated();
        
        $user = new User(
            id: 0, // Será preenchido pelo banco
            name: $userData['name'],
            birthDate: new DateTime($userData['birth_date']),
            isActive: $userData['is_active'] ?? true,
            tags: $userData['tags'] ?? []
        );

        $savedUser = $this->userService->create($user, $userData['password']);

        $this->logger->info('Usuário criado com sucesso', [
            'user_id' => $savedUser->id,
            'name' => $savedUser->name,
            'is_adult' => $savedUser->isAdult(),
        ]);

        return [
            'success' => true,
            'user' => [
                'id' => $savedUser->id,
                'name' => $savedUser->name,
                'age' => $savedUser->getAge(),
                'is_adult' => $savedUser->isAdult(),
                'is_active' => $savedUser->isActive,
                'tags' => $savedUser->tags,
            ],
        ];
    }
}
```

### Serviço de Domínio para User

```php
<?php

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Service\PasswordHashService;

readonly class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHashService $passwordService
    ) {}

    public function create(User $user, string $password): User
    {
        // Validações de negócio
        if (!$user->isAdult()) {
            throw new \DomainException('Usuário deve ser maior de idade');
        }

        if (count($user->tags) > 10) {
            throw new \DomainException('Usuário não pode ter mais de 10 tags');
        }

        // Hash da senha
        $hashedPassword = $this->passwordService->hash($password);

        // Persistir no banco
        return $this->userRepository->save($user, $hashedPassword);
    }

    public function updateTags(int $userId, array $newTags): User
    {
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            throw new \DomainException('Usuário não encontrado');
        }

        if (count($newTags) > 10) {
            throw new \DomainException('Usuário não pode ter mais de 10 tags');
        }

        return $this->userRepository->updateTags($userId, $newTags);
    }
}
```

### Repositório de User

```php
<?php

namespace App\Domain\Repository;

use App\Domain\Entity\User;

interface UserRepositoryInterface
{
    public function save(User $user, string $hashedPassword): User;
    
    public function findById(int $id): ?User;
    
    public function findByEmail(string $email): ?User;
    
    public function updateTags(int $userId, array $tags): User;
    
    public function findActiveUsers(): array;
    
    public function findUsersByTag(string $tag): array;
}
```

### Implementação do Repositório

```php
<?php

namespace App\Infrastructure\Repository;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use Hyperf\Database\ConnectionInterface;
use DateTime;

readonly class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $connection
    ) {}

    public function save(User $user, string $hashedPassword): User
    {
        $id = $this->connection->table('users')->insertGetId([
            'name' => $user->name,
            'email' => $user->email ?? '',
            'password' => $hashedPassword,
            'birth_date' => $user->birthDate->format('Y-m-d'),
            'is_active' => $user->isActive,
            'tags' => json_encode($user->tags),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return new User(
            id: $id,
            name: $user->name,
            birthDate: $user->birthDate,
            isActive: $user->isActive,
            tags: $user->tags
        );
    }

    public function findById(int $id): ?User
    {
        $userData = $this->connection
            ->table('users')
            ->where('id', $id)
            ->first();

        if (!$userData) {
            return null;
        }

        return new User(
            id: $userData->id,
            name: $userData->name,
            birthDate: new DateTime($userData->birth_date),
            isActive: (bool) $userData->is_active,
            tags: json_decode($userData->tags, true) ?? []
        );
    }

    public function findByEmail(string $email): ?User
    {
        $userData = $this->connection
            ->table('users')
            ->where('email', $email)
            ->first();

        if (!$userData) {
            return null;
        }

        return new User(
            id: $userData->id,
            name: $userData->name,
            birthDate: new DateTime($userData->birth_date),
            isActive: (bool) $userData->is_active,
            tags: json_decode($userData->tags, true) ?? []
        );
    }

    public function updateTags(int $userId, array $tags): User
    {
        $this->connection
            ->table('users')
            ->where('id', $userId)
            ->update([
                'tags' => json_encode($tags),
                'updated_at' => now(),
            ]);

        return $this->findById($userId);
    }

    public function findActiveUsers(): array
    {
        $users = $this->connection
            ->table('users')
            ->where('is_active', true)
            ->get();

        return $users->map(fn($userData) => new User(
            id: $userData->id,
            name: $userData->name,
            birthDate: new DateTime($userData->birth_date),
            isActive: true,
            tags: json_decode($userData->tags, true) ?? []
        ))->toArray();
    }

    public function findUsersByTag(string $tag): array
    {
        $users = $this->connection
            ->table('users')
            ->whereJsonContains('tags', $tag)
            ->get();

        return $users->map(fn($userData) => new User(
            id: $userData->id,
            name: $userData->name,
            birthDate: new DateTime($userData->birth_date),
            isActive: (bool) $userData->is_active,
            tags: json_decode($userData->tags, true) ?? []
        ))->toArray();
    }
}
```

### Coleção Tipada de Users

```php
<?php

namespace App\Domain\Collection;

use Constructo\Type\Collection;
use App\Domain\Entity\User;

/**
 * @extends Collection<User>
 */
class UserCollection extends Collection
{
    public function current(): User
    {
        return $this->validate($this->datum());
    }

    protected function validate(mixed $datum): User
    {
        return ($datum instanceof User)
            ? $datum
            : throw $this->exception(User::class, $datum);
    }

    public function getActiveUsers(): UserCollection
    {
        return new self(
            array_filter($this->items, fn(User $user) => $user->isActive)
        );
    }

    public function getAdultUsers(): UserCollection
    {
        return new self(
            array_filter($this->items, fn(User $user) => $user->isAdult())
        );
    }

    public function getUsersByTag(string $tag): UserCollection
    {
        return new self(
            array_filter($this->items, fn(User $user) => in_array($tag, $user->tags))
        );
    }

    public function getAverageAge(): float
    {
        if ($this->count() === 0) {
            return 0;
        }

        $totalAge = array_sum(
            array_map(fn(User $user) => $user->getAge(), $this->items)
        );

        return $totalAge / $this->count();
    }
}
```

## 🧪 Testes

Serendipity fornece ferramentas robustas para testes:

```php
<?php

use Serendipity\Testing\TestCase;
use App\Domain\Entity\User;
use App\Presentation\Input\CreateUserInput;
use App\Presentation\Action\CreateUserAction;
use DateTime;

class CreateUserActionTest extends TestCase
{
    public function testCreateUserSuccess(): void
    {
        $input = new CreateUserInput([
            'name' => 'João Silva',
            'birth_date' => '1990-05-15',
            'email' => 'joao@example.com',
            'password' => 'senha123456',
            'password_confirmation' => 'senha123456',
            'is_active' => true,
            'tags' => ['desenvolvedor', 'php'],
        ]);

        $action = $this->container()->get(CreateUserAction::class);
        $result = $action($input);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals('João Silva', $result['user']['name']);
        $this->assertTrue($result['user']['is_adult']);
        $this->assertTrue($result['user']['is_active']);
        $this->assertContains('desenvolvedor', $result['user']['tags']);
    }

    public function testCreateUserValidationFails(): void
    {
        $this->expectException(\Hyperf\Validation\ValidationException::class);

        $input = new CreateUserInput([
            'name' => '', // Nome vazio
            'birth_date' => '2020-01-01', // Menor de idade
            'email' => 'email-invalido', // Email inválido
            'password' => '123', // Senha muito curta
        ]);

        $input->validated();
    }

    public function testUserEntityMethods(): void
    {
        $user = new User(
            id: 1,
            name: 'Maria Santos',
            birthDate: new DateTime('1985-03-20'),
            isActive: true,
            tags: ['designer', 'ui-ux']
        );

        $this->assertEquals(39, $user->getAge()); // Assumindo 2024
        $this->assertTrue($user->isAdult());
        $this->assertEquals(['designer', 'ui-ux', 'frontend'], $user->addTag('frontend'));
    }
}

class UserServiceTest extends TestCase
{
    public function testCreateUserWithBusinessRules(): void
    {
        $userService = $this->container()->get(\App\Domain\Service\UserService::class);
        
        $user = new User(
            id: 0,
            name: 'Pedro Costa',
            birthDate: new DateTime('1992-08-10'),
            isActive: true,
            tags: ['backend']
        );

        $result = $userService->create($user, 'senhaSegura123');

        $this->assertInstanceOf(User::class, $result);
        $this->assertGreaterThan(0, $result->id);
    }

    public function testCreateMinorUserFails(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Usuário deve ser maior de idade');

        $userService = $this->container()->get(\App\Domain\Service\UserService::class);
        
        $minorUser = new User(
            id: 0,
            name: 'Criança',
            birthDate: new DateTime('2020-01-01'),
            isActive: true,
            tags: []
        );

        $userService->create($minorUser, 'senha123');
    }

    public function testUpdateTagsSuccess(): void
    {
        $userService = $this->container()->get(\App\Domain\Service\UserService::class);
        
        // Mock do usuário existente
        $existingUser = new User(
            id: 1,
            name: 'Ana Silva',
            birthDate: new DateTime('1988-12-05'),
            isActive: true,
            tags: ['old-tag']
        );

        $newTags = ['new-tag', 'another-tag'];
        $result = $userService->updateTags(1, $newTags);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($newTags, $result->tags);
    }
}

class UserCollectionTest extends TestCase
{
    public function testUserCollectionFilters(): void
    {
        $users = [
            new User(1, 'João', new DateTime('1990-01-01'), true, ['php']),
            new User(2, 'Maria', new DateTime('2010-01-01'), true, ['js']), // Menor
            new User(3, 'Pedro', new DateTime('1985-01-01'), false, ['python']), // Inativo
            new User(4, 'Ana', new DateTime('1992-01-01'), true, ['php', 'laravel']),
        ];

        $collection = new \App\Domain\Collection\UserCollection($users);

        // Teste filtro de usuários ativos
        $activeUsers = $collection->getActiveUsers();
        $this->assertCount(3, $activeUsers);

        // Teste filtro de usuários adultos
        $adultUsers = $collection->getAdultUsers();
        $this->assertCount(3, $adultUsers);

        // Teste filtro por tag
        $phpUsers = $collection->getUsersByTag('php');
        $this->assertCount(2, $phpUsers);

        // Teste média de idade
        $averageAge = $collection->getAverageAge();
        $this->assertGreaterThan(0, $averageAge);
    }

    public function testEmptyCollectionAverageAge(): void
    {
        $collection = new \App\Domain\Collection\UserCollection([]);
        $this->assertEquals(0, $collection->getAverageAge());
    }
}
```

## ⚡ Performance e Observabilidade

### Logging Estruturado

```php
<?php

$this->logger->info('Lead processado com sucesso', [
    'lead_id' => $leadId,
    'source' => $source,
    'processing_time_ms' => $processingTime,
    'memory_usage' => memory_get_usage(true),
]);
```

### Métricas e Monitoramento

```php
<?php

// Integração com sistemas de métricas
use Hyperf\Context\Context;

Context::set('metrics.processing_start', microtime(true));
$result = $this->processLead($input);
$duration = microtime(true) - Context::get('metrics.processing_start');

$this->logger->info('Métrica de performance', [
    'operation' => 'process_lead',
    'duration_ms' => round($duration * 1000, 2),
    'success' => $result->isSuccess(),
]);
```

## 🔧 Configuração Avançada

### Schema e Especificações

Configure schemas personalizados em `config/autoload/schema.php`:

```php
<?php

return [
    'specs' => [
        'lead' => [
            'id' => 'string',
            'name' => 'string',
            'email' => 'email',
            'phone' => 'string',
            'created_at' => 'timestamp',
        ],
        'quote' => [
            'id' => 'string',
            'lead_id' => 'string',
            'amount' => 'decimal',
            'status' => 'enum:pending,approved,rejected',
        ],
    ],
];
```

### Request admission

See [request admission](docs/request-admission.md) for the opt-in shared rate budget, local concurrency protection and failure behavior.

### Middlewares Personalizados

```php
<?php

use Serendipity\Hyperf\Middleware\AbstractMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LeadValidationMiddleware extends AbstractMiddleware
{
    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Validação específica de leads
        $body = $request->getParsedBody();
        
        if (isset($body['email']) && !filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Email inválido');
        }
        
        return $handler->handle($request);
    }
}
```

## 📚 Comandos CLI

Serendipity inclui comandos úteis para desenvolvimento:

```bash
# Gerar regras de validação
php bin/hyperf.php gen:rules LeadRules

# Executar health check via CLI
php bin/hyperf.php health:check

# Processar leads em lote
php bin/hyperf.php lead:process-batch

# Limpar caches
php bin/hyperf.php cache:clear
```

## 🤝 Contribuindo

Fork o projeto, crie uma branch para sua feature, commit suas mudanças, push para a branch e abra um Pull Request.

### Padrões de Desenvolvimento

- Siga PSR-12 para código PHP
- Use tipagem forte sempre que possível
- Implemente testes para novas funcionalidades
- Documente mudanças no README

## 📄 Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## 🔗 Links Relacionados

- [Hyperf Framework](https://hyperf.io/)
- [Constructo](https://github.com/devitools/constructo)
- [Devitools](https://devi.tools/)

---

**Serendipity** - Descobrindo o potencial completo do Hyperf através de componentes elegantes e poderosos.
