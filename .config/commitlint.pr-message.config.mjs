// Companion to commitlint.config.mjs, used ONLY by pr-title-lint.yaml's
// lint-pr-message job to check a feature/* -> develop PR's assembled
// title + body. Extends the base config unchanged except for turning off
// two rules - see pr-title-lint.yaml's header comment for the full
// "CONFIRMED LIVE" explanation of why.
//
// Short version: GitHub auto-fills a PR's description from a single-
// commit branch's commit, but strips the Signed-off-by trailer out of
// that auto-fill specifically - not because it's missing, but because
// GitHub re-attaches recognised trailers (Signed-off-by, Co-authored-by)
// to the final squash commit automatically from the source commit(s),
// regardless of what the PR description says. Requiring it in the PR
// body too (to pass a check here) doesn't add coverage - the source
// commit already has it, and that commit is already fully linted,
// signed-off-by included, by preview.yaml's "Lint last commit
// (feature/*)" step on every push. It only produces a second, duplicate
// copy of the same trailer once GitHub carries the real one forward at
// squash time.
//
// Only signed-off-by-regex and signed-off-by are disabled here. Every
// other rule - type/scope/subject/body/footer-shape/references/etc. -
// stays exactly as the base config defines it: those rules DO need to
// run against the PR's title and body, since that (not the discarded
// commits) is what the squash commit's own header and body end up
// being, per pr-title-lint.yaml's own header comment.
import base from './commitlint.config.mjs';

export default {
  ...base,
  rules: {
    ...base.rules,
    'signed-off-by-regex': [0, 'always'],
    'signed-off-by': [0, 'always'],
  },
};
