import js from '@eslint/js'
import tsPlugin from '@typescript-eslint/eslint-plugin'
import tsParser from '@typescript-eslint/parser'

export default [
  js.configs.recommended,
  {
    files: ['Resources/Private/JavaScript/**/*.ts'],
    languageOptions: {
      parser: tsParser,
      parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module',
        project: './tsconfig.json'
      },
      globals: {
        window: 'readonly',
        document: 'readonly',
        console: 'readonly',
        fetch: 'readonly',
        HTMLElement: 'readonly',
        HTMLImageElement: 'readonly',
        HTMLPictureElement: 'readonly',
        HTMLSourceElement: 'readonly',
        Element: 'readonly',
        ResizeObserver: 'readonly',
        IntersectionObserver: 'readonly'
      }
    },
    plugins: {
      '@typescript-eslint': tsPlugin,
    },
    rules: {
      // TypeScript specific rules
      '@typescript-eslint/explicit-function-return-type': 'error',
      '@typescript-eslint/no-explicit-any': 'error',
      '@typescript-eslint/no-unused-vars': 'error',
      '@typescript-eslint/no-non-null-assertion': 'error',
      '@typescript-eslint/naming-convention': [
        'error',
        {
          selector: 'interface',
          format: ['PascalCase'],
        },
        {
          selector: 'class',
          format: ['PascalCase'],
        },
      ],
      'no-undef': 'off', // TypeScript handles this better

      // Formatting rules
      'semi': ['error', 'never'],
      'quotes': ['error', 'single'],
      'indent': ['error', 2],
      'max-len': ['error', { 'code': 120 }],
      'comma-dangle': ['error', 'always-multiline'],
      'object-curly-spacing': ['error', 'never'],
      'arrow-parens': ['error', 'as-needed'],
      'no-multiple-empty-lines': ['error', { 'max': 1 }],
      'eol-last': ['error', 'always'],
    },
  },
]
