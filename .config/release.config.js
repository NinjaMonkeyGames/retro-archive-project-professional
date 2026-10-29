// semantic-release config, loaded by release.yaml via:
//   npx semantic-release --extends ./.config/release.config.js
// (semantic-release only auto-discovers config at the repo root, hence
// --extends rather than a root-level .releaserc).
//
// package.json has "type": "module", so this file is loaded as an ES
// module (a plain top-level `export default`, no .mjs extension needed).
//
// Branch -> channel mapping, matching detect-environment.yaml /
// release.yaml exactly:
//   master     -> stable release, "latest" dist-tag / no prerelease suffix
//   develop    -> beta prerelease (e.g. 1.4.0-beta.1)
//   release/*  -> rc prerelease   (e.g. 1.4.0-rc.1)
// hotfix/* is deliberately NOT listed here, same as release.yaml's own
// trigger: hotfix branches don't get a semantic-release run at all. If
// that changes, add it in both files at once - see release.yaml's
// comment on this.
//
// Commit parsing / release notes both use the Conventional Commits
// preset (conventional-changelog-conventionalcommits), the same preset
// commitlint is configured against elsewhere in this repo - one commit
// grammar, not two.
//
// `release/*` here matches EVERY branch in the repo that fits the glob,
// not just a single intended release branch, and semantic-release
// resolves the full `branches` array on every run regardless of which
// branch triggered it. This config assumes exactly one release/* branch
// exists at a time: two coexisting release/* branches both resolve to
// the same fixed `prerelease: 'rc'` identifier, which semantic-release
// refuses outright (EPRERELEASEBRANCHES - it needs a unique identifier
// per branch to know which branch a version like 1.0.0-rc.1 belongs to),
// and because branch validation runs unconditionally, one stray leftover
// release/* branch breaks every future release.yaml run repo-wide, not
// just release/* ones, until it's deleted. Nothing in
// detect-environment.yaml/deploy.yaml/branch-protection.yaml
// distinguishes multiple concurrent release/* branches either, so this
// is enforced only by convention. Enable "Automatically delete head
// branches" in repo Settings > General so a merged release/*|hotfix/*
// branch can't linger and silently reintroduce this.
export default {
  branches: [
    'master',
    { name: 'develop', channel: 'beta', prerelease: 'beta' },
    { name: 'release/*', channel: 'rc', prerelease: 'rc' },
  ],

  plugins: [
    // Determines the next version bump (major/minor/patch/none) from
    // commits since the last release.
    ['@semantic-release/commit-analyzer', { preset: 'conventionalcommits' }],

    // Builds this release's notes (used for the GitHub release body and
    // fed into the commit message below via ${nextRelease.notes}).
    ['@semantic-release/release-notes-generator', { preset: 'conventionalcommits' }],

    // Prepends this release's notes to CHANGELOG.md and bumps
    // package.json's version field on disk - but ONLY on the stable
    // channel (master). This wraps @semantic-release/changelog and
    // @semantic-release/npm (npmPublish stays false inside the wrapper -
    // this is a game project, not something published to a package
    // registry, only the version bump itself is wanted) rather than
    // listing them directly, specifically so develop/release/*'s
    // prereleases stop rewriting these same two files - see the plugin's
    // own header comment for why: master and develop each independently
    // bumping them made every sync-master-to-develop.yaml PR hit a
    // guaranteed merge conflict, one GitHub's own merge button can't
    // auto-resolve.
    './.config/stable-only-assets.plugin.js',

    // Commits CHANGELOG.md + package.json (+ package-lock.json /
    // npm-shrinkwrap.json if present) and pushes that commit as a
    // Verified commit via the GitHub API, authenticated as nmg-bot.
    // Replaces @semantic-release/git - see that file's header comment
    // for why a plain git push can never be Verified here, regardless
    // of which token authenticates it.
    //
    // assets/message are spelled out explicitly even though they match
    // this plugin's own defaults, so the commit's actual shape is
    // visible here without having to open the plugin file.
    [
      './.config/verified-git-commit.plugin.cjs',
      {
        assets: ['package.json', 'package-lock.json', 'CHANGELOG.md'],
        // No "[skip ci]" - see the plugin's own header comment for why:
        // it suppressed CI for any future push whose tip is this commit,
        // not just this one, which is a bigger problem than the
        // redundant-redeploy it was meant to prevent. deploy.yaml and
        // release.yaml key off commit authorship instead now.
        message: 'chore(release): ${nextRelease.version}\n\n${nextRelease.notes}',
      },
    ],

    // Creates the GitHub release + tag pointing at the commit the
    // plugin above just created.
    //
    // successComment: false disables its separate behaviour of scanning
    // released commits for "Closes #N"/"Fixes #N" references and
    // commenting on each resolved issue/PR. That lookup is a best-effort
    // convenience, not part of actually publishing the release - by the
    // time it runs, the tag and GitHub release already exist - but by
    // default a single unresolvable reference (issue deleted, wrong
    // number, a stray test commit, anything) throws and makes
    // semantic-release report the ENTIRE run as failed, even though the
    // release itself published fine. Disabling it trades away the
    // automatic "released in vX.Y.Z" issue comment for not letting that
    // one convenience feature take down an otherwise-successful release.
    //
    // failComment: false is the same trade on the opposite path: on a
    // validation failure (e.g. EPRERELEASEBRANCHES, see above), this
    // plugin's own "fail" step - which tries to comment on referenced
    // issues saying the release failed - can throw its own unrelated
    // error before the real error is ever shown, since some failures
    // happen before this plugin has everything it needs to build that
    // comment. A crash there would print first and obscure the actual
    // cause underneath it. Same reasoning as successComment: a
    // best-effort convenience comment isn't worth letting it make a real
    // failure harder to read, let alone risk masking one entirely.
    ['@semantic-release/github', { successComment: false, failComment: false }],
  ],
};