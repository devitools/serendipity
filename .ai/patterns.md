# Serendipity Design Patterns

This document outlines the structure of elements intended for use in projects built with the Serendipity library. It
focuses on the components provided in the `src/Example` directory, serving as practical references for implementation.

## Structure of Elements

This guide explains the expected structure of components that follow the `Serendipity` pattern and how they should be
applied in consuming projects.

#### Entity Pattern (Expected Usage)

Serendipity defines a distinct Entity pattern that deviates from traditional DDD. Entities are constructed with metadata
attributes and inherit from command-specific base classes.

**Characteristics**:

* Uses `#[Managed]` attributes for field tracking
* Uses `#[Pattern]` attributes for input validation
* Extends command classes to separate write responsibilities
* Inherits from the base `Entity` class provided by the library

Entities are immutable, receive their dependencies through constructor injection, and are intended to encapsulate domain
logic used in query operations.

**Example from `src/Domain/Entity/Example.php`**:

```php
<?php

declare(strict_types=1);

namespace App\Example\Domain\Entity;

use App\Example\Domain\Collection\Example\FeatureCollection;
use App\Example\Domain\Entity\Command\ExampleCommand;
use Constructo\Support\Reflective\Attribute\Managed;
use Constructo\Support\Reflective\Attribute\Pattern;
use Constructo\Type\Timestamp;

class Example extends ExampleCommand
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

#### Command Pattern

Entities extend command classes that inherit from a shared base. These command classes encapsulate write behavior and
represent mutation-focused versions of the domain model.

**Example from `src/Domain/Entity/Command/ExampleCommand.php`**:

```php
<?php

declare(strict_types=1);

namespace App\Example\Domain\Entity\Command;

use App\Example\Domain\Collection\Example\FeatureCollection;
use Constructo\Support\Entity;
use Constructo\Type\Timestamp;

class ExampleCommand extends Entity
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly Timestamp $publishedAt,
        public readonly array $data,
        public readonly FeatureCollection $features,
    ) {
    }
}
```

#### Collection Pattern

Projects should implement type-safe collections by extending Serendipity’s base `Collection` class and applying item
validation.

**Example from `src/Domain/Collection/ExampleCollection.php`**:

```php
<?php

declare(strict_types=1);

namespace App\Example\Domain\Collection;

use App\Example\Domain\Entity\Example;
use Constructo\Type\Collection;

/**
 * @extends Collection<Example>
 */
final class ExampleCollection extends Collection
{
    public function current(): Example
    {
        return $this->validate($this->datum());
    }

    protected function validate(mixed $datum): Example
    {
        return ($datum instanceof Example) ? $datum : throw $this->exception(Example::class, $datum);
    }
}
```

#### CQRS Repository Pattern

Projects should implement separate repositories for command (write) and query (read) concerns, following a CQRS
architecture.

**Command repository in `src/Domain/Repository/ExampleCommandRepository.php`**:

```php
<?php

declare(strict_types=1);

namespace App\Example\Domain\Repository;

use Serendipity\Domain\Exception\ManagedException;
use App\Example\Domain\Entity\Command\ExampleCommand;

interface ExampleCommandRepository
{
    /**
     * @throws ManagedException
     */
    public function create(ExampleCommand $game): string;

    public function delete(string $id): bool;
}
```

**Query repository in `src/Domain/Repository/ExampleQueryRepository.php`**:

```php
<?php

declare(strict_types=1);

namespace App\Example\Domain\Repository;

use App\Example\Domain\Entity\Example;
use App\Example\Domain\Collection\ExampleCollection;

interface ExampleQueryRepository
{
    public function findById(string $id): ?Example;
    public function findAll(): ExampleCollection;
}
```

#### Input Pattern

Input classes in Serendipity encapsulate and validate incoming data for use cases such as commands, queries, or API
endpoints. They extend the base `Input` class and define a `rules()` method that specifies validation rules for each
field, following a convention similar to Laravel's validation system. This approach ensures consistent, reusable, and
testable input validation across the application.

**Characteristics:**

- Extend the base `Input` class
- Define a `rules()` method returning an array of validation rules
- Support for required, optional, and nested fields
- Used for both write (command) and read (query/search) actions

**Create input for write actions in `src/Presentation/Input/ExampleCreateInput.php`**:

```php
<?php

declare(strict_types=1);

namespace App\Example\Presentation\Input;

use Serendipity\Presentation\Input;

