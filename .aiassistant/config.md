# YAML

neox_crud:

# Active l’index live (opt-in) pour cette ressource uniquement

index:
live_table: true

# ----- Colonnes index (source de vérité unique) -----

index_fields:

- name: title
  label: "Titre"
  sortable: true
  searchable: true

    - name: email
      label: "Email"
      format: "text"
      sortable: true
      searchable: true

    - name: enabled
      label: "Actif"
      boolean_icon: true
      sortable: true
      filter:
      type: boolean

    - name: image
      label: "Image"
      type: image
      class: "thumb-48"
      # pas triable/recherchable par défaut

    - name: createdAt
      label: "Créé le"
      format: "Y-m-d H:i"
      sortable: true
      filter:
      type: date

  # Champ relation (JOIN) : affichage + tri + recherche
    - name: category.name
      label: "Catégorie"
      sortable: true
      searchable: true
      join: left

  # Colonne visible uniquement si autorisée
    - name: roles
      label: "Rôles"
      voters: ["ROLE_ADMIN"]

# ----- Boutons toolbar (en haut) -----

toolbar_buttons:

- name: export_csv
  label: "Exporter CSV"
  icon: "bi bi-download"
  route: "neox_crud_admin_crud_custom"
  method: GET
  params:
  action: "export_csv"
  turbo:
  enabled: false # souvent mieux pour un download

    - name: refresh
      label: "Rafraîchir"
      icon: "bi bi-arrow-clockwise"
      route: "neox_crud_admin_crud_index"
      method: GET
      params: { }
      turbo:
      frame: "crud_table" # si tu rends la table dans un frame (sinon _top)

# ----- Actions par ligne (colonne Actions) -----

actions:

- name: edit
  label: "Éditer"
  icon: "bi bi-pencil"
  route: "neox_crud_admin_crud_edit"
  method: GET
  params:
  id: "entity.id"
  turbo:
  frame: "_top"

    - name: publish
      label: "Publier"
      icon: "bi bi-megaphone"
      route: "neox_crud_admin_crud_custom"
      method: GET
      params:
      id: "entity.id"
      action: "publish"
      if: "!entity.published"
      turbo:
      frame: "_top"

    - name: delete
      label: "Supprimer"
      icon: "bi bi-trash"
      route: "neox_crud_admin_crud_delete"
      method: DELETE
      params:
      id: "entity.id"
      confirm: "Confirmer la suppression ?"
      class: "btn-outline-danger"
      turbo:
      frame: "_top"

# ----- Actions de masse (bulk) -----

bulk_actions:

- name: bulk_publish
  label: "Publier la sélection"
  icon: "bi bi-check2-circle"
  route: "neox_crud_admin_crud_custom"
  method: POST
  params:
  action: "bulk_publish"
  selection_required: true
  turbo:
  frame: "crud_table"
  confirm: "Publier tous les éléments sélectionnés ?"

  - name: bulk_delete
    label: "Supprimer la sélection"
    icon: "bi bi-trash"
    route: "neox_crud_admin_crud_custom"
    method: POST
    params:
    action: "bulk_delete"
    selection_required: true
    confirm: "Supprimer tous les éléments sélectionnés ?"
    class: "btn-outline-danger"
    turbo:
    frame: "crud_table"