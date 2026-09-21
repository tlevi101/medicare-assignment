# Medicare próba feladat megoldás

## Stack:
- PHP 8.5^
- Laravel 13^
- MariaDB (Docker) / SQLite (natív telepítésnél)
- OpenAPI
- Pest

## Telepítés

### Követelmények
- PHP 8.3+ és Composer (a Sail image 8.5-öt használ)
- Node 20+ (csak az OpenAPI bundleléshez és a Vite buildhez)
- Docker **vagy** a `pdo_sqlite` PHP kiterjesztés, attól függ melyik utat választod

A `.env.example` a Docker / MariaDB beállításokkal jön, mert alapvetően én is úgy fejlesztettem.

### Docker / Sail (MariaDB)

```bash
composer install
```

```bash
cp .env.example .env
```

```bash
./vendor/bin/sail up -d
```

```bash
./vendor/bin/sail artisan key:generate
```

```bash
./vendor/bin/sail artisan migrate --seed
```

Az OpenAPI bundleléshez kell még:

```bash
./vendor/bin/sail npm install
```

### Natív PHP (SQLite)

Itt a Docker megoldással ellentétben SQLite adatbázist használok, leginkább azért, mert nem akarom
hogy adatbázis szervert kelljen telepíteni hozzá.

```bash
composer install
```

A [scripts/use-sqlite.php](scripts/use-sqlite.php) írja meg a natív `.env`-et: a `.env.example`-ből
indul ki, a `DB_*` beállításokat SQLite-ra cseréli, az `APP_URL`-t a `php artisan serve` portjára
állítja, és létrehozza a `database/database.sqlite` fájlt.
*Megjegyjés: Ezt a scriptet AI dobta össze*

```bash
composer sqlite
```

Közvetlenül is futtatható, ha nem akarsz a composeren át menni:

```bash
php scripts/use-sqlite.php
```

```bash
php artisan key:generate
```

```bash
php artisan migrate --seed
```

```bash
php artisan serve
```

Amire figyelni kell:
- kell hozzá a `pdo_sqlite` kiterjesztés, enélkül a script hibaüzenettel kilép

## API
Az API Docker alatt a http://localhost/api, natívan a http://localhost:8000/api címen érhető el

Feladat megoldáshoz csináltam openapi specifikációt. Illetve ennek egy részét írtam kézzel, de a nagy részét AI csinálta. 
**Csak az openapin dolgozott az AI!** Így az API.t több féle képpen is el lehet érni.

A [laravelnek az új JSON:API](https://laravel.com/framework/docs/13.x/eloquent-resources#jsonapi-resources) formátumát használom.

### IDE-be épített OpenaAPI kliens 
Ha van IDE-ben kliens akkor az [./openapi/openapi.yaml](./openapi/openapi.yaml) -t elég megnyitni és elvileg kezelni tudja.
De előforudlhat hogy bundle nélkül nem kezeli jól, ebben az esetben ezt a parancsot kell lefuttatni: 
```bash
npm run api:bundle
```
Majd a [public/docs/openapi.yaml](public/docs/openapi.yaml)-t kell megnyitni

### Postman
A teljes [public/docs/openapi.yaml](public/docs/openapi.yaml)-t be lehet importálni postmanbe.

Ha esetleg ez nem sikerülne vagy ez egyszerűbb, be tettem külön az exportált [postman collection](./Medicare%20API.postman_collection.json)

## Tesztelés
Alapvetőne a tesztesetekből teszteltem, nem a Postmanből, emiatt elég sok teszt eset született.

A tesztek memóriában futó SQLite-on mennek (`phpunit.xml`), tehát se MariaDB, se a
`database/database.sqlite` nem kell hozzájuk, és mindkét telepítési úton ugyanazt futtatják:

```bash
php artisan test
```

Vagy Sail alatt:

```bash
./vendor/bin/sail artisan test
```
