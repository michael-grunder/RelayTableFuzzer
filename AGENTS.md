# PHP Agent Guidelines

These guidelines define the default standard for AI agents working on PHP
projects. Prefer idiomatic PHP, clear design, and measurable correctness over
cleverness or unnecessary abstraction.

## Scope and Priorities

- Keep the solution focused on the user's request.
- Prefer maintainability and correctness first, then optimize when the workload
  justifies it.
- If the project uses Composer, prefer existing Composer packages over bespoke
  implementations when they materially reduce code size, complexity, or risk.
- Avoid speculative architecture and unnecessary dependencies.

## Project Tooling

- If the project uses Composer, use Composer for dependency management and
  project scripts.
- Prefer Composer-installed tools over global binaries when both are available.
- If PHPStan is present in the project, run it and address relevant issues
  before considering a change complete.
- If PHPUnit is present in the project, run the tests and prefer finishing only
  when they pass.
- A change is not done until the relevant static analysis and test suite pass,
  unless the user explicitly asks otherwise or the environment prevents it.

## Language Version and Syntax

- Use the most modern PHP syntax the project's `composer.json` supports.
- Determine the target PHP version from `composer.json` when Composer metadata
  is available.
- If Composer is not being used, assume modern PHP and write current idiomatic
  syntax unless the user or repository conventions require older compatibility.
- Prefer modern language features when supported, such as typed properties,
  constructor property promotion, enums, readonly properties, match expressions,
  nullsafe access, and first-class callable syntax.
- Do not introduce syntax that exceeds the project's declared PHP version.

## Style and Conventions

- Follow idiomatic PHP and the repository's existing conventions.
- Use descriptive names for functions, classes, interfaces, traits, variables,
  and methods.
- Use spaces for indentation, never tabs.
- Avoid redundant comments that restate obvious code.
- Keep comments and docs aligned with current behavior.
- Prefer consistent formatting enforced by the project's existing tooling.

## Design

- Keep the codebase DRY and avoid duplicating identical logic.
- Any generic operation that spans more than a few lines should usually be
  extracted into a shared helper, service, utility, or private method that fits
  the local architecture.
- Prefer cohesive classes and modules with clear responsibilities.
- Prefer composition over inheritance unless inheritance is already the
  established pattern.
- Keep abstractions justified by real reuse or clarity, not speculation.

## Functions and Methods

- Keep functions and methods small and focused on one responsibility whenever
  practical.
- Prefer early returns to avoid horizontal nesting.
- Keep parameter lists small; introduce value objects or configuration arrays
  only when they improve clarity within the existing codebase style.
- Extract complex condition handling or repeated branches into well-named
  helpers.
- Avoid deeply nested control flow unless it is materially clearer than the
  alternatives.

## Types and Data Modeling

- Use strict typing where the codebase supports it.
- Prefer explicit parameter types, return types, and property types.
- Use value objects or small domain types when they prevent invalid states or
  clarify semantics.
- Prefer `null` only when absence is a real domain concept, not as a vague
  fallback.
- Keep arrays well-shaped and documented when dedicated types are not practical.

## Error Handling

- Handle failures explicitly; do not silently swallow exceptions or invalid
  states.
- Throw domain-appropriate exceptions with actionable messages when recovery is
  not possible locally.
- Validate untrusted input at boundaries.
- Keep error handling close to the operation when it improves clarity.
- Avoid boolean return values for rich failure cases when an exception or typed
  result is clearer.

## Documentation

- Add docblocks where they materially improve static analysis or explain
  non-obvious behavior.
- Document public APIs, important invariants, and edge cases when the behavior
  is not obvious from types and naming.
- Keep PHPDoc aligned with real signatures and behavior.
- Prefer types in native signatures over PHPDoc-only typing when the target PHP
  version supports them.

## Testing

- Add or update tests for new behavior and bug fixes.
- If PHPUnit is present, run the relevant test suite and prefer finishing only
  when it passes.
- Prefer focused unit tests for isolated behavior and broader integration tests
  when behavior crosses component boundaries.
- Mock external systems only when isolation materially improves confidence or
  speed.
- Remove dead or commented-out tests instead of leaving stale test code behind.

## Dependencies and Imports

- Keep dependencies justified and minimal.
- Prefer stable, well-maintained Composer packages over custom implementations
  for common problems.
- Avoid unused imports and keep `use` statements organized consistently with the
  repository.
- Prefer project-local abstractions only when they clearly improve fit over an
  existing package.

## Security and Reliability

- Never hardcode secrets, credentials, or tokens.
- Do not log sensitive values.
- Validate and sanitize untrusted input where appropriate.
- Handle file, network, database, and serialization failures explicitly.
- Be careful with dynamic execution, reflection, and unserialization.

## Completion Checklist

- [ ] Composer packages were preferred when the project uses Composer.
- [ ] PHP syntax matches the PHP version targeted by `composer.json`, or modern
      PHP was used when no Composer metadata exists.
- [ ] DRY was preserved; repeated logic was consolidated where appropriate.
- [ ] Functions and methods remain small and focused where practical.
- [ ] Early returns were used to reduce unnecessary nesting where appropriate.
- [ ] Static analysis passes with PHPStan if the project has it.
- [ ] Tests pass with PHPUnit if the project has it.
- [ ] Relevant formatting and project tooling checks were run if available.
- [ ] Debug code, dead code, and commented-out code are removed.

## Notes

- If the repository already has established conventions, follow the repository
  unless the user asks for a broader cleanup.
- When the project uses a framework, prefer its native conventions unless they
  conflict with the user's request.
- When Composer, PHPStan, or PHPUnit are absent, do not invent them just to
  satisfy the checklist; work within the project's actual tooling.
