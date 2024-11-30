# SosoRicsi Query Library

Ez a könyvtár egy PDO-alapú PHP osztály, amely lehetővé teszi egyszerű és rugalmas adatbázis-műveletek végrehajtását. Az osztály tartalmazza a leggyakrabban használt adatbázis-kezelési funkciókat, például lekérdezések készítését, beszúrást, törlést és tranzakciókezelést.

## Telepítés

### GitHub-ról:

1. Klónozd a projektet vagy töltsd le a forráskódot a GitHubról:
   ```bash
   git clone https://github.com/your-repo/sosoricsi-query.git
   ```
2. Töltsd be az osztályt a projektedbe:
   ```php
   require_once 'path/to/Db.php';
   ```

### Composerrel:

1. Telepítsd a csomagot:
```bash
composer require sosoricsi/query
```

2. Importáld a kódba:
```php
require 'path/to/vendor/autoload.php';
use SosoRicsi\Query\Db;
```

## Használat

### Adatbázis kapcsolat beállítása
Az adatbázis kapcsolat beállításához és létrehozásához az alábbi metódusokat használd:

```php
$db = new Db();
$db->setDatabase('localhost', 'username', 'password', 'mysql');
$db->connect('adatbazis_nev');
```

### Táblák kezelése és műveletek

#### Táblák kiválasztása
A `table()` metódussal adhatod meg a művelet célját képező táblát:
```php
$db->table('felhasznalok');
```

#### Oszlopok ellenőrzése
Az `columnExists()` metódussal ellenőrizheted, hogy egy adott oszlop létezik-e:
```php
if ($db->columnExists('email', 'felhasznalok')) {
    echo "Az oszlop létezik!";
}
```

#### Adatok lekérdezése
Az alábbi metódusok segítségével paraméterezheted a lekérdezéseket:
- **`select(string $field)`**: A lekérdezendő oszlopok megadása.
- **`where(string $column, string $operator, string $value)`**: Feltétel megadása az `AND` operátorral.
- **`orWhere(string $column, string $operator, string $value)`**: Feltétel megadása az `OR` operátorral.
- **`notWhere(string $column, string $operator, string $value)`**: Feltétel megadása a `NOT` operátorral.
- **`order(string $columns, string $type)`**: Sorbarendezés megadása.
- **`limit(int $limit)`**: Sorok számának korlátozása.
- **`offset(int $offset)`**: Kezdőpont megadása.

Példa lekérdezés:
```php
$eredmenyek = $db->table('felhasznalok')
    ->select('id, nev, email')
    ->where('kor', '>', '18')
    ->orWhere('aktiv', '=', '1')
    ->order('nev', 'ASC')
    ->limit(10)
    ->offset(5)
    ->get();

foreach ($eredmenyek as $felhasznalo) {
    echo $felhasznalo->nev;
}
```

#### Adatok beszúrása
Az `insert()` metódussal egyszerűen adhatsz hozzá új adatokat:
```php
$ujId = $db->table('felhasznalok')
    ->insert('nev, email, kor', ['John Doe', 'john@example.com', 25]);

echo "Új rekord ID: " . $ujId;
```

#### Adatok törlése
A `delete()` metódussal adatokat törölhetsz:
```php
$db->table('felhasznalok')
    ->where('id', '=', '5')
    ->delete();
```

#### Egyedi SQL lekérdezések
A `raw()` metódussal tetszőleges SQL parancsot hajthatsz végre:
```php
$eredmenyek = $db->raw('SELECT * FROM felhasznalok WHERE kor > ?', [18]);

foreach ($eredmenyek as $felhasznalo) {
    echo $felhasznalo->nev;
}
```

#### Csatlakozások (JOIN)
A `join()` metódussal csatlakozásokat adhatsz hozzá a lekérdezésekhez:
```php
$eredmenyek = $db->table('felhasznalok')
    ->join('INNER JOIN', 'rendelesek', 'felhasznalok.id = rendelesek.felhasznalo_id')
    ->get();
```

### Tranzakciók kezelése
Az alábbi metódusok használhatók tranzakciók kezelésére:
- **`transaction()`**: Tranzakció indítása.
- **`commit()`**: Tranzakció mentése.
- **`rollback()`**: Tranzakció visszavonása.

Példa:
```php
try {
    $db->transaction();

    $db->table('felhasznalok')->insert('nev, email', ['Jane Doe', 'jane@example.com']);
    $db->table('felhasznalok')->insert('nev, email', ['John Smith', 'john.smith@example.com']);

    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    echo "Hiba történt: " . $e->getMessage();
}
```

### Egyéb funkciók

#### Lekérdezések állapotának törlése
A `clear()` metódus automatikusan törli az aktuális lekérdezés állapotát minden új `table()` híváskor.

#### Kapcsolat bezárása
A `close()` metódussal zárhatod le az adatbázis-kapcsolatot:
```php
$db->close();
```

## Hozzájárulás
Ha hibát találsz, vagy szeretnél hozzájárulni a projekthez, nyiss egy issue-t vagy küldj egy pull requestet.

## Licenc
Ez a projekt MIT licenc alatt érhető el.
