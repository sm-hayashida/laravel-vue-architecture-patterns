# Laravel Vue Architecture Patterns Repository Entry

This file contains only repository-specific guidance for the MVC / Onion / Clean
comparison project.

## Purpose And Structure

- Purpose: compare Laravel + Vue implementations of MVC, Onion + DDD, and Clean
  Architecture + DDD for a learning portfolio.
- `pattern1-mvc/`: Laravel MVC baseline.
- `pattern2-onion/`: Onion Architecture with a domain-centered dependency rule.
- `pattern3-clean/`: Clean Architecture with Use Cases and Input/Output Ports.
- `docs/`: learning explanations and architectural comparisons.
- `.spec/`: current requirements, design, tasks, and learning roadmap.

Read only the relevant `docs/` and `.spec/` files for the active task. The
schedule and completed progress history live in `.spec/learning-roadmap.md` and
are not routine startup context.

## Architecture Constraints

- Keep the comparison valuable by making responsibility placement and trade-offs
  explicit in both code and documentation.
- Use domain terms consistently and explain why a responsibility belongs in its
  chosen layer.
- Prefer KISS and YAGNI; avoid abstractions that do not improve the comparison.
- Pattern 1 stays Laravel MVC; do not add Repository, UseCase, or Domain layers.
- Pattern 2 keeps Domain independent of Infrastructure.
- Pattern 3 keeps Use Cases and Input/Output Ports as explicit boundaries.

## Planning And Verification

- Before implementation, state how responsibility placement differs across MVC,
  Onion, and Clean for the changed concept.
- Use the repository's current code, README, `.spec/`, and package/framework
  commands as the source of truth for verification.

## Commit Message Convention

- `docs:` learning log or documentation
- `feat:` feature implementation
- `refactor:` behavior-preserving restructure
- `test:` tests
- `chore:` configuration or setup
