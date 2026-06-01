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
# Notes
# -----
# - You can also define keys at the root (flat) instead of nesting under neox_crud.
# - YAML ignores commented lines: keep comments or remove them freely.
#
<?php
$available_fields = $available_fields ?? [];
$field_types = $field_types ?? [];
$enable_live_table = (bool) ($enable_live_table ?? false);

// Helper to detect field type and generate appropriate config
function getFieldConfig(string $fieldName, ?string $doctrineType): string
{
    $config = "name: '{$fieldName}'";
    
    // Detect type and add appropriate options
    if ($doctrineType !== null) {
        $type = strtolower($doctrineType);
        
        // Date/time types
        if (str_contains($type, 'date') || str_contains($type, 'time')) {
            if (str_contains($type, 'date') && !str_contains($type, 'time')) {
                $config .= ", format: 'Y-m-d'";
            } elseif (str_contains($type, 'datetime')) {
                $config .= ", format: 'Y-m-d H:i'";
            }
        }
        // Boolean type
        elseif ($type === 'boolean') {
            $config .= ", boolean_icon: true";
        }
    }
    
    return $config;
}
?>

neox_crud:
  # Index fields — choose which fields appear in the index table
  # If absent, the handler defaults to all Doctrine fields except 'id'.
  #
  # Recommended syntax (more explicit, easy to extend): list of maps
  # You can still use a simple list of strings (BC).
  #
  # Options:
  # - sortable/searchable: used by LiveTable query builder
  # - query_path: for relations (ex: user.email)
  # - join: 'left' (default) or 'inner'
  # - format: date formatting (ex: 'Y-m-d')
  # - boolean_icon: render boolean as icon
  #
  # Relation example:
  #   - { name: 'category', query_path: 'category.name', join: 'left', sortable: true, searchable: true }
  #
<?php
if (is_array($available_fields) && $available_fields !== []) {
    $list = array_values(array_filter(array_map(static fn($f) => is_string($f) ? $f : null, $available_fields)));
    echo "# Detected entity fields (Doctrine):\n";
    foreach ($list as $f) {
        $type = $field_types[$f] ?? 'unknown';
        echo "#   - {$f} ({$type})\n";
    }
    echo "#\n";
    echo "  index_fields:\n";
    
    // Only uncomment first 4 fields
    $i = 0;
    foreach ($list as $f) {
        $comment = $i >= 4 ? '# ' : '';
        $config = getFieldConfig($f, $field_types[$f] ?? null);
        
        if ($i === 0) {
            echo "{$comment}    - { {$config}, sortable: true, searchable: true }\n";
        } else {
            echo "{$comment}    - { {$config} }\n";
        }
        $i++;
    }
} else {
    echo "  # Example (simple list, BC):\n";
    echo "  # index_fields: ['id', 'name', 'createdAt']\n";
    echo "#\n";
    echo "  # Example (recommended, identified fields):\n";
    echo "  index_fields:\n";
    echo "    - { name: 'id', sortable: true }\n";
    echo "    - { name: 'name', sortable: true, searchable: true }\n";
    echo "    - { name: 'createdAt', format: 'Y-m-d' }\n";
}
?>

<?php
$prefix = $enable_live_table ? '' : '# ';
echo $prefix . "  # LiveTable — interactive index table (opt‑in)\n";
echo $prefix . "  # Uncomment to enable, or use Maker option --enable-live-table\n";
echo $prefix . "  live_table:\n";
echo $prefix . "    enabled: true\n";
echo $prefix . "    pagination_position: top   # top | bottom | all\n";
echo $prefix . "    default_per_page: 4\n";
echo $prefix . "    max_per_page: 4\n";
?>

  # UI configuration (opt‑in, BC safe)
  # ----------------------------------
  # Optional UI configuration (opt‑in, BC safe)
  #  - actions: per-row action buttons in the index list
  #  - bulk_actions: actions for the current selection (if your UI implements it)
  #  - toolbar_buttons: buttons shown near the "New" button in the index view

  # append_default_actions: true  # Append default Edit/Delete after your custom actions

  # Per-row actions (index table column)
  # actions:
  #   - name: edit
  #     label: "Éditer"
  #     icon: "bi bi-pencil"
  #     route: "neox_crud_admin_crud_edit"
  #     params: { id: "entity.id" }
  #     voters: ["EDIT"]
  #     priority: 100
  #
  #   - name: delete
  #     label: "Supprimer"
  #     icon: "bi bi-trash"
  #     route: "neox_crud_admin_crud_delete"
  #     method: DELETE
  #     params: { id: "entity.id" }
  #     voters: ["DELETE"]
  #     confirm: "Confirmer la suppression ?"
  #     class: "btn-outline-danger"
  #     priority: 50

  # Bulk actions (selection-based)
  # UI is rendered automatically if you generated this CRUD with --with-bulk-ui (opt-in).
  # Otherwise, override your index template to add the selection column and forms.
  # bulk_actions:
  #   - name: bulk_delete
  #     label: "Supprimer la sélection"
  #     icon: "bi bi-trash3"
  #     route: "neox_crud_admin_crud_custom"
  #     method: POST
  #     params: { action: "bulk_delete" }
  #     voters: ["DELETE"]
  #     confirm: "Supprimer tous les éléments sélectionnés ?"
  #     selection_required: true

  # Toolbar buttons (next to "Nouveau")
  # toolbar_buttons:
  #   - name: export_csv
  #     label: "Exporter CSV"
  #     icon: "bi bi-filetype-csv"
  #     route: "neox_crud_admin_crud_custom"
  #     method: GET
  #     params: { action: "export_csv" }
  #     voters: ["VIEW"]
  #     class: "btn-outline-secondary"

# Context
# -------
# Resource: <?= $resource ?>
# Handler : <?= $class_name ?>
