import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import eslint from '@eslint/js';
import tseslint from 'typescript-eslint';
import jsdoc from 'eslint-plugin-jsdoc';
import jsoncPlugin from 'eslint-plugin-jsonc';
import * as jsoncParser from 'jsonc-eslint-parser';
import jsonSchemaValidator from 'eslint-plugin-json-schema-validator';
import globals from 'globals';

// --- Constants for rule configuration ---
const MAX_COMPLEXITY = 10;
const MAX_LINES = 300;
const MAX_PARAMS = 4;
const INDENT_SPACES = 2;
const SWITCH_CASE_INDENT = 1;

// --- Helper logic to read .gitignore ---
const gitignorePath = path.resolve(process.cwd(), '.gitignore');
const gitignorePatterns = fs.existsSync(gitignorePath)
  ? fs.readFileSync(gitignorePath, 'utf8')
    .split(/\r?\n/)
    .filter(line => line.trim() && !line.startsWith('#'))
  : [];
// ---------------------------------------

/** @type {import('eslint').Linter.FlatConfig[]} */
export default [
  // 1. Global ignores configuration
  {
    ignores: [
      '**/node_modules/',
      '**/vendor/',
      '**/dist/',
      '**/build/',
      '**/coverage/',
      'package-lock.json',
      'branch-protection-rules/',
      ...gitignorePatterns
    ],
  },

  // 2. Base configs
  eslint.configs.recommended,
  jsdoc.configs['flat/recommended'],
  ...tseslint.configs.recommended,

  // 3. JS/TS strict rules
  {
    files: ['**/*.{ts,tsx,js,jsx}'],

    settings: {
      jsdoc: {
        mode: 'typescript',
      },
    },

    rules: {
      // --- General JS/TS strictness ---
      'no-console': 'error',
      'eqeqeq': ['error', 'always'],
      'curly': ['error', 'all'],
      'no-var': 'error',
      'prefer-const': 'error',
      'no-magic-numbers': ['warn', { ignore: [0, 1, -1] }],
      'complexity': ['warn', MAX_COMPLEXITY],
      'max-lines': ['warn', { max: MAX_LINES, skipBlankLines: true, skipComments: true }],
      'max-params': ['error', MAX_PARAMS],
      'semi': ['error', 'always'],
      'quotes': ['error', 'single'],
      'brace-style': ['error', 'allman', { allowSingleLine: false }],
      'indent': ['error', INDENT_SPACES, { SwitchCase: SWITCH_CASE_INDENT }],

      // --- TS strictness ---
      '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
      '@typescript-eslint/explicit-function-return-type': 'error',
      '@typescript-eslint/consistent-type-imports': 'error',
      '@typescript-eslint/no-explicit-any': 'error',
      '@typescript-eslint/no-non-null-assertion': 'error',

      // --- JSDoc strictness ---
      'jsdoc/require-jsdoc': ['error', {
        publicOnly: true,
        require: {
          FunctionDeclaration: true,
          MethodDefinition: true,
          ClassDeclaration: true,
          ArrowFunctionExpression: true,
          FunctionExpression: true,
        },
      }],
      'jsdoc/require-param': 'error',
      'jsdoc/require-param-description': 'error',
      'jsdoc/require-returns': 'error',
      'jsdoc/require-returns-description': 'error',
      'jsdoc/check-alignment': 'error',
      'jsdoc/check-tag-names': [
        'error',
        {
          definedTags: ['remarks', 'example', 'defaultValue'],
        },
      ],
    },
  },

  // 4. JSON/JSONC strict rules
  {
    files: ['**/*.json', '**/*.jsonc'],
    plugins: {
      // @ts-expect-error -- Eslint plugin has incorrect types
      jsonc: jsoncPlugin,
      'json-schema-validator': jsonSchemaValidator,
    },
    languageOptions: {
      parser: jsoncParser,
    },
    rules: {
      ...jsoncPlugin.configs['recommended-with-jsonc'].rules,

      // Strict formatting
      'jsonc/indent': ['error', INDENT_SPACES],
      'jsonc/quotes': ['error', 'double'],
      'jsonc/array-bracket-spacing': ['error', 'never'],
      'jsonc/object-curly-spacing': ['error', 'always'],
      'jsonc/key-spacing': ['error', { beforeColon: false, afterColon: true }],
      'jsonc/comma-dangle': ['error', 'never'],

      // Strict correctness
      'jsonc/sort-keys': ['error', { order: { type: 'asc' }, pathPattern: '^.*$' }],
      'jsonc/no-dupe-keys': 'error',
      'jsonc/no-octal-escape': 'error',
      'jsonc/no-bigint-literals': 'error',
      'jsonc/no-numeric-separators': 'error',

      // Validate against schemas (example: package.json)
      'json-schema-validator/no-invalid': ['error', {
        schemas: [
          {
            fileMatch: ['package.json'],
            schema: 'https://json.schemastore.org/package.json'
          }
        ]
      }]
    },
  },

  // 4b. Comments are only banned in strict .json - that's the whole reason
  // the .jsonc extension exists, and eslint-plugin-jsonc's own presets
  // already draw this exact line: its `recommended-with-json` config turns
  // on `jsonc/no-comments`, its `recommended-with-jsonc` config (the one
  // section 4 above uses for both extensions) does not. Scoping the rule
  // here to `**/*.json` only, rather than pulling in a whole second preset,
  // keeps every other rule in section 4 identically applied to both
  // extensions and changes only this one rule's scope.
  {
    files: ['**/*.json'],
    rules: {
      'jsonc/no-comments': 'error',
    },
  },

  // 5. Repo-local additions - NOT part of the shared config above (sections
  // 1-4 are kept as authored, byte-for-byte, since this config is reused
  // outside this repo too). These two overrides exist because this repo's
  // actual .config/*.js|.cjs|.mjs scripts are plain Node scripts, not
  // TypeScript, and section 3 above was written with a TS codebase in
  // mind - confirmed by actually running this config against every real
  // file here rather than assuming it would just work:
  //
  // - No Node globals were declared anywhere in the shared config, so
  //   every real script calling `require`/`module`/`process`/`fetch` (all
  //   ordinary Node/CommonJS globals) failed `no-undef` immediately - not
  //   a code quality finding, just this config never having declared what
  //   environment these scripts run in.
  // - `@typescript-eslint/explicit-function-return-type` and
  //   `@typescript-eslint/no-require-imports` are both TypeScript-specific
  //   rules that section 3 also applies to plain `.js` files (its own
  //   `files` glob includes `js`/`jsx` alongside `ts`/`tsx`). The first is
  //   unsatisfiable in plain JavaScript - there's no `: ReturnType` syntax
  //   to add outside TypeScript. The second directly conflicts with
  //   verified-git-commit.plugin.cjs, which is a `.cjs` file specifically
  //   because it deliberately uses `require()`.
  //
  // If TypeScript is ever added to this repo, these two rules should stay
  // enforced for real `.ts`/`.tsx` files - that's why both overrides below
  // are scoped to `js`/`cjs`/`mjs` specifically, not applied blanket.
  {
    files: ['**/*.{js,cjs,mjs}'],
    languageOptions: {
      globals: {
        ...globals.node,
      },
    },
    rules: {
      '@typescript-eslint/explicit-function-return-type': 'off',
    },
  },
  {
    files: ['**/*.cjs'],
    rules: {
      '@typescript-eslint/no-require-imports': 'off',
    },
  },
];