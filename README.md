# GAMEMAKER PROJECT TEMPLATE

<!-- markdownlint-disable MD013 -->
[![Common Changelog](https://common-changelog.org/badge.svg)](https://common-changelog.org)
![Signed Commits](https://img.shields.io/badge/commits-signed-blue.svg)
![Conventional Commits](https://img.shields.io/badge/Conventional%20Commits-1.0.0-yellow.svg)
![GitHub Release](https://img.shields.io/github/v/release/NinjaMonkeyGames/retro-archive-project-professional)
<!-- markdownlint-enable MD013 -->
---

## TABLE OF CONTENTS

- [GAMEMAKER PROJECT TEMPLATE](#gamemaker-project-template)
  - [TABLE OF CONTENTS](#table-of-contents)
  - [SUMMERY DESCRIPTION](#summery-description)
  - [WHAT IS THE PURPOSE OF THIS PROJECT ?](#what-is-the-purpose-of-this-project-)
  - [WHO IS THIS REPOSITORY FOR ?](#who-is-this-repository-for-)
  - [API OVERVIEW](#api-overview)
  - [ENVIRONMENT DEPENDENCY MANIFESTO](#environment-dependency-manifesto)
    - [GAMEMAKER](#gamemaker)
    - [IDE](#ide)
      - [VSC (Visual Studio Codium)](#vsc-visual-studio-codium)
      - [VSC EXTENSIONS](#vsc-extensions)
    - [CI TOOLS](#ci-tools)
    - [CI TOOL PLUGINS](#ci-tool-plugins)
    - [SUPPORTING TOOLS](#supporting-tools)
  - [INSTALLATION](#installation)
  - [USAGE](#usage)
  - [SUPPORTING DOCUMENTATION](#supporting-documentation)
  - [KNOWN ISSUES](#known-issues)
  - [CONTACT INFORMATION](#contact-information)
  - [COPYRIGHT](#copyright)

---

## SUMMERY DESCRIPTION

This project aims to create a definitive, web-based archive and knowledge base for video game preservation and tracking
across all gaming consoles.

Core Objectives:

    Comprehensive Console Library: Build a complete digital directory covering every game console in history. For modern
    platforms, this includes exhaustive tracking of game releases, system updates, patches, and post-launch content.

    Trophies & Achievements Integration: Expand the repository to detail unlockable achievements, trophies, and
    downloadable content (DLC) across all supporting platforms and expansions.

    Interactive Web Platform: Develop an intuitive, user-friendly website that serves as a central hub for users to
    navigate, search, and explore the entire database effortlessly.

---

## WHAT IS THE PURPOSE OF THIS PROJECT ?

Preserve Gaming History: Safeguard the legacy of video games by systematically documenting software, updates, and
meta-data before physical and digital assets are lost over time.

Ensure Long-Term Digital Longevity: Serve as a permanent offline-capable archive for future generations, ensuring
critical gaming data remains accessible long after official network services and platform servers go offline.

Unify Scattered Information: Consolidate disconnected databases, patch histories, and achievement registries from across
the web into a single, authoritative repository.

---

## WHO IS THIS REPOSITORY FOR ?

This project is for anyone who is interested in preserving gaming history.

---

## API OVERVIEW

API info here...

## ENVIRONMENT DEPENDENCY MANIFESTO

### GAMEMAKER

- GameMaker version: `2026.0.0.16`
- Runtime version: `2026.0.0.23`
- Target platforms: Windows, Linux

### IDE

#### VSC (Visual Studio Codium)

Version: 1.121.03429
Commit: 824c4c46a288b839f13b24022655329c2aeb9f81
Date: 2026-05-19T23:32:58Z
Electron: 39.8.8
ElectronBuildId: undefined
Chromium: 142.0.7444.265
Node.js: 22.22.1
V8: 14.2.231.22-electron.0
OS: Linux x64 6.12.105+deb13-amd64

#### VSC EXTENSIONS

| Extension Name                                                    | Version   |
| ----------------------------------------------------------------- | --------- |
| streetsidesoftware.code-spell-checker                             | 4.7.3     |
| streetsidesoftware.code-spell-checker-cspell-bundled-dictionaries | 2.0.15    |
| github.vscode-github-actions                                      | 0.32.3    |
| dbaeumer.vscode-eslint                                            | 3.0.34    |
| yzhang.markdown-all-in-one                                        | 3.6.2     |
| davidanson.vscode-markdownlint                                    | 0.62.1    |
| redhat.vscode-yaml                                                | 1.24.0    |
| joshbolduc.commitlint                                             | 2.6.3     |
| timonwong.shellcheck                                              | 0.40.0    |
| ms-azuretools.vscode-docker                                       | 2.0.0     |
| ms-azuretools.vscode-containers                                   | 2.4.5     |
| stylelint.vscode-stylelint                                        | 2.2.1     |
| johnrdorazio.vscode-phpcs                                         | 1.4.1     |

### CI TOOLS

| Tool                                  | Version                           |
|---------------------------------------|-----------------------------------|
| npm                                   | 11.18.0                           |
| cSpell                                | 10.3.0                            |
| eslint                                | 10.11.0                           |
| Markdownlint-Cli2                     | 0.23.0                            |
| Markdownlint                          | 0.41.0                            |
| Commitlint                            | 21.2.2                            |
| Commitlint config-conventional        | 21.2.2                            |
| cSpell                                | 10.3.0                            |
| semantic-release                      | 25.0.9                            |
| htmlhint                              | 11.18.0                           |
| stylelint                             | 40.0.0                            |
| PHP_CodeSniffer                       | 3.13.6                            |

    "@semantic-release/changelog": "^7.0.0",
    "@semantic-release/commit-analyzer": "^13.0.1",
    "@semantic-release/github": "^12.0.9",
    "@semantic-release/npm": "^13.1.5",
    "@semantic-release/release-notes-generator": "^14.1.1",

    "eslint": "^10.11.0",
    "eslint-plugin-jsdoc": "^63.3.3",
    "eslint-plugin-json-schema-validator": "^6.3.1",
    "eslint-plugin-jsonc": "^3.4.2",
    "globals": "^17.12.0",
    "i": "^0.3.7",
    "jsonc-eslint-parser": "^3.3.0",

### CI TOOL PLUGINS

| Tool                                      | Version                           |
|-------------------------------------------|-----------------------------------|
| @semantic-release/changelog               | 7.0.0                             |
| @semantic-release/commit-analyzer         | 13.0.1                            |
| @semantic-release/github                  | 12.0.9                            |
| @semantic-release/npm                     | 13.1.5                            |
| @semantic-release/release-notes-generator | 14.1.1                            |
| eslint-plugin-jsdoc                       | 63.3.3                            |
| eslint-plugin-json-schema-validator       | 6.3.1                             |
| eslint-plugin-jsonc                       | 3.4.2                             |
| jsonc-eslint-parser                       | 3.3.0                             |
| globals                                   | 0.3.7                             |
| htmlhint.vscode-htmlhint                  | 1.17.0                            |

### SUPPORTING TOOLS

Local tool versions.

| Tool                           | Version               |
|--------------------------------|-----------------------|
| NPM                            | 11.18.0               |
| Node                           | 24.11.1               |
| Composer                       | 2.8.8                 |
| PHP                            | 8.4.27                |
| GitHub Desktop                 | 3.4.9-linux1 (x64)    |
| Git                            | 2.47.3-0+deb13u1      |

## INSTALLATION

1. N/A (To be confirmed...)

## USAGE

Provide example API usage examples here...

## SUPPORTING DOCUMENTATION

- CONTRIBUTING.md
- CHANGELOG.md
- DEVELOPER.md
- LICENSE.md
- README.md

## KNOWN ISSUES

There are currently no known limitations or bugs...

## CONTACT INFORMATION

Author: Daniel Mallett (Monkey Knuckles)

If you have any problems with the repository or have any suggestions please contact us at <info@NinjaMonkeyGames.com>.

You may also contact us via our [website](https://NinjaMonkeyGames.com).

Any bugs should be raised as an [issue](https://github.com/NinjaMonkeyGames/grid-utility-professional/issues) on
GitHub.

---

## COPYRIGHT

*NinjaMonkeyGames™ Copyright © 2026 All rights reserved.*
