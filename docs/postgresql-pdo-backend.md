# Optional PostgreSQL PDO backend

This experimental backend adapts the existing Serendipity relational interfaces to
`hyperf/db-connection` and `hyperf/database-pgsql`. It uses PDO PostgreSQL with the
Swoole PDO hook instead of `Swoole\Coroutine\PostgreSQL`. The default backend and
the existing native-client session-recovery module remain unchanged.

## Enable explicitly

After reviewing and releasing this module, an application can select the backend
in its own `config/autoload/dependencies.php`:

```php
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\PostgreSQLPdoBackend;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PostgreSQLSessionRecovery;

use function Hyperf\Support\env;

$backend = env('POSTGRES_CONNECTION_BACKEND', 'swoole');
$databaseDependencies = match ($backend) {
    'swoole' => PostgreSQLSessionRecovery::dependencies(),
    'pdo' => PostgreSQLPdoBackend::dependencies(),
    default => throw new InvalidArgumentException('Unsupported PostgreSQL backend'),
};

return array_replace($existingDependencies, $databaseDependencies);
```

The library does not register these bindings automatically or read this environment
variable itself. Applications that do not opt in retain their existing behavior.
The example assumes the native session-recovery module is the application's existing
baseline; applications not using it can retain their own default bindings instead.

Named connections must be `pgsql` entries under **`databases`**, as expected by
`hyperf/db-connection`. Existing `db` entries alone are insufficient. `make('auto')`
selects `databases.auto`; it does not silently fall back to another connection.
All existing named database entries keep their own pools. Each PostgreSQL
connection role must specify at most one host: host arrays with multiple entries,
libpq comma-separated host strings, and multiple read/write configuration
alternatives are rejected before resolving a connector. One-element host arrays
and single-host read/write configuration remain supported. This restriction
prevents inherited host failover from repeating a failed connection or session
initialization; it does not restrict other drivers or the number of named databases.
The adapter resolves the connection in the current coroutine on every call and
does not retain a pooled connection in a singleton repository.

The two Hyperf packages are already required by Serendipity, so this change adds
no Composer dependency or runtime version upgrade. The runtime must expose `pgsql`
in `PDO::getAvailableDrivers()` and enable `SWOOLE_HOOK_PDO_PGSQL`. Swoole can
register that PDO driver without `extension_loaded('pdo_pgsql')` being true.
Operations reject a missing coroutine or disabled PDO hook before borrowing a
connection, rather than silently blocking the worker or leaking a connection.

## Compatibility and connection budget

`ConnectionFactory` and `Connection` keep their signatures. Queries return arrays
of associative rows; `fetch()` returns `Constructo\Support\Set` and an empty Set
for no row; `execute()` returns affected rows. `insert()` returns 1/0 from the
framework's insert result, not a generated primary key. Use explicit `RETURNING`
through `fetch()` when an ID is needed.

All adapter operations use the **writer PDO**, including reads. This matches the
single-session behavior of the native backend, preserves transaction visibility,
and avoids opening a second read session per pooled object. The framework accepts
read/write config, but this adapter deliberately does not enable read routing.
Schema/search path setup uses the native PostgreSQL connector.

`run()` callbacks now receive **PDO**, not `Swoole\Coroutine\PostgreSQL`. Consumers
with client-specific callbacks must adapt them before switching. Application
callback exceptions propagate unchanged and do not alone invalidate a session.
Direct uses of `Hyperf\DB\DB` bypass this adapter and still use the old backend;
they must be audited before claiming a full A/B migration.

Pool sizing, channel accounting, waiting borrowers and coroutine-context release
remain owned by Hyperf. The small pool subclasses only choose the single-attempt
PostgreSQL wrapper, remove the acquisition retry, and discard an invalid physical
session before returning the managed object. Other drivers retain their native
pool connection class. PDO is non-persistent and always uses exception mode.

Unlike the legacy facade's usual per-operation release, the native PDO resolver
holds each borrowed named connection until the coroutine exits. Awaiting a slow
provider after a database operation can therefore keep a pool slot occupied.
The same configured pool size can experience more waiting; the later A/B test
must measure this effect rather than assuming equal capacity or increasing limits.

The optional bindings also select a PDO subtype for `HyperfConnectionChecker`
and the `ConnectionChecker` interface, so the existing connection-check middleware
uses the selected PDO backend. Each health check executes one `SELECT 1` against
the named `default` connection in its own coroutine. That coroutine's normal
resolver cleanup returns the probe lease before the request handler runs; no
manual pool release is added. The checker retains the existing boolean availability
and attempt-count API, and the middleware still continues after a failed probe.
Legacy retry-count and delay settings do not cause PDO probe retries or sleeps.
The default/native checker and its constructor remain unchanged.

## No replay and transaction failures

The selected PostgreSQL connection disables Hyperf's retry of failed queries and
`BEGIN`; the connector does not retry a failed PDO connect. The transaction helper
accepts one attempt only, preserves the callback's initial exception if rollback
also fails, and never reexecutes the callback. `COMMIT` is invoked once. A transport
failure while confirming COMMIT leaves its outcome uncertain; local rollback or
pool cleanup does not establish that the transaction was undone.

PDO transport SQLSTATE class `08` and server shutdown states `57P01`, `57P02`,
`57P03` invalidate the session. Without a specific SQLSTATE, the framework's
transport-message detection is used only for generic/absent state. Structured
syntax, constraint and server-cancellation errors do not become transport errors
because their message happens to contain words such as "server closed".

An invalid session rejects subsequent operations in the same coroutine. Rollback
then only clears local transaction state. Release closes the physical session;
the next acquisition reconnects without growing the managed pool. Healthy sessions
retain normal rollback and nested savepoint behavior. A failed rollback discards
the session rather than retrying cleanup. No new SQL/bindings/credentials logging
is introduced by this module; existing application logging policy still applies.

## Validation and later staging experiment

The PR tests the adapter against local PostgreSQL with pool size one, including
real backend termination, repeated recovery, concurrent borrowers, nested
transactions, SQL timeouts and callback failures. A sequence side effect proves
that a failed statement executes once. PDO mocks separately count failed reads,
writes, BEGIN and COMMIT. A coroutine timer verifies PostgreSQL I/O yields to the
event loop. Existing default-backend tests continue to run unchanged.

The PHPMD static-call allowlist adds only the two runtime inspection methods
`inCoroutine` and `getHookFlags`; architecture boundaries are unchanged.

After review and a separately confirmed release:

1. Update QuoteGuide to the confirmed version and audit native callbacks/facades.
2. Configure one backend at a time; preserve pools, workers, resources and timeouts.
3. Deploy official staging only through `dev-deploy`, with all providers on owned mocks.
4. Repeat the original six-minute / 150-VU auction scenario for both backends,
   including repeat rounds because the reactor failure is intermittent.
5. Compare HTTP failures/checks/p95, reactor/crash logs including DEFAULT severity,
   pool waiting, physical sessions and post-load idle transactions.

This PR does not update QuoteGuide, publish a release, deploy staging or change
production. No performance or reactor-fix claim is made before the controlled
staging comparison. Reverting the opt-in configuration restores the default
backend through the normal deployment flow; do not swap an active transaction's
backend in place.
