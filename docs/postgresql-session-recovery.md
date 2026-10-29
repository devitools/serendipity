# PostgreSQL session recovery

Session recovery is an explicit opt-in for the `hyperf/db` PostgreSQL driver.
It keeps the existing Swoole PostgreSQL backend and the Serendipity relational
connection interfaces. It does not switch to PDO or enable read/write routing.

Add its bindings to the application's `config/autoload/dependencies.php`:

```php
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PostgreSQLSessionRecovery;

return array_replace($existingDependencies, PostgreSQLSessionRecovery::dependencies());
```

The application must provide its usual `Psr\Log\LoggerInterface` binding and a
runtime with `Swoole\Coroutine\PostgreSQL` support. The binding applies to
PostgreSQL pools created by `Hyperf\DB\Pool\PoolFactory`; other drivers and
applications that do not register the binding retain their existing behavior.

The failed operation throws its original exception and is never automatically
replayed. A transport or protocol failure invalidates the session. Release
discards the physical connection while returning the managed object to the
native pool; a later acquisition establishes a fresh session. An invalid active
transaction cannot reconnect or continue. Its rollback only resets local state
and discards the session. Structured SQL failures keep healthy sessions usable
after the normal transaction rollback.

The adapter preserves diagnostics when native error messages are missing.
Invalidation warnings contain the pool, stage, category, SQLSTATE, driver code
and result status when available. They do not include SQL, bindings or credentials.
Cleanup and logging failures do not replace the operation's original exception.

A connection failure during `COMMIT` leaves the transaction outcome uncertain;
the caller must not assume rollback or retry the write automatically. Application
transaction helpers must also preserve the initial exception if cleanup fails.
This module does not introduce a transaction callback runner, partner request
retries, tracing changes, infrastructure changes or deployment behavior.
