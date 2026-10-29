////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//                                             ISSUE REFERENCE KEYWORD RULE                                           //
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/* This function is a custom commitlint plugin restricting which keyword may introduce an issue reference (e.g. "#123") in a commit message, to exactly the three this project recognises: Close, Fix, Reference. */

// 1. Export default.
// 2. Define the exact, case-sensitive set of allowed keywords - no other word, and no inflected form of these three
//    (Closes/Closed, Fixes/Fixed, References/Referenced all fail), is accepted.
// 3. Split the raw commit message into lines. Any line containing a "#123"-style issue reference must itself START
//    with one of the allowed keywords (this is deliberately a per-LINE check, not per-reference: it lets one keyword
//    introduce a comma-separated list of issues on the same line, e.g. "Reference #123, #124", the same way
//    "Signed-off-by:" already introduces its own dedicated line elsewhere in this config).
// 4. Return an error listing every offending line, if any.

// Export Default

const ALLOWED_KEYWORDS = ['Close', 'Fix', 'Reference'];

export default
{
  rules:
  {
    /**
     * Custom commitlint rule restricting which keyword may introduce an issue reference.
     * @author                        NinjaMonkeyGames
     * @since                         0.1.0
     * @version                       0.1.0
     * @description                 - Every line that mentions a "#123"-style issue reference must start with exactly
     *                                 one of "Close", "Fix", or "Reference" - case-sensitive, and no other form (not
     *                                 "Closes", "closed", "Resolves", "references", etc.), and a bare "#123" with no
     *                                 keyword at all also fails. This is deliberately stricter than GitHub's own
     *                                 auto-close keyword list, which also accepts "Resolves" and every inflected
     *                                 form - see CONTRIBUTING.md's "Closing issues" section for why this project
     *                                 narrows it down to just these three.
     * @param {object} params       - Parameters provided by commitlint.
     * @param {string} params.raw   - The raw commit message.
     * @returns {Array}             - Returns [true] if valid or [false, error message] if invalid.
     */

    'issue-reference-keyword': ({ raw }) =>
    {
      // Matches "#123" anywhere in a line, and separately whether that same
      // line (once leading whitespace/list-dashes are trimmed) starts with
      // one of the allowed keywords, immediately followed by an optional
      // colon, whitespace, then the reference itself.

      const issueRefPattern = /#\d+/;
      const allowedLineStart = new RegExp(`^(?:${ALLOWED_KEYWORDS.join('|')}):?\\s+#\\d+`);

      const offendingLines = raw
        .split('\n')
        .map((line) => line.replace(/^[\s*-]+/, ''))
        .filter((line) => issueRefPattern.test(line) && !allowedLineStart.test(line));

      if (offendingLines.length === 0)
      {
        return [true];
      }

      // Return an error naming every offending line, so a violation caused
      // by the wrong keyword (or no keyword at all) is easy to find even
      // when it's buried in a long commit body.

      return [
        false,
        `Every issue reference must start its own line with exactly one of ${ALLOWED_KEYWORDS.join(', ')} (case-sensitive, no other form such as "Closes"/"Fixed"/"Resolves", and not a bare "#123"). Offending line(s): ${offendingLines.map((line) => JSON.stringify(line)).join(', ')}`,
      ];
    },
  },
};