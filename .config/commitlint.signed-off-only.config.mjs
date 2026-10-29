// Companion to commitlint.pr-message.config.mjs, but inverted: this one
// keeps ONLY the two signed-off-by rules from the base config and turns
// everything else off, instead of the other way around.
//
// Used by pr-title-lint.yaml's "Lint signed-off-by (full commit range)"
// step to check every individual commit in a feature/* PR's range - not
// just the PR's assembled title+body - because GitHub carries each
// squashed commit's OWN Signed-off-by trailer forward into the final
// squash commit at merge time, not just the tip commit's. preview.yaml's
// "Lint last commit (feature/*)" step only ever checks the tip (--last),
// so a non-tip commit missing a valid signature would otherwise reach
// develop's history carried-forward and unverified. See pr-title-lint.yaml's
// header comment for the full explanation.
//
// Deliberately does NOT extend commitlint.config.mjs's `extends` chain
// (e.g. @commitlint/config-conventional) or copy its `rules` wholesale -
// only signed-off-by-regex and signed-off-by are pulled across, read
// directly off the base config's own `rules` object so this file can
// never drift out of sync with whatever that regex/severity actually is.
// Every other rule (type/scope/subject/body/header-length/etc.) is simply
// absent here, which is what "off" means to commitlint - not present, not
// inherited from an extends chain we deliberately don't include.
//
// parserPreset/plugins ARE still carried over: signed-off-by-regex and
// signed-off-by are themselves provided by a local plugin (see
// commitlint.config.mjs), and correctly locating the Signed-off-by
// trailer in a commit's footer at all still depends on the same parser
// preset the base config uses.
import base from './commitlint.config.mjs';

export default {
  parserPreset: base.parserPreset,
  plugins: base.plugins,
  rules: {
    'signed-off-by-regex': base.rules['signed-off-by-regex'],
    'signed-off-by': base.rules['signed-off-by'],
  },
};
