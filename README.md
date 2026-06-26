# wallet-ledger-php

API REST de billetera/ledger contable en PHP 8.3.

El proyecto modela operaciones financieras simples con OOP, reglas de dominio aisladas, persistencia SQLite, idempotencia y tests automatizados. No requiere instalar PHP, Composer ni SQLite en el host: todo corre dentro de Docker o VSCode Dev Container.

## Stack

- PHP 8.3
- Composer
- Slim Framework
- PDO
- SQLite
- PHPUnit
- PHPStan nivel maximo con strict rules
- PHP-CS-Fixer
- Docker Compose
- VSCode Dev Containers

## Desarrollo Con Dev Container

1. Abrir este directorio en VSCode.
2. Ejecutar `Dev Containers: Reopen in Container`.
3. Trabajar dentro del contenedor `Wallet Ledger PHP`.

El Dev Container ejecuta `composer install` al crearse. No hace falta instalar herramientas PHP en el host.

## Desarrollo Con Docker Compose

Crear `.env` local si todavia no existe:

```bash
cp .env.example .env
```

Instalar dependencias:

```bash
docker compose run --rm app composer install
```

Inicializar la base SQLite local:

```bash
docker compose run --rm app composer db:init
```

Levantar la API:

```bash
docker compose up --build
```

La API queda disponible en:

```text
http://127.0.0.1:8080
```

`localhost` tambien puede funcionar segun el entorno, pero `127.0.0.1` es la URL recomendada para pruebas locales.

## Comandos De Calidad

Formatear codigo:

```bash
docker compose run --rm app composer format
```

Validar formato:

```bash
docker compose run --rm app composer format:check
```

Ejecutar PHPStan estricto:

```bash
docker compose run --rm app composer lint
```

Ejecutar tests:

```bash
docker compose run --rm app composer test
```

Ejecutar todas las verificaciones:

```bash
docker compose run --rm app composer check
```

## Configuracion

`.env.example` es la fuente versionada de variables disponibles. `.env` es local y no debe commitearse.

Variables actuales:

- `APP_ENV`: ambiente de ejecucion (`local`, `test`, `production`).
- `APP_DEBUG`: activa detalles de debug en desarrollo.
- `APP_PORT`: puerto publicado en el host.
- `DATABASE_DSN`: DSN PDO.
- `DATABASE_PATH`: ruta del archivo SQLite dentro del proyecto.

La lectura de envs esta centralizada en `src/Infrastructure/Config/EnvironmentReader.php`. El resto del codigo recibe configuracion tipada.

## Endpoints

### Crear Cuenta

```bash
curl -sS -X POST http://127.0.0.1:8080/accounts \
  -H 'Content-Type: application/json' \
  -d '{"currency":"ARS"}'
```

Respuesta:

```json
{
  "data": {
    "account_id": "acc_123",
    "balance": 0,
    "currency": "ARS"
  }
}
```

### Obtener Cuenta

```bash
curl -sS http://127.0.0.1:8080/accounts/acc_123
```

### Depositar

```bash
curl -sS -X POST http://127.0.0.1:8080/accounts/acc_123/deposits \
  -H 'Content-Type: application/json' \
  -H 'Idempotency-Key: deposit-001' \
  -d '{"amount":15000,"currency":"ARS"}'
```

### Retirar

```bash
curl -sS -X POST http://127.0.0.1:8080/accounts/acc_123/withdrawals \
  -H 'Content-Type: application/json' \
  -H 'Idempotency-Key: withdrawal-001' \
  -d '{"amount":5000,"currency":"ARS"}'
```

### Transferir

```bash
curl -sS -X POST http://127.0.0.1:8080/transfers \
  -H 'Content-Type: application/json' \
  -H 'Idempotency-Key: transfer-001' \
  -d '{
    "from_account_id":"acc_123",
    "to_account_id":"acc_456",
    "amount":7000,
    "currency":"ARS"
  }'
```

### Listar Movimientos

```bash
curl -sS http://127.0.0.1:8080/accounts/acc_123/transactions
```

## Idempotencia

Las mutaciones financieras requieren header `Idempotency-Key`:

- `POST /accounts/{id}/deposits`
- `POST /accounts/{id}/withdrawals`
- `POST /transfers`

Si se repite la misma key con el mismo payload, la API devuelve la respuesta persistida originalmente. Si se reutiliza la key con payload diferente, devuelve `409 conflict`.

## Errores

Formato:

```json
{
  "error": {
    "code": "business_rule_violation",
    "message": "Insufficient funds for account: acc_123"
  }
}
```

Codigos principales:

- `400`: request invalido o header requerido faltante.
- `404`: cuenta inexistente.
- `409`: conflicto de idempotencia.
- `422`: regla de negocio incumplida.
- `500`: error inesperado.

## Arquitectura

El codigo esta separado por capas:

- `Domain`: entidades, value objects y reglas puras de negocio.
- `Application`: casos de uso, DTOs y contratos.
- `Infrastructure`: PDO, SQLite, configuracion, reloj e IDs.
- `Http`: controladores Slim, parsing de requests y respuestas JSON.

Reglas de diseño:

- Los controladores no contienen reglas de negocio.
- `Application` no depende de Slim, PDO ni variables de entorno.
- `Domain` no depende de infraestructura.
- Las operaciones financieras son transaccionales mediante `TransactionManager`.
- El ledger audita cada cambio de saldo.
- Dinero se representa con enteros en unidades menores; no se usa `float`.

## Tests

La suite incluye tests unitarios de dominio/aplicacion y tests de integracion con SQLite en memoria.

Estado actual verificado:

```text
41 tests, 110 assertions
```
