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
# Quick start
# ----------
# 1) Choose which fields appear in the index table
#    If absent, the handler defaults to all Doctrine fields except 'id'.
#
#    Recommended syntax (more explicit, easy to extend): list of maps
#    You can still use a simple list of strings (BC).
#
#    index_fields:
#      - { name: 'text', sortable: true, searchable: true }
#      - { name: 'roles' }
#      - { name: 'createdAt', format: 'Y-m-d' }
#
#    Notes about these options:
#    - sortable/searchable are mainly used by the LiveTable query builder.
#    - query_path can be used for relations (ex: user.email)
#    - join can be 'left' (default) or 'inner'
#
#    Relation example:
#      - { name: 'category', query_path: 'category.name', join: 'left', sortable: true, searchable: true }
#      - { name: 'author', query_path: 'author.email', join: 'left', sortable: true, searchable: true }
#
# 2) Enable LiveTable for this handler (opt‑in)
#    Uncomment the block printed below to enable the interactive index table for THIS resource.
#    You can also generate it already enabled with the Maker option --enable-live-table.
#
# 3) Optional UI configuration (opt‑in, BC safe)
#    - actions: per-row action buttons in the index list
#    - bulk_actions: actions for the current selection (if your UI implements it)
#    - toolbar_buttons: buttons shown near the "New" button in the index view
#
# Notes
# -----
# - You can also define keys at the root (flat) instead of nesting under neox_crud.
# - YAML ignores commented lines: keep comments or remove them freely.
#
<?php
// If the Maker provided the list of Doctrine fields, suggest them
$available_fields = $available_fields ?? [];
$enable_live_table = (bool) ($enable_live_table ?? false);
if (is_array($available_fields) && $available_fields !== []) {
    $list = array_values(array_filter(array_map(static fn($f) => is_string($f) ? $f : null, $available_fields)));
    $quoted = array_map(static fn(string $f) => "'{$f}'", $list);
    $joined = implode(', ', $quoted);
    echo "# Detected entity fields (Doctrine):\n";
    foreach ($list as $f) {
        echo '# - ' . $f . "\n";
    }
    echo "#\n";
    echo "# Quick start — uncomment to use all fields as-is (simple list, BC):\n";
    echo '# index_fields: [' . $joined . "]\n";
    echo "#\n";
    echo "# Example (recommended, identified fields):\n";
    echo "# index_fields:\n";
    $i = 0;
    foreach ($list as $f) {
        $prefix = $i === 0 ? 'text' : $f;
        if ($i === 0) {
            echo "#   - { name: '" . $f . "', sortable: true, searchable: true }\n";
        } else {
            echo "#   - { name: '" . $f . "' }\n";
        }
        $i++;
    }
    echo "#\n";
} else {
    echo "# Example (simple list, BC):\n";
    echo "# index_fields: ['id', 'name', 'createdAt']\n";
    echo "#\n";
    echo "# Example (recommended, identified fields):\n";
    echo "# index_fields:\n";
    echo "#   - { name: 'id', sortable: true }\n";
    echo "#   - { name: 'name', sortable: true, searchable: true }\n";
    echo "#   - { name: 'createdAt', format: 'Y-m-d' }\n";
    echo "#\n";
}
?>

<?php
$prefix = $enable_live_table ? '' : '# ';
echo "# LiveTable (per handler)\n";
echo "# --------------------\n";
echo $prefix . "neox_crud:\n";
echo $prefix . "  live_table:\n";
echo $prefix . "    enabled: true\n";
echo $prefix . "    pagination_position: top   # top | bottom | all\n";
echo $prefix . "    default_per_page: 4\n";
echo $prefix . "    max_per_page: 4\n";
echo "#\n";
?>



# UI examples (nested under neox_crud)
# -------------------------------
# neox_crud:
#   append_default_actions: true
#
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
