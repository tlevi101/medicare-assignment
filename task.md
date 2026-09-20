# Backend tesztfeladat — Időpont-foglalási rendszer API

## Áttekintés

Készíts egy REST API-t Laravel keretrendszerrel egy **orvosi rendelő időpont-foglalási rendszeréhez**.

A rendszerben **orvosok** és **páciensek** vannak. Az orvosok megadják, mikor érhetők el (rendelési idő), a páciensek pedig ezekre az időpontokra foglalhatnak vizitet. A foglalásoknak életciklusa van — létrehozástól a teljesítésig vagy lemondásig.

Csak a backend API-t kell megvalósítani, frontend nem szükséges.

---

## Entitások

### Orvos (Doctor)

- Név
- Email (egyedi)
- Szakterület (pl. „háziorvos", „bőrgyógyász")

### Páciens (Patient)

- Név
- Email (egyedi)
- Telefonszám

### Rendelési idő (Availability)

Egy orvoshoz tartozó időablak, amelyre foglalás tehető.

- Orvos (belongsTo)
- Kezdő időpont (`starts_at`)
- Záró időpont (`ends_at`)
- Slot időtartam percben (pl. 30 perc) — ez határozza meg, hány foglalás fér bele

### Foglalás (Appointment)

- Páciens (belongsTo)
- Orvos (belongsTo)
- Időpont kezdete (`start_time`)
- Időpont vége (`end_time`)
- Státusz: `pending` → `confirmed` → `completed` | `cancelled`
- Lemondás oka (nullable, csak cancelled esetén)

---

## Üzleti szabályok

### Rendelési idők

1. Orvos létrehozhat rendelési időt a jövőre vonatkozóan.
2. Rendelési idők nem fedhetik egymást ugyanannál az orvosnál.
3. Minimum időtartam: 30 perc.

### Foglalás

4. Páciens csak **szabad slotra** foglalhat (az adott slot nincs más foglalás által lefedve).
5. Foglalás csak a **jövőre** vonatkozhat — múltbeli időpontra nem foglalható.
6. Egy páciens **nem foglalhat két időpontot ugyanarra az időre** (még különböző orvosoknál sem).
7. Foglalás létrehozásakor a státusz `pending`.

### Állapotátmenetek

8. `pending` → `confirmed`: az orvos megerősíti a foglalást.
9. `pending` → `cancelled`: a páciens vagy az orvos lemondja.
10. `confirmed` → `completed`: az orvos lezárja a vizitet.
11. `confirmed` → `cancelled`: lemondható, **de csak legalább 24 órával a foglalt időpont előtt**.
12. `completed` és `cancelled` végállapotok — további átmenet nem lehetséges.

### Listázás, szűrés

13. Elérhető (szabad) slotok listázása orvos és/vagy dátumtartomány szerint, lapozhatóan.
14. Páciens foglalásainak listázása, szűrhetően státusz szerint.

---

## Elvárt API végpontok

Az alábbi végpontok az elvárt minimumot jelentik. A route-ok elnevezése és csoportosítása a jelölt döntése.

| Művelet | Leírás |
|---|---|
| Orvos CRUD | Orvosok létrehozása, listázása |
| Páciens CRUD | Páciensek létrehozása, listázása |
| Rendelési idő kezelése | Orvos rendelési idejének létrehozása, listázása |
| Szabad slotok lekérdezése | Adott orvos elérhető időpontjainak listázása |
| Foglalás létrehozása | Páciens időpontot foglal |
| Foglalás státuszváltás | `confirm`, `complete`, `cancel` műveletek |
| Páciens foglalásai | Egy páciens foglalásainak listázása szűrőkkel |

---

## Technikai követelmények

- **PHP** 8.3+
- **Laravel** 11+
- **Adatbázis**: MySQL, MariaDB vagy SQLite
- **Autentikáció**: a feladatban nem szükséges (az API publikus végpontokkal dolgozik)
- **API válaszformátum**: JSON, konzisztens struktúrában
- **Tesztelés**: Feature tesztek a kritikus üzleti logikára (minimum 5 teszteset, de a lefedettség fontosabb a számnál)

---

## Értékelési szempontok

Az alábbi szempontok alapján értékeljük a beadott megoldást:

1. **Adatbázis-tervezés** — Migrációk, indexek, típusválasztás
2. **Üzleti logika szervezése** — Hol él a logika? Controller, Service, Model, Rule?
3. **Validáció** — Input validáció teljessége, egyedi szabályok kezelése
4. **Állapotgép** — Hogyan van megoldva a státuszváltás és az érvénytelen átmenetek tiltása
5. **Tesztek** — Lefedettség, edge case-ek, factory használat, olvashatóság
6. **API design** — Route struktúra, HTTP verb-ök, válaszformátum, hibakezelés
7. **Kódminőség** — Olvashatóság, Laravel konvenciók betartása, SOLID elvek

---

## Beadandó

- Laravel projekt (Git repó, vagy zip — `.env` és `vendor/` nélkül)
- Működő migrációk (`php artisan migrate` hiba nélkül lefusson)
- Seed opcionális, de javasolt a tesztelés megkönnyítésére
- `README.md` a telepítés és használat leírásával

---

## Időkeret

A feladat beadási határideje a kiküldéstől számított **5 munkanap**. Várt ráfordítás: **4–6 óra**.

Sok sikert!
