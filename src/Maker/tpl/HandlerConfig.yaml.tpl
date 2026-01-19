<?= "#\n" ?>
# NeoxCrud — Per-handler configuration (optional)
# ------------------------------------------------
# This file lives next to your handler class to customize behavior without
# changing PHP code. It is safe to commit and maintain per resource.
#
# Location searched by the handler at runtime (first match wins):
#  - <HandlerDir>/config.yaml               # ← this file
#  - <HandlerDir>/<?= $class_name ?>.yaml
#  - <HandlerDir>/config/crud.yaml
#
# Currently supported keys (runtime):
#  - index_fields: [<list of field names>]
#    Choose which entity fields appear in the index table.
#    If absent, the handler defaults to all Doctrine fields except 'id'.
#
#    Advanced (optional): you can attach per-field attributes to control rendering
#    or visibility. Three equivalent syntaxes are supported:
#
#    1) Simple list (BC):
#       index_fields: ['title', 'email', 'enabled', 'createdAt']
#
#    2) List of maps with a "name" (or "field") key:
#       index_fields:
#         - { name: 'title' }
#         - { name: 'email', format: 'text' }
#         - { name: 'enabled', boolean_icon: true }   # render ✓/✗ icons
#         - { name: 'image', type: 'image', class: 'thumb-48' } # render <img>
#         - { name: 'createdAt', format: 'Y-m-d H:i' } # date/datetime format
#         - { name: 'roles', voters: ['ROLE_ADMIN'] }  # visible only if voter granted
#
#    3) Associative map: field name as key, options as value:
#       index_fields:
#         title: ~
#         email: { format: 'text' }
#         enabled: { boolean_icon: true }
#         image: { type: 'image', class: 'thumb-48' }
#         createdAt: { format: 'Y-m-d' }
#         roles: { voters: ['ROLE_ADMIN', 'ROLE_MANAGER'] }
#
# Notes:
#  - You can also nest the option under a root key `neox_crud:` if you prefer.
#  - Additional optional UI keys are supported (opt‑in, BC safe):
#    - actions: per-row action buttons in the index list
#    - bulk_actions: actions for the current selection (if your UI implements it)
#    - toolbar_buttons: buttons shown near the "New" button in the index view
#  - Keep comments or remove them; YAML ignores commented lines.
#
<?php
// If the Maker provided the list of Doctrine fields, suggest them
$available_fields = $available_fields ?? [];
if (is_array($available_fields) && $available_fields !== []) {
    $list = array_values(array_filter(array_map(static fn($f) => is_string($f) ? $f : null, $available_fields)));
    $quoted = array_map(static fn(string $f) => "'{$f}'", $list);
    $joined = implode(', ', $quoted);
    echo "# Detected entity fields (Doctrine):\n";
    foreach ($list as $f) {
        echo '# - ' . $f . "\n";
    }
    echo "#\n";
    echo "# Quick start — uncomment to use all fields as-is:\n";
    echo '# index_fields: [' . $joined . "]\n#\n";
} else {
    echo "# Example (flat key):\n";
    echo "# index_fields: ['id', 'name', 'createdAt']\n";
    echo "#\n";
}
?>



# Example (nested under neox_crud):
# neox_crud:
#   index_fields: ['id', 'name', 'createdAt']
#   append_default_actions: true
#   # Per-row actions (index table column)
#   actions:
#     - name: edit
#       label: "Éditer"
#       icon: "bi bi-pencil"
#       route: "neox_crud_admin_crud_edit"
#       params: { id: "entity.id" }
#       voters: ["EDIT"]
#       priority: 100
#
#     - name: delete
#       label: "Supprimer"
#       icon: "bi bi-trash"
#       route: "neox_crud_admin_crud_delete"
#       method: DELETE
#       params: { id: "entity.id" }
#       voters: ["DELETE"]
#       confirm: "Confirmer la suppression ?"
#       class: "btn-outline-danger"
#       priority: 50
#
#   # Append default CRUD buttons (Edit/Delete) after your custom actions (opt‑in)
#   # When true, the index will show your configured actions first, then the
#   # standard Edit/Delete buttons appended (without overriding your definitions).
#   # Default: false (BC). Works with both flat keys and under neox_crud.
#   append_default_actions: false
#
#   # Bulk actions (selection-based)
#   bulk_actions:
#     # UI is rendered automatically if you generated this CRUD with --with-bulk-ui (opt-in).
#     # Otherwise, override your index template to add the selection column and forms.
#     - name: bulk_delete
#       label: "Supprimer la sélection"
#       icon: "bi bi-trash3"
#       route: "neox_crud_admin_crud_custom"
#       method: POST
#       params: { action: "bulk_delete" }
#       voters: ["DELETE"]
#       confirm: "Supprimer tous les éléments sélectionnés ?"
#       selection_required: true
#
#   # Toolbar buttons (next to "Nouveau")
#   toolbar_buttons:
#     - name: export_csv
#       label: "Exporter CSV"
#       icon: "bi bi-filetype-csv"
#       route: "neox_crud_admin_crud_custom"
#       method: GET
#       params: { action: "export_csv" }
#       voters: ["VIEW"]
#       class: "btn-outline-secondary"

# Context
# -------
# Resource: <?= $resource ?>
# Handler : <?= $class_name ?>
