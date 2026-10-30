# Retro Gaming Archival Database — Developer Documentation

A museum-grade, highly normalized archival database for retro video game preservation.
This system strictly decouples master intellectual property (IP) and game entities
from physical regional releases, box variants, and ROM binaries.

## Architecture and Environment

* **Stack:** PHP 8+ / MySQL 8.0 (running in Docker containers).
* **Environment Strategy:** **Ephemeral-First.** To ensure clean environment builds
  and zero state pollution, the database container (`lamp_db`) is intentionally
  un-volumed.
* **Workflow:** Tearing down the containers (`docker compose down -v`) completely
  wipes the database, allowing `build-db.php` to rebuild the schema and seed raw
  data from scratch on every fresh boot.

## Database Normalization Schema

The database relies on a relational model where master abstract game entities link
via foreign keys to standardized lookup tables, preventing redundant data strings.

### Lookup and Master Tables

* **`origins`**: Stores country/regional origin metadata (e.g., Japan, United States),
  ISO shortcodes, and flag asset links.
* **`franchises`**: Stores IP franchise data, original creators, inception years,
  and historical notes.
* **`genres`**: Normalized categorization of game mechanics (e.g., Puzzle).
* **`licenses`**: Tracks legal release classifications (e.g., Official Release,
  Unlicensed, Public Domain).
* **`games` (Master Entity)**: Represents the core game concept. Links to `origins`,
  `franchises`, `genres`, and `licenses` via foreign keys (`BIGINT UNSIGNED`). Also
  tracks museum-grade metadata such as Wikidata QIDs (`wikidata_qid`), canonical
  titles, original media types, and review statuses.

## Project File Structure

```text
project-files/
├── docker-compose.yml       # Defines the LAMP / MySQL 8.0 container stack (ephemeral)
├── sql-connect.php          # Centralized PDO database connection helper
├── build-db.php             # Automated schema migration, self-patching, and JSON seeder
├── index.php                # Visual administrative debug dashboard
└── db-data/
    └── game-data.json       # Master seed dataset (strictly sorted alphabetical keys)