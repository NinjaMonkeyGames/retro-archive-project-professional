# Security Policy

This covers vulnerability reporting for the CI/CD pipeline this repository provides — the GitHub Actions workflows,
semantic-release configuration, and supporting scripts under `.config/` and `.github/`. It doesn't cover the
GameMaker project itself; that's your own game's code to triage.

## Supported Versions

This repository releases from a single rolling `master` branch via semantic-release — there are no long-term-support
branches. Only the latest published release (and the current `master` tip) receive security fixes; older tags aren't
patched.

## Reporting a Vulnerability

If you find a security issue in this pipeline — for example, a workflow that could leak a secret, a way to trigger an
unintended deploy, or a privilege-escalation path through the `nmg-bot` GitHub App's permissions — please report it
privately rather than opening a public issue.

- **Preferred, if enabled on this repo:** use GitHub's own private reporting — the **Security** tab →
  **Report a vulnerability**. This button only appears once "Private vulnerability reporting" has been turned on for
  the repository (Settings → Security → Private vulnerability reporting) — it isn't on by default, so if you don't
  see it, use the email address below instead.
- **Always available:** email <info@ninjamonkeygames.com> with details and, if possible, steps to reproduce.

Please don't include real secrets (tokens, private keys) in a report — describe the exposure and how to reproduce it
instead, and we'll follow up to confirm and rotate anything affected.

## Out of Scope

- The GameMaker project itself (your own game's code) — this policy only covers the pipeline these files provide.
- Vulnerabilities in third-party GitHub Actions or npm packages this pipeline depends on — please also report those
  directly to their own maintainers, though letting us know is appreciated so we can pin an update.
