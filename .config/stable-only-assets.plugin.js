// Wraps @semantic-release/changelog and @semantic-release/npm so their
// `prepare` step - the part that actually rewrites CHANGELOG.md and
// package.json's version field on disk - only runs on the stable
// channel (master, i.e. context.branch.prerelease is falsy).
//
// Plain .js, not .cjs: package.json has "type": "module" and both
// wrapped packages ship as ESM-only with named exports (no default
// export, no CJS build) - confirmed locally: requiring either of them
// from a .cjs file throws ERR_REQUIRE_ASYNC_MODULE, since Node can't
// synchronously require() an ESM module with top-level await. `import`
// is what actually works here, same as release.config.js's own module
// style.
//
// Why this exists: every branch in release.config.js's `branches` array
// runs semantic-release independently, and until now every one of them
// - including develop's betas and release/*'s rcs, not just master's
// real releases - ran these two plugins' `prepare` step. That meant
// master and develop each independently rewrote the exact same lines in
// package.json/CHANGELOG.md, so sync-master-to-develop.yaml's PR hit an
// unavoidable merge conflict on those two files on essentially every
// sync (CONFIRMED LIVE - not occasional, since develop cuts betas
// continuously). GitHub's own "Merge pull request" button can't be
// taught to auto-resolve this either - it ignores .gitattributes merge
// strategies (merge=union and friends) entirely, undocumented but
// confirmed via GitHub staff replies and long-standing community
// reports - so this was landing as a genuine "resolve via the command
// line" chore on every single sync, not a one-off.
//
// Fix: only a non-prerelease branch (master here) ever writes to these
// two files now. develop and release/* still get a real GitHub release
// + tag for every beta/rc exactly as before - commit-analyzer,
// release-notes-generator, and the GitHub release step in
// release.config.js are all untouched by this - they just stop touching
// package.json/CHANGELOG.md, so there's nothing left for master and
// develop to conflict on when syncing. As a side effect, prerelease
// branches also stop producing a "chore(release): ..." bot commit at
// all (verified-git-commit.plugin.cjs already no-ops when none of its
// configured assets actually changed), which means one less noisy
// Deploy/Release run pair per prerelease too.
//
// verifyConditions/publish/addChannel from both plugins are delegated
// unconditionally, on every branch - none of them touch package.json's
// version field or CHANGELOG.md, and npm's publish/addChannel are
// already a real no-op regardless of branch given npmPublish: false (see
// the logged "Skip publishing to npm registry as npmPublish is false" on
// every release, prerelease or not) - nothing about that changes here.
//
// pluginConfig here is release.config.js's own entry for THIS plugin
// (whatever object, if any, is passed alongside its path in the plugins
// array) - it is intentionally NOT threaded through to the wrapped
// npm/changelog plugins below, since their options are fixed by this
// file (NPM_CONFIG) rather than configurable per-repo; add real options
// passthrough here later only if a real need for it shows up.

import * as changelog from '@semantic-release/changelog';
import * as npm from '@semantic-release/npm';

const NPM_CONFIG = { npmPublish: false };

/**
 * Verifies conditions for both changelog and npm plugins.
 * @param {Record<string, unknown>} pluginConfig - Configuration options for the plugin.
 * @param {object} context - Semantic-release context object.
 * @returns {Promise<void>} A promise that resolves when verification is complete.
 */
export async function verifyConditions(pluginConfig, context) 
{
  await changelog.verifyConditions(pluginConfig, context);
  await npm.verifyConditions(NPM_CONFIG, context);
}

/**
 * Prepares the release by updating assets (package.json and CHANGELOG.md)
 * only on stable (non-prerelease) branches.
 * @param {Record<string, unknown>} pluginConfig - Configuration options for the plugin.
 * @param {object} context - Semantic-release context object.
 * @param {object} context.branch - Branch details currently being released.
 * @param {boolean} context.branch.prerelease - Indicates whether the current branch is a prerelease.
 * @param {string} context.branch.name - Name of the branch.
 * @param {object} context.logger - Logger interface provided by semantic-release.
 * @param {object} context.nextRelease - Details about the next release.
 * @param {string} context.nextRelease.channel - Channel name for the next release.
 * @returns {Promise<void>} A promise that resolves when the prepare step finishes.
 */
export async function prepare(pluginConfig, context) 
{
  if (context.branch.prerelease) 
  {
    context.logger.log(
      'stable-only-assets: skipping package.json/CHANGELOG.md changes on prerelease branch "%s" (channel "%s") - only master writes these, so they never conflict when this branch is later synced back into develop.',
      context.branch.name,
      context.nextRelease.channel,
    );
    return;
  }

  await changelog.prepare(pluginConfig, context);
  await npm.prepare(NPM_CONFIG, context);
}

/**
 * Publishes the package via the npm plugin.
 * @param {Record<string, unknown>} pluginConfig - Configuration options for the plugin.
 * @param {object} context - Semantic-release context object.
 * @returns {Promise<unknown>} A promise that resolves with the publish result.
 */
export async function publish(pluginConfig, context) 
{
  return npm.publish(NPM_CONFIG, context);
}

/**
 * Adds a channel for the package via the npm plugin.
 * @param {Record<string, unknown>} pluginConfig - Configuration options for the plugin.
 * @param {object} context - Semantic-release context object.
 * @returns {Promise<unknown>} A promise that resolves when the channel is added.
 */
export async function addChannel(pluginConfig, context) 
{
  return npm.addChannel(NPM_CONFIG, context);
}