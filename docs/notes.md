# Guia De Desarrollo - wallet-ledger-php

## Objetivo

Mantener una API REST de billetera/ledger contable en PHP moderno. El proyecto debe demostrar OOP, SOLID, SSOT para configuracion, persistencia transaccional, idempotencia y tests.

## Arquitectura

```text
src/
├── Domain/
├── Application/
├── Infrastructure/
└── Http/
```

### Domain

Contiene reglas de negocio puras.

Incluye:

- `Account`
- `Money`
- `Currency`
- `LedgerEntry`
- `FinancialLedger`
- value objects de identificadores
- excepciones de negocio

No debe depender de Slim, PDO, envs, SQL ni HTTP.

### Application

Orquesta casos de uso y define contratos.

Incluye:

- `CreateAccount`
- `GetAccountBalance`
- `DepositFunds`
- `WithdrawFunds`
- `TransferFunds`
- `ListAccountLedgerEntries`
- repositorios como interfaces
- `TransactionManager`
- DTOs de entrada/salida

No debe depender de Slim, PDO ni variables de entorno.

### Infrastructure

Implementa detalles externos.

Incluye:

- configuracion tipada
- lectura centralizada de envs
- conexion PDO
- schema SQLite
- repositorios SQLite
- transaction manager PDO
- reloj del sistema
- generador de IDs

### Http

Traduce HTTP hacia casos de uso.

Incluye:

- controladores Slim
- parseo/validacion superficial de JSON
- respuestas JSON
- error handler

Los controladores deben permanecer delgados.

## SSOT De Configuracion

- `.env.example` documenta todas las variables disponibles.
- `.env` es local y no se versiona.
- La unica lectura directa de `getenv()`, `$_ENV` y `$_SERVER` debe estar en `src/Infrastructure/Config/EnvironmentReader.php`.
- El resto del sistema recibe objetos de configuracion tipados.

## Reglas De Negocio

- El monto debe ser mayor a cero.
- La moneda de la operacion debe coincidir con la moneda de la cuenta.
- No se puede retirar mas que el saldo disponible.
- No se puede transferir a la misma cuenta.
- Todo cambio de saldo registra una entrada de ledger.
- Una transferencia registra debito y credito con el mismo `operation_id`.
- Toda mutacion financiera se ejecuta dentro de una transaccion.
- Las mutaciones financieras son idempotentes.
- Dinero se guarda como entero en unidades menores.
- No usar `float` para dinero.

## Idempotencia

Endpoints protegidos:

- `POST /accounts/{id}/deposits`
- `POST /accounts/{id}/withdrawals`
- `POST /transfers`

Reglas:

- Requieren header `Idempotency-Key`.
- Repetir key con el mismo payload devuelve el resultado persistido.
- Repetir key con payload distinto devuelve conflicto.
- No se duplica saldo ni ledger ante reintentos.

## Persistencia

El schema vive en `database/schema.sql`.

Tablas:

- `accounts`
- `ledger_entries`
- `idempotency_keys`

SQLite es la persistencia local y de integracion actual.

## Calidad

Comando principal:

```bash
docker compose run --rm app composer check
```

Incluye:

- PHP-CS-Fixer
- PHPStan nivel maximo con strict rules
- PHPUnit

## Desarrollo Local

No instalar PHP, Composer ni SQLite en el host.

Usar:

```bash
docker compose run --rm app composer install
docker compose run --rm app composer db:init
docker compose up --build
```

## Estado Actual

MVP funcional:

- API HTTP completa.
- Persistencia SQLite.
- Idempotencia.
- Ledger auditable.
- Configuracion SSOT.
- Tests unitarios e integracion.
- `composer check` pasa.
