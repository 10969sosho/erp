# Form Standards

## Form Contract

Every form defines field key, label, type, requiredness, default, source, visibility rule, editability by state, precision, validation, help text, and audit sensitivity.

## Layout

Header fields: document number (read-only generated), date, company/branch, party, currency, terms, status. Lines: item, description, UOM, quantity, price, discount, tax, warehouse, dimensions. Summary: subtotal, discount, tax, rounding, total. Supporting: notes, attachment, references.

## Actions

Save Draft, Submit, Approve, Reject, Rework, Post, Cancel, Reverse, Print, Export, Add Attachment, and View Audit appear only when state and permission permit. Post action shows calculated journal/stock result.

## Input Standards

Use ISO backend dates with localized display; decimal inputs must reject invalid precision; autocomplete must require selection; monetary total is server-calculated; users cannot submit stale version. Autosave is draft-only and must expose last saved time.