class ExampleCreateInput extends Input
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'slug' => ['required', 'string'],
            'published_at' => ['required', 'date'],
            'data' => ['required', 'array'],
            'features' => ['required', 'array'],
            'features.*.name' => ['required', 'string'],
            'features.*.description' => ['required', 'string'],
            'features.*.enabled' => ['required', 'boolean'],
        ];
    }
}
```

**Read input for query actions in `src/Presentation/Input/ExampleReadInput.php`**:

```php
<?php

declare(strict_types=1);

namespace App\Example\Presentation\Input;

use Serendipity\Presentation\Input;

class ExampleReadInput extends Input
{
    public function rules(): array
    {
        return [
            'id' => 'required|string',
        ];
    }
}
```

**Search input for filter actions in `src/Presentation/Input/ExampleSearchInput.php`**:

```php
<?php

declare(strict_types=1);

namespace App\Example\Presentation\Input;

use Serendipity\Presentation\Input;

class ExampleSearchInput extends Input
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string'],
            'slug' => ['sometimes', 'string'],
        ];
    }
}
```

#### Action Pattern

Actions in Serendipity are readonly classes that encapsulate business logic for specific use cases. They follow the
single responsibility principle and use constructor-based dependency injection with an `__invoke` method for execution.

**Characteristics:**

- Readonly classes with constructor dependency injection
- Implement `__invoke` method that takes an Input and returns a Message
- Separate actions for command (write) and query (read) operations
- Use appropriate repositories and return structured responses

**Write action in `src/Presentation/Action/CreateExampleAction.php`**:

```php
<?php

declare(strict_types=1);

namespace Serendipity\Example\Game\Presentation\Action;

use Constructo\Contract\Message;
use Serendipity\Domain\Exception\ManagedException;
use Serendipity\Example\Game\Domain\Entity\Command\GameCommand;
use Serendipity\Example\Game\Domain\Repository\GameCommandRepository;
use Serendipity\Example\Game\Presentation\Input\CreateGameInput;
use Serendipity\Infrastructure\Adapter\Serialize\Builder;
use Serendipity\Presentation\Output\Accepted;

readonly class CreateGameAction
{
    public function __construct(
        private Builder $builder,
        private GameCommandRepository $gameCommandRepository,
    ) {
    }

    /**
     * @throws ManagedException
     */
    public function __invoke(CreateGameInput $input): Message
    {
        $game = $this->builder->build(GameCommand::class, $input->values());
        $id = $this->gameCommandRepository->create($game);
        return Accepted::createFrom($id);
    }
}
```

**Read action in `src/Presentation/Action/ReadExampleAction.php`**:

```php
<?php

declare(strict_types=1);

namespace Serendipity\Example\Game\Presentation\Action;

use Constructo\Contract\Message;
use Serendipity\Example\Game\Domain\Entity\Game;
use Serendipity\Example\Game\Domain\Repository\GameQueryRepository;
use Serendipity\Example\Game\Presentation\Input\ReadGameInput;
use Serendipity\Presentation\Output\Fail\NotFound;
use Serendipity\Presentation\Output\Ok;

readonly class ReadGameAction
{
    public function __construct(private GameQueryRepository $gameQueryRepository)
    {
    }

    public function __invoke(ReadGameInput $input): Message
    {
        $id = $input->value('id', '');
        $game = $this->gameQueryRepository->getGame($id);
        return $game
            ? Ok::createFrom($game)
            : NotFound::createFrom(Game::class, $id);
    }
}
```

Notes:

- Inputs do not have other methods but `values()` which returns an array of the input data and the `value()` method to
  retrieve a specific value by key. The default value given to `value()` must be of the same type as the expected value;
- When getting a value from an input use `->value('id', '')` or `->value('amount', 0)` to ensure type safety;
- Avoid using `get()` or `has()` methods in inputs, as they can follow to an unsafe type context.

## Summary of Implemented Patterns

The components within the `src/` directory exemplify the following architectural patterns provided by Serendipity:

1. **Entity Pattern** – Metadata via attributes and inheritance from command classes
2. **Command Pattern** – Write-focused structure through command inheritance
3. **Collection Pattern** – Type-safe, validated collection wrappers
4. **CQRS Repository Pattern** – Separation of command and query interfaces
5. **Action Pattern** – Readonly classes with constructor-based dependency injection and `__invoke` usage
6. **Input Pattern** – Validation-enabled request objects extending a base Input class

These patterns form the core architectural approach promoted by the Serendipity library and are intended to be followed
consistently across consuming projects.
