# Customization

## Core Versus Extension

Core contains universal master, procurement, sales, stock, warehouse, accounting, finance, control, API, report, and security capabilities. Customization contains country tax filing, industry-specific fields/rules, local integrations, specialized workflows, document layouts, and vertical reports.

## Non-Negotiable Boundary

Customization must not modify core tables, core state semantics, posting invariants, or shared API behavior. Use extension tables keyed by core ID, metadata schema, field registry, plugin package, hooks, events, observers, report registry, and theme/layout overrides.

## Extension Governance

Each extension has owner, version, dependency, migration, permissions, feature flag, test suite, data classification, rollback plan, and compatibility contract. Core upgrade runs extension compatibility checks. Custom code may subscribe to events but cannot veto committed invariant after posting.
