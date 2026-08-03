<?php
/**
 * ArtiGrid - AJAX Grid Library
 * Author: Daniel Huerta
 * Year: 2026
 * Version: 1.9
 */

require_once 'db.php';
require_once __DIR__ . '/Sqldialect.php';
$config = require 'config/config.php';

DB::setConfig($config['db']);
$pdo = DB::connect();

class ArtiGrid
{
    protected $table = '';
    protected $baseTable = '';
    protected $db;
    protected $perPage = 5;
    protected $mode = 'crud';
    protected $editData = [];
    protected $uploadPath = '';
    protected $editRowData = [];
    protected $viewRowData = [];
    protected $id;
    protected $onePagePosition = 'left';
    protected $customAddButton = null;
    protected $whereConditions = [];
    protected $perPageOptions = [5, 10, 20, 30, 50, 'all'];
    protected $colRename = [];
    protected $columns = [];
    protected $useModal = false;
    protected $query = '';
    protected $useQuery = false;
    protected $crudTable = null;
    protected $primaryKey = 'id';
    protected $fieldTypes = [];
    protected $customButtons = [];
    protected $exportTypes = [];
    protected $joins = [];
    protected $selectColumns = [];
    protected $requiredFields = [];
    protected $requiredGroups = [];
    protected $duplicateFields = [];
    protected $runtimeGridId = null;
    protected $sortColumn = null;
    protected $sortOrder  = null;
    protected $groupByColumns = [];
    protected $template = 'bootstrap5';
    protected $bulkDeleteCondition = null;
    protected $comboBoxes = [];
    protected $jsonColumns = null;
    protected $jsonRows    = null;
    protected $hiddenColumns = [];
    protected $formFields = null;
    protected $editFormFields = [];
    protected $viewFormFields = [];
    protected $formFieldValues = [];
    protected $fieldAttributes = [];   
    protected $pdf = null; 
    protected $useChart = false;
    protected $nestedGrids = [];
    protected $imageFieldsConfig = [];
    protected $chartLabels = [];
    protected $chartDatasets = [];
    protected $chartType = 'bar';
    protected $chartOptions = [];
    protected $fieldCss = [];
    protected $columnColors = [];
    protected $rowColors = [];
    protected $allFieldsRequired = false;
    protected $insertFormTemplate = '';
    protected $editFormTemplate   = '';
    protected $viewFormTemplate   = '';
    protected $selectFormTemplate = '';
    protected $buttonsDropdown = false;
    protected $sendEmailOnInsert = false;
    protected $emailConfig = [];
    protected $radioFields = [];
    protected $checkboxGroups = [];
    protected $inlineEditEnabled = false;
    protected $inlineEditConfig = [];
    protected $customFieldTypes = [];
    protected $selectOptions = [];
    protected $crudTemplate = '';
    protected $fieldsArrange = [];
    protected $ajaxConfig = null;
    protected $subselects = [];
    protected $calculatedFields = [];
    protected $fieldConditions = [];
    protected $advancedFilters      = [];
    protected $advancedFilterTitle  = 'Advanced Filters';
    protected $advancedFilterOpen   = false;
    protected $advancedFilterTarget = null;
    protected $advancedFilterTemplate = '';
    protected $advancedFilterLazy = false;
    protected $advancedFilterPosition = 'top';
    protected $filterPanelRendered = false;
    protected $colorFields = [];
    protected $rowTemplate = null;
    protected $rowWrapperTag = 'div';
    protected $rowWrapperClass = 'row g-4';
    protected $rowWrapperAttrs = [];  
    protected $calendarConfig = [
        'titleField'    => 'title',
        'startField'    => 'start',
        'endField'      => 'end',
        'colorField'    => null,
        'allDayField'   => null,
        'initialView'   => 'dayGridMonth',
        'editable'      => true,
        'selectable'    => true,
        'locale'        => 'es',
        'height'        => 'auto',
    ];
    protected $ckeditorFields = [];
    protected $summernoteFields = [];
    protected $select2Fields  = [];
    protected $chosenFields   = [];
    protected $callbacks = [
        'beforeInsert' => [],
        'afterInsert'  => [],
        'beforeUpdate' => [],
        'afterUpdate' => [],
        'beforeDelete' => [],
        'afterDelete' => [],
        'beforeRenderRow' => []
    ];
    protected $actions = [
        'search' => true,
        'filter' => true,
        'add' => true,
        'view' => true,
        'actions' => true,
        'refresh' => true,
        'edit' => true,
        'clone' => true,
        'delete' => true,
        'checkbox' => true,
        'dropdownpage' => true,
        'pagination' => true,
        'edit_multiple' => true,
        'delete_multiple' => true
    ];
    protected $actionConditions = [
        'view' => [],
        'edit' => [],
        'clone' => [],
        'delete' => [],
        'checkbox' => []
    ];
    protected $lang = [
        'add'               => 'Add',
        'view'              => 'View',
        'actions'           => 'Actions',
        'clear'             => 'Clear',
        'This_action'       => 'This action cannot be undone',
        'search'            => 'Search...',
        'delete'            => 'Delete record?',
        'delete_multiple'   => 'Delete selected',
        'edit_multiple'     => 'Edit Multiple',
        'The_record'        => 'The records could not be deleted',
        'records_deleted'   => 'record(s) deleted',
        'prev'              => 'Prev',
        'next'              => 'Next',
        'This_field_is_required' => 'This field is required',
        'save'              => 'Save',
        'cancel'            => 'Cancel',
        'export'            => 'Export',
        'select_all'        => 'Select all',
        'All'               => 'All'
    ];
    
    protected $config = [];
    protected SqlDialect $dialect;
    protected $bulkCustomAction = [];
    protected $summaryRow = [];
    protected $summaryConfig = [
        'label'      => 'Total',
        'position'   => 'bottom',   // bottom | top
        'decimals'   => 2,
        'thousands'  => '.',
        'decimalSep' => ',',
    ];
    protected $timelineMode = false;
    protected $timelineConfig = [
        'dateField'    => null,
        'titleField'   => null,
        'contentField' => null,
        'iconField'    => null,
        'colorField'   => null,
        'align'        => 'left',   // left | right | alternate
        'dateFormat'   => 'd-m-Y',
        'orderDir'     => 'desc',
    ];

    public function timeline(array $config = []): self
    {
        $this->timelineConfig = array_merge($this->timelineConfig, $config);
        return $this;
    }

    public function __construct(?PDO $pdo = null)
    {
        $this->db = $pdo ?? DB::connect();
        $this->dialect = new SqlDialect($this->db);
        $this->id = 'artigrid_' . uniqid();
        $this->nestedGrids = [];
        $configFile = __DIR__ . '/config/config.php';
        if (file_exists($configFile)) {
            $this->config = require $configFile;
            $this->applyDefaultsFromConfig();
        }
        $this->config['actionsPosition'] = $this->config['actionsPosition'] ?? 'right';
    }

    public function summernote(string $field, array $options = []): self
    {
        $this->summernoteFields[$field] = array_merge([
            'height'      => 200,
            'toolbar'     => [],
            'placeholder' => '',
            'lang'        => 'es-US',
        ], $options);
        if (!isset($this->fieldTypes[$field])) {
            $this->fieldTypes[$field] = 'textarea';
        }
        return $this;
    }

    protected function summernoteAttr(string $name): string
    {
        if (!isset($this->summernoteFields[$name])) {
            return '';
        }
        $cfg = json_encode($this->summernoteFields[$name]);
        return ' data-summernote-field="1" data-summernote-options=\'' . htmlspecialchars($cfg, ENT_QUOTES) . '\'';
    }

    public function ckeditor(string $field, array $options = []): self
    {
        $this->ckeditorFields[$field] = array_merge([
            'height'  => 200,
            'toolbar' => [],
        ], $options);
        if (!isset($this->fieldTypes[$field])) {
            $this->fieldTypes[$field] = 'textarea';
        }
        return $this;
    }

    public function imageField(string $field, array $options = []): self
    {
        $this->fieldTypes[$field] = 'image';
        $this->imageFieldsConfig[$field] = array_merge([
            'multiple'    => false,
            'crop'        => true,
            'aspectRatio' => null,
            'width'       => null,
            'height'      => null,
            'maxFiles'    => 10,
        ], $options);
        return $this;
    }

    protected function chosenAttr(string $name): string
    {
        if (!isset($this->chosenFields[$name])) {
            return '';
        }
        $cfg = json_encode($this->chosenFields[$name]);
        return ' data-chosen-field="1" data-chosen-options=\'' . htmlspecialchars($cfg, ENT_QUOTES) . '\'';
    }

    protected function select2Attr(string $name): string
    {
        if (!isset($this->select2Fields[$name])) {
            return '';
        }
        $cfg = json_encode($this->select2Fields[$name]);
        return ' data-select2-field="1" data-select2-options=\'' . htmlspecialchars($cfg, ENT_QUOTES) . '\'';
    }

    public function select2(string $field, array $options = []): self
    {
        $this->select2Fields[$field] = array_merge([
            'placeholder' => 'Select an option',
            'allowClear'  => true,
            'width'       => '100%',
        ], $options);
        return $this;
    }

    public function chosen(string $field, array $options = []): self
    {
        $this->chosenFields[$field] = array_merge([
            'placeholder_text_single'   => 'Select an option',
            'placeholder_text_multiple' => 'Select options',
            'no_results_text'           => 'No results found',
            'width'                     => '100%',
            'allow_single_deselect'     => true,
        ], $options);
        return $this;
    }

    public function colorPicker(string $field, array $options = []): self
    {
        $this->colorFields[$field] = $options;
        return $this;
    }

    protected function colorAttr(string $name): string
    {
        if (!isset($this->colorFields[$name])) {
            return '';
        }
        $cfg = json_encode($this->colorFields[$name]);
        return ' data-jscolor-field="1" data-jscolor-options=\'' . htmlspecialchars($cfg, ENT_QUOTES) . '\'';
    }

    public function calendar(array $config = []): self
    {
        $this->calendarConfig = array_merge($this->calendarConfig, $config);
        return $this;
    }

    public function subselect(string $alias, string $sql): self
    {
        $this->subselects[$alias] = $sql;
        return $this;
    }

    public function calculate(string $alias, string $expression): self
    {
        $this->calculatedFields[$alias] = $expression;
        return $this;
    }

    public function advancedFilter(
        string $field,
        string $type  = 'text',
        array  $options = [],
        array  $extra   = []
    ): self {
        $this->advancedFilters[$field] = array_merge([
            'field'    => $field,
            'type'     => $type,
            'options'  => $options,
            'label'    => $extra['label'] ?? ucfirst(str_replace('_', ' ', $field)),
            'placeholder' => $extra['placeholder'] ?? '',
            'operator' => $extra['operator'] ?? 'LIKE',
            'min'      => $extra['min'] ?? null,
            'max'      => $extra['max'] ?? null,
            'group'    => $extra['group'] ?? '',
            'field_from' => $extra['field_from'] ?? ($field . '_from'),
            'field_to'   => $extra['field_to']   ?? ($field . '_to'),
        ], $extra);
        return $this;
    }
   
    public function advancedFilterLazy(bool $lazy = true): self
    {
        $this->advancedFilterLazy = $lazy;
        return $this;
    }

    public function advancedFilterTitle(string $title): self
    {
        $this->advancedFilterTitle = $title;
        return $this;
    }

    public function advancedFilterPosition(string $position): self
    {
        $allowed = ['top', 'bottom', 'left', 'right'];
        $position = strtolower(trim($position));
        if (in_array($position, $allowed, true)) {
            $this->advancedFilterPosition = $position;
        }
        return $this;
    }

    public function renderWithFilter(string $crudHtml): string
    {
        $filterHtml = $this->renderAdvancedFilterPanel();
        if ($filterHtml === '') {
            return $crudHtml;
        }

        $pos = $this->advancedFilterPosition ?? 'top';
        switch ($pos) {
            case 'left':
                return '
                <div class="artigrid-with-filter container-fluid p-0">
                    <div class="row g-3">
                        <div class="col-md-3">' . $filterHtml . '</div>
                        <div class="col-md-9">' . $crudHtml . '</div>
                    </div>
                </div>';

            case 'right':
                return '
                <div class="artigrid-with-filter container-fluid p-0">
                    <div class="row g-3">
                        <div class="col-md-9">' . $crudHtml . '</div>
                        <div class="col-md-3">' . $filterHtml . '</div>
                    </div>
                </div>';

            case 'bottom':
                return '
                <div class="artigrid-with-filter">
                    ' . $crudHtml . '
                    <div class="mt-3">' . $filterHtml . '</div>
                </div>';

            case 'top':
            default:
                return '
                <div class="artigrid-with-filter">
                    <div class="mb-3">' . $filterHtml . '</div>
                    ' . $crudHtml . '
                </div>';
        }
    }

    public function setAdvancedFilterTemplate(string $template): self
    {
        if (is_file($template)) {
            ob_start();
            include $template;
            $template = ob_get_clean();
        }
        $this->advancedFilterTemplate = $template;
        return $this;
    }

    public function advancedFilterOpen(bool $open = true): self
    {
        $this->advancedFilterOpen = $open;
        return $this;
    }

    public function renderAdvancedFilterPanel(): string
    {
        if (empty($this->advancedFilters)) {
            return '';
        }
        if (!empty($this->filterPanelRendered)) {
            return '';
        }
        $this->filterPanelRendered = true;

        if (empty($this->runtimeGridId)) {
            $this->runtimeGridId = $this->id . '_' . substr(md5(uniqid('', true)), 0, 8);
        }
        if (!isset($_SESSION['artigrid'][$this->runtimeGridId])) {
            $_SESSION['artigrid'][$this->runtimeGridId] = [];
        }
        $_SESSION['artigrid'][$this->runtimeGridId]['config'] = $this->buildConfig();
        $gridId = $this->runtimeGridId;
        $title  = htmlspecialchars($this->advancedFilterTitle ?? 'Advanced Filters');
        $isOpen = $this->advancedFilterOpen ? ' is-open' : '';
        $filters = $this->advancedFilters;
        $groups = [];
        foreach ($filters as $f) {
            $g = $f['group'] ?: '__default__';
            $groups[$g][] = $f;
        }
        $filterTemplate = '';
        if (!empty($this->advancedFilterTemplate)) {
            $filterTemplate = $this->advancedFilterTemplate;
            foreach ($filters as $f) {
                $fieldName = $f['field'] ?? $f['name'] ?? '';
                if (!$fieldName) {
                    continue;
                }
                $html = $this->renderSingleFilterField($f);
                $filterTemplate = str_replace(
                    '{' . $fieldName . '}',
                    $html,
                    $filterTemplate
                );
            }
            $filterTemplate = strtr($filterTemplate, $this->buildCrudControls());
        }
        ob_start();
        static $cssLoaded = false;
        if (!$cssLoaded) {
            $cssLoaded = true;
            $base = $this->getBaseUrl();
            echo '<link rel="stylesheet" href="' . $base . 'assets/css/artigrid-advanced-filter.css">';
        }
        ?>
        <div class="artigrid-filter-panel <?= $isOpen?>"
            data-filter-target="<?= htmlspecialchars($gridId) ?>"
            data-lazy="<?= $this->advancedFilterLazy ? '1' : '0' ?>"
            id="afp_<?= htmlspecialchars($gridId) ?>">
            <div class="artigrid-filter-header"
                onclick="ArtiGridFilter.toggle(this.closest('.artigrid-filter-panel'))">
                <span class="artigrid-filter-header-left">
                    <i class="fa fa-sliders"></i>
                    <?= $title ?>
                    <span class="artigrid-filter-badge hidden">0</span>
                </span>
                <i class="fa fa-chevron-down artigrid-filter-toggle-icon"></i>
            </div>
            <div class="artigrid-filter-chips"></div>
            <div class="artigrid-filter-body">
                <?php if (!empty($filterTemplate)): ?>
                    <?= $filterTemplate ?>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($groups as $groupName => $groupFields): ?>
                            <?php if ($groupName !== '__default__'): ?>
                                <div class="col-12">
                                    <div class="artigrid-filter-group">
                                        <div class="artigrid-filter-group-title">
                                            <i class="fa fa-layer-group"></i>
                                            <?= htmlspecialchars($groupName) ?>
                                        </div>
                                        <div class="row g-3" style="margin-bottom:0">
                                            <?php foreach ($groupFields as $f): ?>
                                                <div class="<?= htmlspecialchars($f['col'] ?? 'col-md-6') ?>">
                                                    <?= $this->renderSingleFilterField($f) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($groupFields as $f): ?>
                                    <div class="<?= htmlspecialchars($f['col'] ?? 'col-md-6') ?>">
                                        <?= $this->renderSingleFilterField($f) ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="artigrid-filter-footer">
                    <button class="btn btn-artigrid-apply"
                            onclick="ArtiGridFilter.apply(this)"
                            style="display:none;">
                        <i class="fa fa-check me-1"></i> Apply filters
                    </button>
                    <button class="btn btn-artigrid-clear"
                            onclick="ArtiGridFilter.clear(this)">
                        <i class="fa fa-times me-1"></i><?= $this->lang['clear'] ?>
                    </button>
                    <span class="artigrid-filter-status"></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    protected function renderSingleFilterField(array $f): string
    {
        $name  = htmlspecialchars($f['field']);
        $label = htmlspecialchars($f['label']);
        $ph    = htmlspecialchars($f['placeholder'] ?? '');
        $type  = $f['type'];
        ob_start();
        echo '<div class="artigrid-filter-field" data-field="' . $name . '" data-type="' . htmlspecialchars($type) . '">';
        echo '<label>' . $label . '</label>';
        switch ($type) {
            case 'text':
                $op = htmlspecialchars($f['operator'] ?? 'LIKE');
                echo '<input type="text" class="form-control afg-input" name="' . $name . '" placeholder="' . $ph . '" data-operator="' . $op . '">';
                break;
            case 'number':
                $min = $f['min'] !== null ? ' min="' . (float)$f['min'] . '"' : '';
                $max = $f['max'] !== null ? ' max="' . (float)$f['max'] . '"' : '';
                echo '<input type="number" class="form-control afg-input" name="' . $name . '" placeholder="' . $ph . '" data-operator="=" ' . $min . $max . '>';
                break;
            case 'date':
                echo '<input type="text" class="form-control afg-input artigrid-date" name="' . $name . '" placeholder="YYYY-MM-DD" data-operator="=">';
                break;
            case 'datetime':
                echo '<input type="text" class="form-control afg-input artigrid-datetime" name="' . $name . '" placeholder="YYYY-MM-DD HH:MM" data-operator="=">';
                break;
            case 'date_range':
                $from = htmlspecialchars($f['field_from']);
                $to   = htmlspecialchars($f['field_to']);
                echo '<div class="artigrid-filter-range">';
                echo '<input type="text" class="form-control afg-input artigrid-date" name="' . $from . '" placeholder="Inicio" data-operator=">=" data-range-role="from">';
                echo '<span class="artigrid-filter-range-sep">→</span>';
                echo '<input type="text" class="form-control afg-input artigrid-date" name="' . $to . '" placeholder="Término" data-operator="<=" data-range-role="to">';
                echo '</div>';
                break;
            case 'number_range':
                $from = htmlspecialchars($f['field_from']);
                $to   = htmlspecialchars($f['field_to']);
                $min  = $f['min'] !== null ? ' min="' . (float)$f['min'] . '"' : '';
                $max  = $f['max'] !== null ? ' max="' . (float)$f['max'] . '"' : '';
                echo '<div class="artigrid-filter-range">';
                echo '<input type="number" class="form-control afg-input" name="' . $from . '" placeholder="Min" data-operator=">=" data-range-role="from" ' . $min . $max . '>';
                echo '<span class="artigrid-filter-range-sep">–</span>';
                echo '<input type="number" class="form-control afg-input" name="' . $to . '" placeholder="Max" data-operator="<=" data-range-role="to" ' . $min . $max . '>';
                echo '</div>';
                break;
            case 'select':
                echo '<select class="form-select afg-input" name="' . $name . '" data-operator="=">';
                echo '<option value="">-- All --</option>';
                foreach ($f['options'] as $val => $txt) {
                    echo '<option value="' . htmlspecialchars($val) . '">' . htmlspecialchars($txt) . '</option>';
                }
                echo '</select>';
                break;
            case 'select_cascade':
                $dep         = htmlspecialchars($f['depends_on']    ?? '');
                $cTable      = htmlspecialchars($f['table']         ?? '');
                $cValue      = htmlspecialchars($f['value']         ?? 'id');
                $cLabel      = htmlspecialchars($f['label_col']     ?? 'name');
                $cField      = htmlspecialchars($f['depends_field'] ?? '');
                $cWhere      = htmlspecialchars(json_encode($f['where'] ?? []), ENT_QUOTES);
                echo '<select class="form-select afg-input afg-cascade"
                        name="'               . $name   . '"
                        data-operator="="
                        data-depends-on="'    . $dep    . '"
                        data-cascade-table="' . $cTable . '"
                        data-cascade-value="' . $cValue . '"
                        data-cascade-label="' . $cLabel . '"
                        data-cascade-field="' . $cField . '"
                        data-cascade-where=\'' . $cWhere . '\'
                        disabled>
                    <option value="">-- Select ' . $label . ' --</option>
                </select>';
                break;
            case 'checkbox':
                echo '<div class="artigrid-filter-checks">';
                foreach ($f['options'] as $val => $txt) {
                    $id = 'afg_' . $name . '_' . preg_replace('/\W/', '_', $val);
                    echo '<div class="form-check">';
                    echo '<input class="form-check-input afg-input" type="checkbox" name="' . $name . '[]" value="' . htmlspecialchars($val) . '" id="' . $id . '" data-operator="IN">';
                    echo '<label class="form-check-label" for="' . $id . '">' . htmlspecialchars($txt) . '</label>';
                    echo '</div>';
                }
                echo '</div>';
                break;
            case 'radio':
                echo '<div class="artigrid-filter-checks">';
                $rid = 'afg_' . $name . '_all';
                echo '<div class="form-check">';
                echo '<input class="form-check-input afg-input" type="radio" name="' . $name . '" value="" id="' . $rid . '" data-operator="=" checked>';
                echo '<label class="form-check-label" for="' . $rid . '">All</label>';
                echo '</div>';
                foreach ($f['options'] as $val => $txt) {
                    $rid = 'afg_' . $name . '_' . preg_replace('/\W/', '_', $val);
                    echo '<div class="form-check">';
                    echo '<input class="form-check-input afg-input" type="radio" name="' . $name . '" value="' . htmlspecialchars($val) . '" id="' . $rid . '" data-operator="=">';
                    echo '<label class="form-check-label" for="' . $rid . '">' . htmlspecialchars($txt) . '</label>';
                    echo '</div>';
                }
                echo '</div>';
                break;
            case 'boolean':
                echo '<select class="form-select afg-input" name="' . $name . '" data-operator="=">';
                echo '<option value="">-- All --</option>';
                echo '<option value="1">Yes</option>';
                echo '<option value="0">No</option>';
                echo '</select>';
                break;
            default:
                echo '<input type="text" class="form-control afg-input" name="' . $name . '" placeholder="' . $ph . '">';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function fieldCondition(
        string $field,
        string $dependsOn,
        string $operator,
        $value,
        string $action = 'show'
    ): self {
        $this->fieldConditions[] = [
            'field'     => $field,
            'dependsOn' => $dependsOn,
            'operator'  => $operator,
            'value'     => $value,
            'action'    => $action
        ];
        return $this;
    }

    protected function applySubselects(array $rows): array
    {
        if (empty($this->subselects) && empty($this->calculatedFields)) {
            return $rows;
        }
        foreach ($rows as &$row) {
            foreach ($this->subselects as $alias => $sql) {
                $parsedSql = $sql;
                foreach ($row as $key => $value) {
                    $parsedSql = str_replace('{' . $key . '}', $this->db->quote($value), $parsedSql);
                }
                $stmt = $this->db->query($parsedSql);
                $row[$alias] = $stmt ? $stmt->fetchColumn() : null;
            }
            foreach ($this->calculatedFields as $alias => $expr) {
                $parsed = $expr;
                foreach ($row as $key => $value) {
                    $replacement = is_numeric($value) ? $value : '"' . addslashes($value) . '"';
                    $parsed = str_replace('{' . $key . '}', $replacement, $parsed);
                }
                $row[$alias] = $this->safeMathEval($parsed);
            }
        }
        return $rows;
    }

    protected function safeMathEval(string $expr): float
    {
        $expr = preg_replace('/[^0-9+\-*\/().\s]/', '', $expr);
        $expr = trim($expr);
        if ($expr === '' || $expr === null) return 0;
        try {
            return (float) eval("return ($expr);");
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function setActionCondition(string $action, array $conditions): self
    {
        $allowed = ['view', 'edit', 'clone', 'delete', 'checkbox'];
        if (in_array($action, $allowed, true)) {
            $this->actionConditions[$action] = $conditions;
        }
        return $this;
    }

    public function checkboxCondition(array $conditions): self
    {
        return $this->setActionCondition('checkbox', $conditions);
    }

    public function fields_arrange(string $fields, string $groupLabel = '', bool $useRow = false, bool $showLabel = true): self {
        $this->fieldsArrange[] = [
            'fields' => array_map('trim', explode(',', $fields)),
            'label'  => $groupLabel,
            'row'    => $useRow,
            'showLabel' => $showLabel
        ];
        return $this;
    }

    protected function arrangeFields(array $cols): array
    {
        if (empty($this->fieldsArrange)) {
            return [['fields' => $cols, 'label' => '', 'row' => false]];
        }
        $used = [];
        $groups = [];
        foreach ($this->fieldsArrange as $group) {
            $groupFields = [];
            foreach ($group['fields'] as $f) {
                foreach ($cols as $c) {
                    $name = is_array($c) ? $c['name'] : $c;
                    if ($name === $f) {
                        $groupFields[] = $c;
                        $used[] = $name;
                    }
                }
            }
            if (!empty($groupFields)) {
                $groups[] = [
                    'fields' => $groupFields,
                    'label'  => $group['label'],
                    'row'    => $group['row'],
                    'showLabel' => $group['showLabel'] ?? true
                ];
            }
        }
        $remaining = [];
        foreach ($cols as $c) {
            $name = is_array($c) ? $c['name'] : $c;
            if (!in_array($name, $used, true)) {
                $remaining[] = $c;
            }
        }
        if (!empty($remaining)) {
            $groups[] = [
                'fields' => $remaining,
                'label'  => '',
                'row'    => false,
                'showLabel' => false
            ];
        }
        return $groups;
    }

    public function setFieldType(string $field, string $type): self
    {
        $this->customFieldTypes[$field] = $type;
        return $this;
    }

    public function setSelect(string $field, array $options): self
    {
        $this->selectOptions[$field] = $options;
        return $this;
    }

    public function inlineEdit($config = true): self
    {
        if (is_array($config)) {
            $this->inlineEditEnabled = true;
            $this->inlineEditConfig = array_merge([
                'mode' => 'cell',
                'saveOnBlur' => true,
                'highlight' => true
            ], $config);
        } else {
            $this->inlineEditEnabled = (bool)$config;
        }

        return $this;
    }

    public function sendMail(string $to, string $subject, string $body, array $cc = [], array $attachments = []): bool
    {
        require_once __DIR__ . '/vendor/autoload.php';
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mailConfig = $this->config['mail'] ?? [];
            $mail->isSMTP();
            $mail->Host       = $mailConfig['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $mailConfig['username'];
            $mail->Password   = $mailConfig['password'];
            $mail->Port       = $mailConfig['port'] ?? 587;
            if (($mailConfig['secure'] ?? 'tls') === 'tls') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            }
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            $mail->setFrom(
                $mailConfig['from'],
                $mailConfig['from_name'] ?? 'ArtiGrid'
            );
            $mail->addAddress($to);
            foreach ($cc as $email) {
                $mail->addCC($email);
            }
            foreach ($attachments as $file) {
                if (file_exists($file)) {
                    $mail->addAttachment($file);
                }
            }
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->send();
            return true;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("MAIL ERROR: " . $mail->ErrorInfo);
            return false;
        }
    }

    public function radio(string $field, array $options): self
    {
        $this->radioFields[$field] = $options;
        return $this;
    }

    public function checkboxGroup(string $field, array $options, string $separator = ',', int $min = 1): self
    {
        $this->checkboxGroups[$field] = [
            'options'   => $options,
            'separator' => $separator,
            'min'       => $min,
        ];
        return $this;
    }

    protected function ckeditorAttr(string $name): string
    {
        if (!isset($this->ckeditorFields[$name])) {
            return '';
        }
        $cfg = json_encode($this->ckeditorFields[$name]);
        return ' data-ckeditor-field="1" data-ckeditor-options=\'' . htmlspecialchars($cfg, ENT_QUOTES) . '\'';
    }

    public function sendEmailInsert(bool $state = true, array $config = []): self
    {
        $this->sendEmailOnInsert = $state;
        $this->emailConfig = $config;
        return $this;
    }

    public function lang(array $translations): self
    {
        $this->lang = array_merge($this->lang, $translations);
        return $this;
    }

    public function CellColor(string $field, string $operator, $value, $style): self
    {
        $this->columnColors[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
            'style' => $style
        ];
        return $this;
    }

    public function RowColor(string $field, string $operator, $value, $style): self
    {
        $this->rowColors[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
            'style' => $style
        ];
        return $this;
    }

    public function checkDuplicateRecord(array $fields): self
    {
        $this->duplicateFields = $fields;
        return $this;
    }

    public function fieldCss(string $field, array $class): self
    {
        if (!isset($this->fieldCss[$field])) {
            $this->fieldCss[$field] = [];
        }
        $classes = is_array($class)
            ? $class
            : preg_split('/\s+/', trim($class));

        $this->fieldCss[$field] = array_values(
            array_unique(
                array_merge($this->fieldCss[$field], $classes)
            )
        );
        return $this;
    }

    public function where(string $field, string $operator, $value, string $boolean = 'AND')
    {
        $this->whereConditions[] = [
            'field' => $field,
            'operator' => strtoupper($operator),
            'value' => $value,
            'boolean' => strtoupper($boolean)
        ];
        return $this;
    }

    public function whereBetween(string $field, $start, $end, string $boolean = 'AND'): self {
        return $this->where($field, 'BETWEEN', [$start, $end], $boolean); // ← array
    }

    public function whereLike($field, $value, $boolean = 'AND') {
        return $this->where($field, 'LIKE', "%$value%", $boolean);
    }

    public function required(bool $state = true): self
    {
        $this->allFieldsRequired = $state;
        return $this;
    }

    protected function applyDefaultsFromConfig(): void
    {
        if (
            isset($this->config['forms']) &&
            array_key_exists('required_all_fields', $this->config['forms'])
        ) {
            $this->required(
                (bool) $this->config['forms']['required_all_fields']
            );
        }
        $map = [
            'search'           => 'search',
            'filter'           => 'filter',
            'add'              => 'add',
            'view'             => 'view',
            'refresh'          => 'refresh',
            'edit'             => 'edit',
            'clone'            => 'clone',
            'delete'           => 'delete',
            'checkbox'         => 'checkbox',
            'dropdownpage'     => 'dropdownpage',
            'pagination'       => 'pagination',
            'edit_multiple'    => 'edit_multiple',
            'delete_multiple'  => 'delete_multiple'
        ];
        foreach ($map as $configKey => $method) {
            if (
                array_key_exists($configKey, $this->config)
                && method_exists($this, $method)
            ) {
                $this->{$method}((bool)$this->config[$configKey]);
            }
        }
    }
    public function getPDFObject()
    {
        require_once __DIR__ . '/vendor/autoload.php';
        if (!isset($this->pdf)) {
            if (ob_get_length()) {
                ob_clean();
            }
            $this->pdf = new \Mpdf\Mpdf();
        }
        return $this->pdf;
    }

    public function setInsertFormTemplate(string $template): self
    {
        if (is_file($template)) {
            ob_start();
            include $template;
            $template = ob_get_clean();
        }
        $this->insertFormTemplate = $template;
        return $this;
    }

    public function setCrudTemplate(string $html): self
    {
        $this->crudTemplate = $html;
        return $this;
    }

    public function setRowTemplate(
        string $html,
        string $wrapperClass = 'row g-4',
        string $wrapperTag = 'div',
        array $wrapperAttrs = []
    ): self {
        $this->rowTemplate = $html;
        $this->rowWrapperClass = $wrapperClass;
        $this->rowWrapperTag = $wrapperTag;
        $this->rowWrapperAttrs = $wrapperAttrs;
        return $this;
    }

    public function setEditFormTemplate(string $html): self
    {
        $this->editFormTemplate = $html;
        return $this;
    }

    public function setViewFormTemplate(string $html): self
    {
        $this->viewFormTemplate = $html;
        return $this;
    }

    public function setSelectFormTemplate(string $html): self
    {
        $this->selectFormTemplate = $html;
        return $this;
    }

    protected function add(bool $state): self
    {
        $this->actions['add'] = $state;
        return $this;
    }

    protected function view(bool $state): self
    {
        $this->actions['view'] = $state;
        return $this;
    }

    protected function checkbox(bool $state): self
    {
        $this->actions['checkbox'] = $state;
        return $this;
    }

    protected function dropdownpage(bool $state): self
    {
        $this->actions['dropdownpage'] = $state;
        return $this;
    }

    protected function pagination(bool $state): self
    {
        $this->actions['pagination'] = $state;
        return $this;
    }

    protected function refresh(bool $state): self
    {
        $this->actions['refresh'] = $state;
        return $this;
    }

    protected function edit(bool $state): self
    {
        $this->actions['edit'] = $state;
        return $this;
    }

    protected function clone(bool $state): self
    {   
        $this->actions['clone'] = $state;
        return $this;
    }

    protected function delete(bool $state): self
    {
        $this->actions['delete'] = $state;
        return $this;
    }

    protected function delete_multiple(bool $state): self
    {
        $this->actions['delete_multiple'] = $state;
        return $this;
    }

    protected function filter(bool $state): self
    {
        $this->actions['filter'] = $state;
        return $this;
    }

    protected function search(bool $state): self
    {
        $this->actions['search'] = $state;
        return $this;
    }

    public function export(array $types): self
    {
        $allowed = ['excel', 'pdf', 'csv'];
        $this->exportTypes = array_values(
            array_intersect($types, $allowed)
        );
        return $this;
    }

    protected function runCallbacks(array $list, $payload, $context = null)
    {
        foreach ($list as $cb) {
            if (!empty($cb['file'])) {
                $path = str_replace(['\\', '//'], '/', $cb['file']);
                if (!preg_match('~^([a-zA-Z]:/|/)~', $path)) {
                    $path = __DIR__ . '/' . ltrim($path, '/');
                }
                if (is_file($path)) {
                    require_once $path;
                } else {
                    throw new Exception("Callback file not found: {$cb['file']}");
                }
            }
            $fn = $cb['fn'] ?? $cb['callback'] ?? null;
            if (!$fn) {
                throw new Exception("Callback with no function defined");
            }
            if (!is_callable($fn)) {
                throw new Exception("Callback not callable: " . print_r($fn, true));
            }
            try {
                $ref = null;
                if (is_string($fn)) {
                    $ref = new \ReflectionFunction($fn);
                } elseif (is_array($fn) && count($fn) === 2) {
                    $ref = new \ReflectionMethod($fn[0], $fn[1]);
                }
                if ($ref && $ref->getNumberOfParameters() >= 2) {
                    $payload = call_user_func($fn, $payload, $context);
                } else {
                    $payload = call_user_func($fn, $payload);
                }
            } catch (\Throwable $e) {
                throw $e;
            }
        }
        return $payload;
    }

    public function setActions(array $actions): self
    {
        $this->actions = array_merge([], $actions);
        return $this;
    }

    public function nestedTable(
        string $label,
        string $parentKey,
        string $childTable,
        string $childKey,
        array $config = [],
        bool $loadDirect = true
    ): self {
        $dedupKey = $parentKey . '|' . $childTable . '|' . $childKey;
        foreach ($this->nestedGrids as $existing) {
            $existingKey = ($existing['parentKey'] ?? '') . '|'
                        . ($existing['childTable'] ?? '') . '|'
                        . ($existing['childKey'] ?? '');
            if ($existingKey === $dedupKey) {
                return $this;
            }
        }
        $errors = [];
        if (empty($this->table)) {
            $errors[] = "Parent table is not defined. Call table() before nestedTable().";
        }
        if (!empty($this->table)) {
            try {
                $parentCols = array_column($this->dialect->describeTable($this->table), 'Field');
                if (!in_array($parentKey, $parentCols, true)) {
                    $errors[] = "Parent column '{$parentKey}' does not exist in parent table '{$this->table}'.";
                }
            } catch (\Throwable $e) {
                $errors[] = "Error reading parent table '{$this->table}': " . $e->getMessage();
            }
        }
        try {
            $childCols = array_column($this->dialect->describeTable($childTable), 'Field');
            if (!in_array($childKey, $childCols, true)) {
                $errors[] = "Child column '{$childKey}' does not exist in child table '{$childTable}'.";
            }
        } catch (\Throwable $e) {
            $errors[] = "Child table '{$childTable}' does not exist.";
        }
        if (!empty($errors)) {
            $errorHtml = "<div style='padding:12px;background:#fef3c7;border:1px solid #f59e0b;color:#92400e;border-radius:6px;margin:8px 0;font-size:13px'><strong>⚠ nestedTable() error:</strong><ul style='margin:6px 0 0 16px'>";
            foreach ($errors as $err) {
                $errorHtml .= "<li>{$err}</li>";
            }
            $errorHtml .= "</ul></div>";
            $this->nestedGrids[] = [
                'label'      => $label,
                'parentKey'  => $parentKey,
                'nestedGrid' => null,
                'childTable' => $childTable,
                'childKey'   => $childKey,
                'config'     => $config,
                'loadDirect' => false,
                'id'         => 'nested_error_' . count($this->nestedGrids),
                'error'      => $errorHtml,
            ];
            return $this;
        }
        $nestedGridId = $this->id . '_nested_' . count($this->nestedGrids);
        $nestedGrid = new self($this->db);
        $nestedGrid->applyParentConfig($this);
        $nestedGrid->table($childTable);
        $nestedGrid->setId($nestedGridId);
        $defaultNestedConfig = [
            'actions' => [
                'add' => false,
                'edit' => true,
                'delete' => false,
                'view' => true,
                'search' => false,
                'filter' => false,
                'delete_multiple' => false,
                'edit_multiple' => false
            ],
            'required' => false,
            'perPage' => 10
        ];
        $finalConfig = array_merge($defaultNestedConfig, $config);
        if (isset($config['actions'])) {
            $finalConfig['actions'] = array_merge(
                $defaultNestedConfig['actions'],
                $config['actions']
            );
        }
        $finalConfig['requiredFields']    = $config['requiredFields'] ?? [];
        $finalConfig['allFieldsRequired'] = $config['allFieldsRequired'] ?? false;
        if (isset($config['buttonsArrange'])) {
            $config['buttonsDropdown'] = $config['buttonsArrange'];
        }
        $finalConfig = array_merge($defaultNestedConfig, $config);
        $nestedGrid->applyConfig($finalConfig);
        if (!empty($config['nestedGrids'])) {
            $nestedGrid->reconstructNestedTablesFromConfig($config['nestedGrids']);
        }
        $nestedGrid->setAjaxConfig([
            'parent_table'     => $this->table,
            'parent_key'       => $parentKey,
            'child_table'      => $childTable,
            'child_key'        => $childKey,
            'label'            => $label,
            'parent_grid_id'   => $this->id,
            'nest_level'       => ($this->ajaxConfig['nest_level'] ?? 0) + 1,
            'parent_id_placeholder' => '{' . $parentKey . '}'
        ]);
        $_SESSION['artigrid'][$nestedGridId] = [
            'created_at'     => time(),
            'last_used'      => time(),
            'config'         => $nestedGrid->buildConfig(),
            'parent_config'  => $this->buildConfig(),
            'nested_config'  => $finalConfig,
            'table'          => $childTable
        ];    
        $this->nestedGrids[] = [
            'label'      => $label,
            'parentKey'  => $parentKey,
            'nestedGrid' => $nestedGrid,
            'childTable' => $childTable,
            'childKey'   => $childKey,
            'config'     => $finalConfig,
            'loadDirect' => true,
            'id'         => $nestedGridId,
            'error'      => null,
        ];
        return $this;
    }

    public function reconstructNestedTablesFromConfig(array $nestedGridsConfig): self
    {
        if (!empty($this->nestedGrids)) {
            return $this;
        }
        foreach ($nestedGridsConfig as $ng) {
            if (empty($ng['childTable']) || empty($ng['childKey']) || empty($ng['parentKey'])) {
                continue;
            }
            $this->nestedTable(
                $ng['label'] ?? '',
                $ng['parentKey'],
                $ng['childTable'],
                $ng['childKey'],
                $ng['config'] ?? []
            );
        }
        return $this;
    }

    protected function renderNestedInline(array $nt, $parentId): string
    {
        if (!empty($nt['error'])) return $nt['error'];
        $config = $nt['config'] ?? [];
        $ng = new self($this->db);
        $ng->table($nt['childTable']);
        if (!empty($config['actions'])) $ng->setActions(array_merge($ng->getActions(), $config['actions']));
        if (!empty($config['actionsPosition'])) $ng->actionsPosition($config['actionsPosition']);
        if (isset($config['buttonsArrange'])) $ng->buttonsArrange((bool)$config['buttonsArrange']);
        elseif (isset($config['buttonsDropdown'])) $ng->buttonsArrange((bool)$config['buttonsDropdown']);
        if (!empty($config['insertFormTemplate'])) $ng->setInsertFormTemplate($config['insertFormTemplate']);
        if (!empty($config['editFormTemplate'])) $ng->setEditFormTemplate($config['editFormTemplate']);
        if (!empty($config['viewFormTemplate'])) $ng->setViewFormTemplate($config['viewFormTemplate']);
        if (!empty($config['crudTemplate'])) $ng->setCrudTemplate($config['crudTemplate']);
        if (!empty($config['columns'])) $ng->crudCol($config['columns']);
        if (!empty($config['formFields'])) $ng->formFields($config['formFields']);
        if (!empty($config['hiddenColumns'])) $ng->columnHide($config['hiddenColumns']);
        if (isset($config['perPage'])) $ng->perPage((int)$config['perPage']);
        if (!empty($config['perPageOptions'])) $ng->perPageOptions($config['perPageOptions']);
        if (!empty($config['template'])) $ng->template($config['template']);
        if (!empty($config['lang'])) {
            $ng->lang($config['lang']);
        }
        if (isset($config['rowTemplate'])) {
            $ng->setRowTemplate(
                $config['rowTemplate'],
                $config['rowWrapperClass'] ?? 'row g-4',
                $config['rowWrapperTag'] ?? 'div',
                $config['rowWrapperAttrs'] ?? []
            );
        }        if (!empty($config['sortColumn'])) $ng->orderby($config['sortColumn'], $config['sortOrder'] ?? 'asc');
        if (!empty($config['colRename'])) foreach ($config['colRename'] as $from => $to) $ng->colRename($from, $to);
        if (!empty($config['fieldTypes'])) foreach ($config['fieldTypes'] as $field => $type) $ng->fieldType($field, $type);
        if (!empty($config['requiredFields'])) $ng->validation_required($config['requiredFields']);
        if (isset($config['allFieldsRequired'])) $ng->required((bool)$config['allFieldsRequired']);
        if (!empty($config['comboBoxes'])) {
            foreach ($config['comboBoxes'] as $field => $cfg) {
                if (($cfg['source'] ?? '') === 'array') $ng->combobox($field, $cfg['options']);
                elseif (($cfg['source'] ?? '') === 'table') $ng->combobox($field, $cfg['table'], $cfg['value'] ?? null, $cfg['label'] ?? null, $cfg['dependsOn'] ?? null, $cfg['dependsField'] ?? null, $cfg['where'] ?? []);
            }
        }
        if (!empty($config['radioFields'])) foreach ($config['radioFields'] as $field => $options) $ng->radio($field, $options);
        if (!empty($config['checkboxGroups'])) {
            foreach ($config['checkboxGroups'] as $field => $cfg) {
                $ng->checkboxGroup(
                    $field,
                    $cfg['options'] ?? [],
                    $cfg['separator'] ?? ',',
                    $cfg['min'] ?? 1
                );
            }
        }
        if (!empty($config['exportTypes'])) $ng->export($config['exportTypes']);
        if (!empty($config['useModal'])) $ng->modal((bool)$config['useModal']);
        if (!empty($config['inlineEditEnabled'])) $ng->inlineEdit($config['inlineEditConfig'] ?? true);
        if (!empty($config['customButtons'])) foreach ($config['customButtons'] as $btn) $ng->addCustomBtn($btn['class'], $btn['action'], $btn['label'], $btn['title'] ?? '', $btn['conditions'] ?? [], $btn['url'] ?? null, $btn['target'] ?? '_self', $btn['attributes'] ?? []);
        if (!empty($config['duplicateFields'])) $ng->checkDuplicateRecord($config['duplicateFields']);
        if (!empty($config['columnColors'])) foreach ($config['columnColors'] as $cc) $ng->CellColor($cc['field'], $cc['operator'], $cc['value'], $cc['color']);
        if (!empty($config['rowColors'])) foreach ($config['rowColors'] as $rc) $ng->RowColor($rc['field'], $rc['operator'], $rc['value'], $rc['color']);
        if (!empty($config['ckeditorFields'])) foreach ($config['ckeditorFields'] as $field => $opts) $ng->ckeditor($field, $opts);
        if (!empty($config['summernoteFields'])) foreach ($config['summernoteFields'] as $field => $opts) $ng->summernote($field, $opts);
        if (!empty($config['select2Fields']))  foreach ($config['select2Fields']  as $field => $opts) $ng->select2($field, $opts);
        if (!empty($config['chosenFields']))   foreach ($config['chosenFields']   as $field => $opts) $ng->chosen($field, $opts);
        if (!empty($config['imageFieldsConfig'])) foreach ($config['imageFieldsConfig'] as $field => $opts) $ng->imageField($field, $opts);
        if (!empty($config['calendarConfig'])) $ng->calendar($config['calendarConfig']);
        $actionConditions = [];
        if (!empty($nt['nestedGrid']) && $nt['nestedGrid'] instanceof self) $actionConditions = $nt['nestedGrid']->getActionConditions();
        elseif (!empty($config['actionConditions'])) $actionConditions = $config['actionConditions'];
        foreach ($actionConditions as $action => $conditions) { if (!empty($conditions)) $ng->setActionCondition($action, $conditions); }
        if (!empty($config['nestedGrids'])) $ng->reconstructNestedTablesFromConfig($config['nestedGrids']);
        if (!empty($config['joins'])) foreach ($config['joins'] as $join) $ng->join($join['localColumn'], $join['joinTable'], $join['foreignColumn'], $join['type'] ?? 'INNER');
        if (!empty($config['subselects'])) foreach ($config['subselects'] as $alias => $sql) $ng->subselect($alias, $sql);
        if (!empty($config['calculatedFields'])) foreach ($config['calculatedFields'] as $alias => $expression) $ng->calculate($alias, $expression);
        if (!empty($config['summaryRow'])) $ng->summary($config['summaryRow'], $config['summaryConfig'] ?? []);
        $ng->where($nt['childKey'], '=', (string)$parentId);
        $ng->formFieldValue($nt['childKey'], (string)$parentId);
        if (empty($config['formFields'])) $ng->fieldType($nt['childKey'], 'hidden');
        return $ng->render('crud');
    }

    public function getActionConditions(): array
    {
        return $this->actionConditions;
    }

    public function applyParentConfig(ArtiGrid $parentGrid): self
    {
        $parentConfig = $parentGrid->buildConfig();
        $blacklist = [
            'table', 'crudTable', 'primaryKey', 'ajaxConfig', 'nestedGrids',
            'id', 'runtimeGridId', 'db', 'pdo',
            'columns',
            'formFields',
            'editFormFields',
            'viewFormFields',
            'requiredFields',
            'requiredGroups',
            'formFieldValues',
            'whereConditions',
            'colRename',
            'fieldTypes',
            'customFieldTypes',
            'comboBoxes',
            'radioFields',
            'selectOptions',
            'hiddenColumns',
            'duplicateFields',
            'insertFormTemplate',
            'editFormTemplate',
            'viewFormTemplate',
        ];
        foreach ($parentConfig as $key => $value) {
            if (in_array($key, $blacklist, true)) {
                continue;
            }
            if ($key === 'actions') {
                $this->actions = array_merge($this->actions, $value);
            } elseif ($key === 'whereConditions' || $key === 'where') {
                $this->whereConditions = $value;
            } elseif ($key === 'columns') {
              
            } elseif (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return $this;
    }

    public function setAjaxConfig(array $config): void {
        $this->ajaxConfig = array_merge($this->ajaxConfig ?? [], $config);
    }
    
    public function getAjaxConfig(): ?array {
        return $this->ajaxConfig;
    }

    public function insertData(array $data, string $gridId)
    {
        $mainTable = $this->table;
        $session = $_SESSION['artigrid'][$gridId] ?? [];
        $config = $session['config'] ?? [];
        $this->config = array_merge($this->config ?? [], $config ?? []);
        $sendEmailOnInsert = $config['sendEmailOnInsert'] ?? false;
        $emailCfg = $config['emailConfig'] ?? [];
        $callbacks = $config['callbacks'] ?? [
            'beforeInsert' => [],
            'afterInsert'  => []
        ];
        $duplicateFields = !empty($this->duplicateFields)
            ? $this->duplicateFields
            : ($config['duplicateFields'] ?? []);
        try {
            $this->db->beginTransaction();
            $data = $this->runCallbacks($callbacks['beforeInsert'], $data, $this);
            if (!empty($duplicateFields)) {
                foreach ($duplicateFields as $field) {
                    if (!isset($data[$field])) continue;
                    $sql = "SELECT COUNT(*) FROM {$this->table} WHERE {$field} = :val LIMIT 1";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bindValue(":val", $data[$field]);
                    $stmt->execute();
                    if ($stmt->fetchColumn() > 0) {
                        return [
                            'success' => false,
                            'errors' => [
                                $field => ["This value already exists"]
                            ]
                        ];
                    }
                }
            }
            $mainColsInfo = $this->getTableColumns();
            $mainCols = array_column($mainColsInfo, 'name');
            $mainData = [];
            foreach ($data as $k => $v) {
                if (in_array($k, $mainCols)) {
                    $mainData[$k] = $v;
                }
            }
            if (!empty($this->primaryKey)) {
                foreach ($this->dialect->describeTable($this->table) as $col) {
                    if ($col['Field'] === $this->primaryKey && strpos($col['Extra'] ?? '', 'auto_increment') !== false) {
                        unset($mainData[$this->primaryKey]);
                        break;
                    }
                }
            }
            $mainData = $this->normalizeTemporalData($mainData);
            $cols = array_keys($mainData);
            $d = $this->dialect;
            $escapedCols = array_map(fn($col) => $d->quote($col), $cols);
            $placeholders = array_map(function ($c) {
                return ":$c";
            }, $cols);

            $sql = "INSERT INTO `{$mainTable}` (" . implode(',', $escapedCols) . ")
                    VALUES (" . implode(',', $placeholders) . ")";
            $stmt = $this->db->prepare($sql);
            foreach ($mainData as $k => $v) {
                $stmt->bindValue(":$k", $v);
            }
            $stmt->execute();
            $lastId = $this->db->lastInsertId();
            if (!empty($sendEmailOnInsert)) {
                try {
                    $to      = $emailCfg['to'];
                    $subject = $emailCfg['subject'];
                    $body    = $emailCfg['body'] ?? '<pre>' . print_r($data, true) . '</pre>';
                    if (is_string($body)) {
                        $body = str_replace('{id}', $lastId, $body);
                        foreach ($data as $k => $v) {
                            if ($k === $this->primaryKey) continue;
                            $body = str_replace('{'.$k.'}', (string)($v ?? ''), $body);
                        }
                    }
                    $toList = array_unique(is_array($to) ? $to : [$to]);
                    foreach ($toList as $mail) {
                        $this->sendMail($mail, $subject, $body);
                    }
                } catch (\Throwable $e) {
                    error_log('Email send failed: ' . $e->getMessage());
                }
            }
            $payload = ['lastId' => $lastId, 'data' => $data];
            $this->runCallbacks($callbacks['afterInsert'], $payload, $this);
            $this->db->commit();
            return $lastId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getId(): string {
        return $this->id;
    }

    public function getFullConfig(): array {
        return [
            'columns' => $this->columns ?? [],
            'where' => $this->whereConditions ?? [],
            'actions' => $this->actions ?? [],
            'perPage' => $this->perPage ?? 10,
            'sortColumn' => $this->sortColumn,
            'sortOrder' => $this->sortOrder,
            'ajaxConfig' => $this->ajaxConfig ?? null,
            'table' => $this->table ?? ''
        ];
    }
    
    public function clearWhere(): self {
        $this->whereConditions = [];
        return $this;
    }

    public function summary(array $columns, array $config = []): self
    {
        $this->summaryRow = $columns;
        $this->summaryConfig = array_merge($this->summaryConfig, $config);
        return $this;
    }

    public function chart_labels(array $labels, array $datasets, string $type = 'bar', array $options = []): self 
    {
        $this->chartLabels   = $labels;
        $this->chartDatasets = $datasets;
        $this->chartType     = $type;
        $this->chartOptions  = $options;
        return $this;
    }

    public function chart_view(bool $state = true): self
    {
        $this->useChart = $state;
        return $this;
    }

    protected function resolveChartData(string $data)
    {
        if (!is_string($data) || strpos($data, '#select') !== 0) {
            return $data;
        }
        $sql = ltrim($data, '#');
        try {
            $stmt = $this->db->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return array_map('floatval', $rows);
        } catch (Throwable $e) {
            return [];
        }
    }

    protected function renderChart(): string
    {
        $chartId = $this->id . '_chart';
        $datasets = [];
        foreach ($this->chartDatasets as $ds) {

            $datasets[] = array_merge($ds, [
                'data' => $this->resolveChartData($ds['data'])
            ]);
        }
        $labels  = json_encode($this->chartLabels);
        $dataSet = json_encode($datasets);
        $options = json_encode($this->chartOptions);
        ob_start(); ?>
        <div class="card mb-4 p-3">
            <canvas id="<?= $chartId ?>"></canvas>
        </div>
        <script src="<?=$this->getBaseUrl()?>assets/js/chart.js"></script>
        <script>
            const ctx_<?= $chartId ?> = document.getElementById('<?= $chartId ?>');
            new Chart(ctx_<?= $chartId ?>, {
                type: '<?= $this->chartType ?>',
                data: {
                    labels: <?= $labels ?>,
                    datasets: <?= $dataSet ?>
                },
                options: <?= $options ?: '{}' ?>
            });
        </script>
        <?php
        return ob_get_clean();
    }

    public function formFields(array $fields): self
    {
        $this->formFields = $fields;
        return $this;
    }

    public function editFormFields(array $fields): self
    {
        $this->editFormFields = $fields;
        return $this;
    }

    public function viewFormFields(array $fields): self
    {
        $this->viewFormFields = $fields;
        return $this;
    }

    public function formFieldValue(string $field, string $value)
    {
        $this->formFieldValues[$field] = $value;
        return $this;
    }

    protected function resolveFormFields(string $mode = 'add'): array
    {
        if ($mode === 'edit' && !empty($this->editFormFields)) {
            return $this->editFormFields;
        }

        return $this->formFields ?? $this->getTableColumns();
    }

    protected function resolveViewFields(string $mode = 'add'): array
    {
        if ($mode === 'view' && !empty($this->viewFormFields)) {
            return $this->viewFormFields;
        }
        return $this->formFields ?? $this->getTableColumns();
    }

    public function fieldAttr(string $field, array $attrs): self
    {
        $this->fieldAttributes[$field] = $attrs;
        return $this;
    }

    public function crudJson(array $data): self
    {
        if (empty($data['columns']) || !is_array($data['columns'])) {
            throw new InvalidArgumentException("You must provide a 'columns' array");
        }
        $this->jsonColumns = $data['columns'];
        $this->jsonRows    = $data['rows'] ?? [];
        $this->table = '';
        $this->useQuery = false;
        $this->crudTable = null;
        $this->actions['add'] = true;
        $this->actions['view'] = false;
        $this->actions['edit'] = false;
        $this->actions['delete'] = false;
        $this->actions['delete_multiple'] = false;
        $this->actions['checkbox'] = false;
        return $this;
    }

    public function combobox(
        string $field,
        $table,
        ?string $valueCol = null,
        $labelCol = null,
        ?string $dependsOn = null,
        ?string $dependsField = null,
        array $where = []
    ): self {
        if (!is_string($table) && !is_array($table)) {
            throw new InvalidArgumentException(
                'Argument $table must be of type string or array'
            );
        }
        if (is_array($table)) {
            $this->comboBoxes[$field] = [
                'source'  => 'array',
                'options' => $table
            ];
        } else {
            $this->comboBoxes[$field] = [
                'source' => 'table',
                'table'  => $table,
                'value'  => $valueCol,
                'label'  => $labelCol,
                'dependsOn' => $dependsOn,
                'dependsField' => $dependsField,
                'where' => $where
            ];
        }
        return $this;
    }

    public function delete_bulk_select(bool $show = true, string $field = '', string $operator = '', $value = null): self 
    {
        $this->actions['delete_multiple'] = $show;
        if ($field && $operator) {
            $this->bulkDeleteCondition = [
                'field'    => $field,
                'operator' => $operator,
                'value'    => $value
            ];
        }
        return $this;
    }

    public function template(string $name): self
    {
        $allowed = ['bootstrap4', 'bootstrap5'];
        if (!in_array($name, $allowed, true)) {
            throw new InvalidArgumentException("Template not supported: $name");
        }
        $this->template = $name;
        return $this;
    }

    public function orderby(string $column, string $order = 'asc'): self
    {
        $this->sortColumn = $column;
        $this->sortOrder  = strtolower($order) === 'desc' ? 'desc' : 'asc';
        return $this;
    }

    public function groupby(array $columns): self
    {
        if (is_string($columns)) {
            $this->groupByColumns = [$columns];
        } elseif (is_array($columns)) {
            $this->groupByColumns = $columns;
        }
        return $this;
    }

    protected function buildOrderBy(): string
    {
        if ($this->sortColumn) {
            $dir = strtoupper($this->sortOrder ?? 'ASC');
            if (!in_array($dir, ['ASC','DESC'], true)) $dir = 'ASC';
            return "ORDER BY " . $this->dialect->quote($this->sortColumn) . " $dir";
        }
        return '';
    }

    public function actionsPosition(string $position)
    {
        $position = strtolower($position);
        if (!in_array($position, ['left', 'right'])) {
            $position = 'right';
        }
        $this->config['actionsPosition'] = $position;
        return $this;
    }

    protected function getDataSQL(): string
    {
        $select = $this->buildSelect();
        $joins  = $this->buildJoins();
        $order  = $this->buildOrderBy();
        return "SELECT $select FROM `{$this->table}` $joins $order";
    }

    public function validation_required($field): self
    {
        $fields = is_array($field) ? $field : [$field];
        foreach ($fields as $f) {
            if (!in_array($f, $this->requiredFields, true)) {
                $this->requiredFields[] = $f;
            }
        }
        return $this;
    }

    public function validation_required_group(array $fields): self
    {
        if (count($fields) > 1) {
            $this->requiredGroups[] = $fields;
        }
        return $this;
    }

    public function getRequiredFields(): array
    {
        return $this->requiredFields;
    }

    public function join(string $localColumn, string $joinTable, string $foreignColumn, string $type = 'INNER'): self 
    {
        $type = strtoupper(trim($type));
        if (!in_array($type, ['INNER', 'LEFT', 'RIGHT'])) {
            throw new InvalidArgumentException("Invalid JOIN type $type");
        }
        if (!$this->table) {
            throw new Exception("You must define table() before using join().");
        }
        $this->joins[] = [
            'type'   => $type,
            'table'  => $joinTable,
            'on'     => "{$this->table}.{$localColumn} = {$joinTable}.{$foreignColumn}"
        ];
        return $this;
    }

    protected function buildSelect(): string
    {
        if (!empty($this->selectColumns)) {
            return implode(', ', $this->selectColumns);
        }
        $d = $this->dialect;
        $select = [];
        $select[] = $d->quote("{$this->table}.{$this->primaryKey}") . " AS " . $d->quote($this->primaryKey);
        foreach ($d->describeTable($this->table) as $col) {
            $name = $col['Field'];
            if ($name === $this->primaryKey) continue;
            $select[] = $d->quote("{$this->table}.{$name}");
        }
        foreach ($this->joins as $join) {
            foreach ($d->describeTable($join['table']) as $col) {
                $colName = $col['Field'];
                $alias = "{$join['table']}_{$colName}";
                $select[] = $d->quote("{$join['table']}.{$colName}") . " AS " . $d->quote($alias);
            }
        }
        return implode(', ', $select);
    }

    public function addCustomBtn(string $class, string $action, string $label, string $title = '', array $conditions = [], ?string $url = null, string $target = '_self', array $attributes = []): self
    {
        $this->customButtons[] = [
            'class'      => $class,
            'action'     => $action,
            'label'      => $label,
            'title'      => $title,
            'conditions' => $conditions,
            'url'        => $url,
            'target'     => $target,
            'attributes' => $attributes
        ];
        return $this;
    }

    protected function renderRowActions(array $row): string
    {
        $html = '';
        $id = $row[$this->primaryKey] ?? '';
        foreach ($this->customButtons as $btn) {
            $show = true;
            if (!empty($btn['conditions'])) {
                [$field, $operator, $value] = $btn['conditions'];
                $cellValue = $row[$field] ?? null;
                switch ($operator) {
                    case '!=':
                        $show = $cellValue != $value;
                        break;
                    case '==':
                    case '=':
                        $show = $cellValue == $value;
                        break;
                    case '>':
                        $show = $cellValue > $value;
                        break;
                    case '<':
                        $show = $cellValue < $value;
                        break;
                    case '>=':
                        $show = $cellValue >= $value;
                        break;
                    case '<=':
                        $show = $cellValue <= $value;
                        break;
                    case 'in':
                        $show = is_array($value)
                            && in_array($cellValue, $value);
                        break;
                    case 'not in':
                        $show = is_array($value)
                            && !in_array($cellValue, $value);
                        break;
                    default:
                        $show = false;
                }
            }
            if (!$show) {
                continue;
            }
            $label = $btn['label'];
            $title = $btn['title'];
            $url   = $btn['url'];
            foreach ($row as $field => $value) {
                $placeholder = '{' . $field . '}';
                $label = str_replace($placeholder, $value, $label);
                $title = str_replace($placeholder, $value, $title);
                if ($url !== null) {
                    $url = str_replace($placeholder, $value, $url);
                }
            }
            $attributes = '';
            foreach ($btn['attributes'] as $attribute => $value) {
                foreach ($row as $field => $rowValue) {
                    $value = str_replace(
                        '{' . $field . '}',
                        $rowValue,
                        $value
                    );
                }
                $attributes .= ' '
                    . htmlspecialchars($attribute, ENT_QUOTES, 'UTF-8')
                    . '="'
                    . htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
                    . '"';
            }
            if (!empty($url)) {
                $html .= '<a'
                    . ' href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"'
                    . ' class="' . htmlspecialchars($btn['class'], ENT_QUOTES, 'UTF-8') . '"'
                    . ' target="' . htmlspecialchars($btn['target'], ENT_QUOTES, 'UTF-8') . '"'
                    . ' title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '"'
                    . $attributes
                    . '>'
                    . $label
                    . '</a> ';
            } else {
                $html .= '<button'
                    . ' class="' . htmlspecialchars($btn['class'], ENT_QUOTES, 'UTF-8') . '"'
                    . ' data-action="' . htmlspecialchars($btn['action'], ENT_QUOTES, 'UTF-8') . '"'
                    . ' title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '"'
                    . ' data-id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"'
                    . $attributes
                    . '>'
                    . $label
                    . '</button> ';
            }
        }
        return $html;
    }

    public function primaryKey(string $col): self
    {
        $this->primaryKey = $col;
        return $this;
    }

    public function fieldType(string $field, string $type): self
    {
        $this->fieldTypes[$field] = $type;
        return $this;
    }

    public function table(string $table): self
    {
        $this->table = $table;
        if (is_string($this->id) && strpos($this->id, 'artigrid_') === 0) {
            $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $table);
            $this->id = $safeId ?: $this->id;
        }
        try {
            $columns = $this->dialect->describeTable($table);
            if (!$columns) {
                throw new Exception("The table '$table' does not exist or has no columns.");
            }
            foreach ($columns as $col) {
                if ($col['Key'] === 'PRI') {
                    $this->primaryKey = $col['Field'];
                    break;
                }
            }
        } catch (Throwable $e) {
            echo "
            <div style='
                padding:15px;
                background:#fee2e2;
                border:1px solid #ef4444;
                color:#991b1b;
                border-radius:6px;
                margin:10px 0;
                font-family:Arial'>
                <strong>ArtiGrid Error:</strong>The table <b>$table</b> does not exist in the database.
            </div>
            ";
            exit;
        }
        return $this;
    }

    public function editable(string $table): self
    {
        $this->baseTable = $table;
        $this->table($table);
        $this->actions['add'] = true;
        $this->actions['view'] = true;
        $this->actions['edit'] = true;
        $this->actions['delete'] = true;
        $this->actions['delete_multiple'] = true;
        return $this;
    }

    public function query(string $sql): self
    {
        $this->query = trim($sql);
        $this->useQuery = true;
        $this->table = '';
        $this->actions['add'] = false;
        $this->actions['view'] = false;
        $this->actions['edit'] = false;
        $this->actions['delete'] = false;
        $this->actions['delete_multiple'] = false;
        return $this;
    }

    public function buttonsArrange(bool $state = true): self
    {
        $this->buttonsDropdown = $state;
        return $this;
    }

    public function modal(bool $state = true): self
    {
        $this->useModal = $state;
        return $this;
    }

    public function perPage(int $n): self
    {
        $this->perPage = $n;
        return $this;
    }

    public function perPageOptions(array $options): self
    {
        $this->perPageOptions = $options;
        return $this;
    }

    public function crudCol(array $cols): self
    {
        $this->columns = $cols;
        return $this;
    }

    public function colRename(string $from, string $to): self
    {
        $this->colRename[$from] = $to;
        return $this;
    }

    public function unset(string $action, bool $value = false): self
    {
        if (array_key_exists($action, $this->actions)) {
            $this->actions[$action] = $value;
        }
        return $this;
    }

    public function columnHide(array $cols): self
    {
        if (is_string($cols)) {
            $cols = array_map('trim', explode(',', $cols));
        }
        foreach ($cols as $c) {
            if (!in_array($c, $this->hiddenColumns, true)) {
                $this->hiddenColumns[] = $c;
            }
        }
        return $this;
    }

    protected function getColumns(): array
    {
        $columns = [];
        if (!empty($this->columns)) {
            $columns = array_map(function($c) {
                return [
                    'name'  => $c,
                    'label' => $this->colRename[$c] ?? $c,
                    'type'  => 'text'
                ];
            }, $this->columns);
        } elseif (!empty($this->jsonColumns)) {
            $columns = array_map(function($c) {
                return [
                    'name'  => $c['name'],
                    'label' => $c['label'] ?? $c['name'],
                    'type'  => $c['type'] ?? 'text'
                ];
            }, $this->jsonColumns);
        } elseif ($this->useQuery) {
            $columns = $this->crudTable ? $this->getCrudTableColumns() : $this->getQueryColumns();
        } elseif (!empty($this->joins)) {
            $columns = $this->getJoinedColumns();
        } else {
            $columns = $this->getTableColumns();
        }
        $columns = array_filter($columns, function ($c) {
            $name = is_array($c) ? $c['name'] : $c;
            return !in_array($name, $this->hiddenColumns, true);
        });
        return array_values($columns);
    }

    protected function getJoinedColumns(): array
    {
        $sql = "
            SELECT {$this->buildSelect()}
            FROM {$this->table}
            {$this->buildJoins()}
            LIMIT 1
        ";
        $stmt = $this->db->query($sql);
        $cols = [];
        for ($i = 0; $i < $stmt->columnCount(); $i++) {
            $meta = $stmt->getColumnMeta($i);
            $name = $meta['name'];
            $cols[] = [
                'name'  => $name,
                'label' => $this->colRename[$name] ?? $name,
                'type'  => 'text'
            ];
        }
        return $cols;
    }

    protected function getCrudTableColumns(): array
    {
        $rows = $this->dialect->describeTable($this->crudTable);
        $columns = [];
        foreach ($rows as $row) {
            $columns[] = [
                'name'  => $row['Field'],
                'label' => $this->colRename[$row['Field']] ?? ucfirst($row['Field']),
                'type'  => $row['Type'],
                'key'   => $row['Key'],
                'null'  => $row['Null'],
                'extra' => $row['Extra'],
            ];
        }
        return $columns;
    }

    protected function getTableColumns(): array
    {
        if (!empty($this->jsonColumns)) {
            return $this->jsonColumns;
        }
        if (!$this->table) {
            return [];
        }
        static $cache = [];
        if (isset($cache[$this->table])) {
            return $cache[$this->table];
        }
        $rows = $this->dialect->describeTable($this->table);
        $columns = [];
        $primaryKeyFound = false;
        foreach ($rows as $row) {
            $name = $row['Field'];
            if (!$primaryKeyFound && empty($this->primaryKey)) {
                if ($row['Key'] === 'PRI') {
                    $this->primaryKey = $name;
                    $primaryKeyFound = true;
                }
            }
            $columns[] = [
                'name'  => $name,
                'label' => $this->colRename[$name] ?? ucfirst(str_replace('_',' ',$name)),
                'type'  => $row['Type'],
                'key'   => $row['Key'],
                'null'  => $row['Null'],
                'extra' => $row['Extra'],
            ];
        }
        if (!$primaryKeyFound && empty($this->primaryKey)) {
            $this->primaryKey = 'id';
        }
        return $cache[$this->table] = $columns;
    }

    protected function getQueryColumns(): array
    {
        $db = $this->db;
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        switch ($driver) {
            case 'mysql':
            case 'pgsql':
                $sql = "SELECT * FROM ({$this->query}) AS artigrid_sub LIMIT 1";
                break;
            case 'sqlsrv':
                $sql = "SELECT TOP 1 * FROM ({$this->query}) AS artigrid_sub";
                break;
            case 'oci':
                $sql = "SELECT * FROM ({$this->query}) artigrid_sub WHERE ROWNUM = 1";
                break;
            default:
                throw new Exception("Driver {$driver} not supported");
        }
        $stmt = $db->query($sql);
        $cols = [];
        for ($i = 0; $i < $stmt->columnCount(); $i++) {
            $meta = $stmt->getColumnMeta($i);
            $cols[] = [
                'name'  => $meta['name'],
                'label' => $this->colRename[$meta['name']] ?? $meta['name'],
                'type'  => 'text',
            ];
        }
        return $cols;
    }

    protected function buildJoins(): string
    {
        if (empty($this->joins)) return '';
        $sql = '';
        foreach ($this->joins as $j) {
            $sql .= " {$j['type']} JOIN {$j['table']} ON {$j['on']} ";
        }
        return $sql;
    }

    protected function getRowById($id): array
    {
        if (!$this->primaryKey) {
            throw new Exception("Primary key has not been defined for the table {$this->table}");
        }
        $d = $this->dialect;
        $sql = "SELECT * FROM " . $d->quote($this->table)
            . " WHERE " . $d->quote($this->primaryKey) . " = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function render(string $mode = 'crud', $param = null): string
    {
        if (empty($this->table) && !$this->useQuery && !$this->jsonColumns) {
            throw new Exception("ArtiGrid: No table defined. Call table() before render().");
        }
        $columns = [];
        if ($this->table) {
            $columns = $this->getTableColumns();
        } elseif ($this->jsonColumns) {
            $columns = $this->jsonColumns;
        }
        if ($mode === 'edit' && $param !== null && $this->table) {
            if (empty($this->primaryKey)) {
                $this->getTableColumns();
            }
            $stmt = $this->db->prepare("
                SELECT *
                FROM {$this->table}
                WHERE {$this->primaryKey} = ?
                LIMIT 1
            ");
            $stmt->execute([$param]);
            $this->editRowData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        }
        if ($mode === 'view' && $param !== null && $this->table) {
            if (empty($this->primaryKey)) {
                $this->getTableColumns();
            }
            $stmt = $this->db->prepare("
                SELECT *
                FROM {$this->table}
                WHERE {$this->primaryKey} = ?
                LIMIT 1
            ");
            $stmt->execute([$param]);
            $this->viewRowData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        }
        if ($this->useChart) {
            return $this->renderChart();
        }
        if (!isset($_SESSION['artigrid'][$this->id])) {
            $_SESSION['artigrid'][$this->id] = [];
        }
        $fullConfig = $this->buildConfig();
        $_SESSION['artigrid'][$this->id] = array_merge(
            $_SESSION['artigrid'][$this->id],
            [
                'table'      => $this->table,
                'primaryKey' => $this->primaryKey,
                'columns'    => $columns,
            ]
        );
        $_SESSION['artigrid'][$this->id]['config'] = $fullConfig;
        if (!empty($this->callbacks)) {
            $_SESSION['artigrid'][$this->id]['config']['callbacks'] = $this->callbacks;
        }
        switch ($mode) {
            case 'insert':
                return $this->renderInsertForm();
            case 'view':
                return $this->renderViewForm($param);
            case 'edit':
                return $this->renderEditForm($param);
            case 'select':
                return $this->renderSelectForm('default_login', 'Login', 'table');
            case 'calendar':
                return $this->renderCalendar();
            case 'onepage':
                return $this->renderOnePage();
            case 'timeline':
                return $this->renderTimeline();
            default:
                $crudHtml = $this->renderCrud();
                if (!empty($this->advancedFilters)) {
                    return $this->renderWithFilter($crudHtml);
                }
                return $crudHtml;
        }
    }

    protected function renderTimeline(): string
    {
        if (empty($this->table)) {
            throw new Exception("ArtiGrid timeline: define table() before render('timeline').");
        }
        $tl = $this->timelineConfig;
        if (empty($tl['dateField'])) {
            foreach ($this->getDateColumns() as $dc) { $tl['dateField'] = $dc; break; }
        }
        if (empty($tl['titleField'])) {
            foreach ($this->getTableColumns() as $col) {
                if (!in_array($col['name'], [$this->primaryKey, $tl['dateField']], true)) {
                    $tl['titleField'] = $col['name']; break;
                }
            }
        }
        $this->timelineConfig = $tl;
        $this->timelineMode = true;
        $html = $this->renderCrud();
        $this->loadTimelineAssets();
        return $html;
    }

    protected function loadTimelineAssets(): void
    {
        static $loaded = false;
        if ($loaded) return;
        $loaded = true;
        ?>
        <link rel="stylesheet" href="<?= $this->getBaseUrl() ?>assets/css/artigrid-timeline.css">
        <?php
    }

    protected function renderCalendar(): string
    {
        if (empty($this->table)) {
            throw new Exception("ArtiGrid calendar: define table() before render('calendar').");
        }
        if (!isset($_SESSION['artigrid'])) {
            $_SESSION['artigrid'] = [];
        }
        $originalGridId = $this->id;
        if (empty($this->runtimeGridId)) {
            $this->runtimeGridId = $this->id . '_' . substr(md5(uniqid('', true)), 0, 8);
        }
        $gridId = $this->runtimeGridId;
        $columns = $this->getTableColumns();
        $_SESSION['artigrid'][$gridId] = array_merge(
            $_SESSION['artigrid'][$gridId] ?? [],
            [
                'table'      => $this->table,
                'primaryKey' => $this->primaryKey,
                'columns'    => $columns,
            ]
        );
        $_SESSION['artigrid'][$gridId]['config'] = $this->buildConfig();
        if (!empty($this->callbacks)) {
            $_SESSION['artigrid'][$gridId]['config']['callbacks'] = $this->callbacks;
        }
        $_SESSION['artigrid'][$gridId]['original_id'] = $originalGridId;
        $token   = $this->csrfToken();
        $calCfg  = $this->calendarConfig;
        $useModal = true;
        $configForJs = $this->buildConfig();
        $configForJs['useModal'] = true;
        ob_start();
        ?>
        <div id="<?= $gridId ?>"
            class="artigrid-container artigrid-calendar-container card shadow-sm mb-4"
            data-csrf="<?= $token ?>"
            data-table="<?= htmlspecialchars($this->table) ?>"
            data-primary-key="<?= htmlspecialchars($this->primaryKey) ?>"
            data-grid-id="<?= $gridId ?>"
            data-where='<?= htmlspecialchars(json_encode($this->whereConditions ?: []), ENT_QUOTES) ?>'
            data-actions='<?= json_encode($this->actions) ?>'
            data-calendar='<?= htmlspecialchars(json_encode($calCfg), ENT_QUOTES) ?>'
            data-config='<?= htmlspecialchars(json_encode($configForJs), ENT_QUOTES) ?>'
            data-lang='<?= json_encode($this->lang) ?>'
            data-baseurl='<?= $this->getBaseUrl() ?>'>
            <div class="artigrid-crud-view">
                <div class="artigrid-spinner-overlay" style="display:none;">
                    <div class="artigrid-spinner"></div>
                </div>
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><?= htmlspecialchars($this->table) ?></h6>
                    <?php if ($this->actions['add']): ?>
                        <button class="btn btn-primary btn-sm artigrid-calendar-add" data-action="add">
                            <i class="fa fa-circle-plus"></i> <?= $this->lang['add'] ?>
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="artigrid-calendar"></div>
                </div>
            </div>
        </div>
        <?php
        echo $this->renderModal($gridId);
        $this->loadAssetsOnce();
        $this->loadCalendarAssets();
        return ob_get_clean();
    }

    protected function loadCalendarAssets(): void
    {
        static $loaded = false;
        if ($loaded) return;
        $loaded = true;
        ?>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales-all.global.min.js"></script>
        <script src="<?= $this->getBaseUrl() ?>assets/js/artigrid-calendar.js"></script>
        <?php
    }

    public function setOnePagePosition(string $position): self
    {
        $allowed = ['left', 'right', 'top', 'bottom'];
        if (in_array($position, $allowed)) {
            $this->onePagePosition = $position;
        }
        return $this;
    }

    protected function renderOnePage(): string
    {
        $this->mode = 'onepage';
        $this->actions['add'] = false;
        $crudHtml = $this->renderCrud();
        $formHtml = $this->renderInsertForm();
        if ($this->onePagePosition === 'right') {
            return '
            <div class="artigrid-onepage container-fluid">
                <div class="row">
                    <div class="col-md-8">
                        ' . $crudHtml . '
                    </div>
                    <div class="col-md-4">
                        ' . $formHtml . '
                    </div>
                </div>
            </div>';
        } elseif ($this->onePagePosition === 'left') {
            return '
            <div class="artigrid-onepage container-fluid">
                <div class="row">
                    <div class="col-md-4">
                        ' . $formHtml . '
                    </div>
                    <div class="col-md-8">
                        ' . $crudHtml . '
                    </div>
                </div>
            </div>';
        } elseif ($this->onePagePosition === 'top') {
            return '
            <div class="artigrid-onepage">
                <div class="mb-3">
                    ' . $formHtml . '
                </div>
                ' . $crudHtml . '
            </div>';
        } elseif ($this->onePagePosition === 'bottom') {
            return '
            <div class="artigrid-onepage">
                ' . $crudHtml . '
                <div class="mt-3">
                    ' . $formHtml . '
                </div>
            </div>';
        }
        return $crudHtml;
    }

    public function importConfig(array $config): void
    {
        foreach ($config as $key => $value) {
            if (!property_exists($this, $key)) {
                continue;
            }
            if ($value === null) {
                continue;
            }
            if (in_array($key, ['db', 'pdo', 'id', 'runtimeGridId', 'nestedGrids', 'callbacks'], true)) {
                continue;
            }
            $this->$key = $value;
        }
    }

    protected function evaluateCondition(string $value, string $operator, string $compare)
    {
        switch ($operator) {
            case '<': return $value < $compare;
            case '>': return $value > $compare;
            case '<=': return $value <= $compare;
            case '>=': return $value >= $compare;
            case '==': return $value == $compare;
            case '!=': return $value != $compare;
        }
        return false;
    }

    protected function buildConfig(): array
    {
        return [
            'table' => $this->table,
            'crudTable' => $this->crudTable,
            'primaryKey' => $this->primaryKey,
            'lang' => $this->lang,
            'insertFormTemplate' => $this->insertFormTemplate,
            'editFormTemplate' => $this->editFormTemplate,
            'viewFormTemplate' => $this->viewFormTemplate,
            'selectFormTemplate' => $this->selectFormTemplate,
            'perPage' => $this->perPage,
            'perPageOptions' => $this->perPageOptions,
            'columns' => $this->columns,
            'formFields' => $this->formFields,
            'timelineConfig' => $this->timelineConfig,
            'viewFormFields' => $this->viewFormFields,
            'editFormFields' => $this->editFormFields,
            'fieldCss' => $this->fieldCss,
            'colorFields' => $this->colorFields,
            'formFieldValues' => $this->formFieldValues,
            'fieldAttributes' => $this->fieldAttributes,
            'colRename' => $this->colRename,
            'fieldTypes' => $this->fieldTypes,
            'joins' => $this->joins,
            'where' => $this->whereConditions ?: [],
            'allFieldsRequired' => $this->allFieldsRequired,
            'fieldConditions' => $this->fieldConditions,
            'requiredGroups' => $this->requiredGroups,
            'selectColumns' => $this->selectColumns,
            'requiredFields' => $this->requiredFields,
            'actions' => $this->actions,
            'advancedFilterLazy' => $this->advancedFilterLazy ?? false,
            'template' => $this->template,
            'comboBoxes' => $this->comboBoxes,
            'jsonColumns' => $this->jsonColumns,
            'jsonRows' => $this->jsonRows,
            'callbacks' => $this->callbacks,
            'duplicateFields' => $this->duplicateFields,
            'columnColors' => $this->columnColors,
            'summaryRow'    => $this->summaryRow,
            'summaryConfig' => $this->summaryConfig,
            'rowColors' => $this->rowColors,
            'exportTypes' => $this->exportTypes,
            'sortColumn' => $this->sortColumn,
            'sortOrder' => $this->sortOrder,
            'groupByColumns' => $this->groupByColumns,
            'useModal' => $this->useModal,
            'buttonsDropdown' => $this->buttonsDropdown,
            'sendEmailOnInsert' => $this->sendEmailOnInsert,
            'emailConfig' => $this->emailConfig,
            'fieldsArrange' => $this->fieldsArrange,
            'customFieldTypes' => $this->customFieldTypes,
            'selectOptions' => $this->selectOptions,
            'radioFields' => $this->radioFields,
            'checkboxGroups' => $this->checkboxGroups,
            'inlineEditEnabled' => $this->inlineEditEnabled,
            'inlineEditConfig' => $this->inlineEditConfig,
            'actionConditions' => $this->actionConditions,
            'bulkDeleteCondition' => $this->bulkDeleteCondition,
            'subselects' => $this->subselects,
            'calculatedFields' => $this->calculatedFields,
            'ajaxConfig' => $this->ajaxConfig,
            'chartLabels' => $this->chartLabels,
            'chartDatasets' => $this->chartDatasets,
            'chartType' => $this->chartType,
            'chartOptions' => $this->chartOptions,
            'useChart' => $this->useChart,
            'customButtons' => $this->customButtons,
            'hiddenColumns' => $this->hiddenColumns,
            'onePagePosition' => $this->onePagePosition,
            'customAddButton' => $this->customAddButton,
            'advancedFilters'     => $this->advancedFilters     ?? [],
            'advancedFilterTitle' => $this->advancedFilterTitle ?? 'Advanced Filters',
            'advancedFilterOpen'  => $this->advancedFilterOpen  ?? false,
            'crudTemplate' => $this->crudTemplate,
            'rowTemplate'      => $this->rowTemplate,
            'rowWrapperClass'  => $this->rowWrapperClass,
            'rowWrapperTag'    => $this->rowWrapperTag,
            'rowWrapperAttrs'  => $this->rowWrapperAttrs,
            'actionsPosition' => $this->config['actionsPosition'] ?? 'right',
            'ckeditorFields'      => $this->ckeditorFields,
            'summernoteFields' => $this->summernoteFields,
            'select2Fields'       => $this->select2Fields,
            'chosenFields'        => $this->chosenFields,
            'imageFieldsConfig'   => $this->imageFieldsConfig,
            'nestedGrids' => array_map(function ($ng, $index) {
                $hasNestedGrid = isset($ng['nestedGrid']) && $ng['nestedGrid'] instanceof self;
                return [
                    'id'         => $hasNestedGrid ? $ng['nestedGrid']->getId() : ($ng['id'] ?? 'nested_' . $index),
                    'label'      => $ng['label'] ?? '',
                    'parentKey'  => $ng['parentKey'] ?? '',
                    'childTable' => $ng['childTable'] ?? '',
                    'childKey'   => $ng['childKey'] ?? '',
                    'config'     => $hasNestedGrid ? $ng['nestedGrid']->buildConfig() : ($ng['config'] ?? []),
                    'loadDirect' => $ng['loadDirect'] ?? true,
                    'ajaxConfig' => $hasNestedGrid ? ($ng['nestedGrid']->getAjaxConfig() ?? []) : ($ng['ajaxConfig'] ?? []),
                    'actions'    => $hasNestedGrid ? $ng['nestedGrid']->getActions() : ($ng['actions'] ?? []),
                    'error'      => $ng['error'] ?? null,
                ];
            }, array_values($this->nestedGrids), array_keys($this->nestedGrids)),
            'created_at' => time(),
            'last_used' => time(),
        ];
    }

    public function applyConfig(array $config): self
    {
        $blacklist = [
            'db', 'pdo', 'nestedGrids', 'nestedTables', 'callbacks', 
            'created_at', 'last_used', 'runtimeGridId', 'id', 'table'
        ];
        foreach ($config as $key => $value) {
            if (in_array($key, $blacklist, true)) {
                continue;
            }
            if ($key === 'actionsPosition') {
                $this->actionsPosition($value);
                continue;
            }
            if (!property_exists($this, $key)) {
                continue;
            }
            if (is_array($value)) {
                if (in_array($key, ['actions', 'whereConditions', 'where', 'columns', 'actionConditions'])) {
                    $this->$key = array_merge($this->$key ?? [], $value);
                    continue;
                }
            }
            $this->$key = $value;
        }
        return $this;
    }

    protected function getBaseUrl(): string
    {
        $dir  = str_replace('\\', '/', realpath(__DIR__));
        $root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
        if ($root && strpos($dir, $root) === 0) {
            return rtrim(str_replace($root, '', $dir), '/') . '/';
        }
        return '/';
    }

    protected function getFieldTypesMap(): array
    {
        $cols = $this->getColumns();
        $map = [];
        foreach ($cols as $c) {
            $name = is_array($c) ? $c['name'] : $c;
            if ($name === 'id') continue;
            $map[$name] = $this->detectFieldType($name);
        }
        return $map;
    }

    public function setAddButton(array $button): self
    {
        $this->customAddButton = $button;
        return $this;
    }

    protected function isAddAllowed(): bool
    {
        return true;
    }

    public function addBulkButton(
        string $label,
        string $url,
        string $icon = 'fa fa-bolt',
        string $class = 'btn btn-info btn-sm',
        array $attributes = [],
        array $confirm = []
    ): self {
        $this->bulkCustomAction = [
            'label'      => $label,
            'url'        => $url,
            'icon'       => $icon,
            'class'      => $class,
            'attributes' => $attributes,
            'confirm'    => $confirm
        ];
        return $this;
    }

    protected function renderCrud(): string
    {
        if (!isset($_SESSION['artigrid'])) {
            $_SESSION['artigrid'] = [];
        }
        $originalGridId = $this->id;
        if (empty($this->runtimeGridId)) {
            $this->runtimeGridId = $this->id . '_' . substr(md5(uniqid('', true)), 0, 8);
        }
        $gridId = $this->runtimeGridId;
        $_SESSION['artigrid'][$gridId]['config'] = $this->buildConfig();
        $this->syncNestedSessions();
        $_SESSION['artigrid'][$gridId]['original_id'] = $originalGridId;
        $resolvedCols = $this->getColumns();
        $resolvedCols = array_map(function ($c) {
            return is_array($c) ? $c['name'] : $c;
        }, $resolvedCols);
        $cols = !empty($this->columns) ? $this->columns : $resolvedCols;
        $searchCols = $cols;
        $dateColumns = $this->getDateColumns();
        if (!empty($_SESSION['artigrid'][$gridId]['config']['where'])) {
            $this->whereConditions = $_SESSION['artigrid'][$gridId]['config']['where'];
        }
        $token = $this->csrfToken();
        ob_start();
        if ($this->actions['search']): ?>
            <input type="text" class="form-control form-control-sm artigrid-search"
                placeholder="<?= $this->lang['search'] ?>" style="max-width:150px;">
        <?php endif;
        $searchInputHtml = ob_get_clean();
        ob_start();
        if ($this->actions['search']): ?>
            <select class="form-select form-select-sm artigrid-search-col" style="width:auto;">
                <option value=""><?= $this->lang['All'] ?></option>
                <?php foreach ($searchCols as $c):
                    if (!is_string($c)) continue;
                    $label = $this->colRename[$c] ?? $c;
                ?>
                    <option value="<?= $c ?>"><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif;
        $searchColumnHtml = ob_get_clean();
        ob_start();
        if ($this->actions['add'] && $this->isAddAllowed()) {
            if ($this->customAddButton) {
                if (is_string($this->customAddButton)) {
                    echo $this->customAddButton;
                }
                elseif (is_callable($this->customAddButton)) {
                    echo call_user_func($this->customAddButton, $this);
                }
                elseif (is_array($this->customAddButton)) {
                    $label = $this->customAddButton['label'] ?? $this->lang['add'];
                    $icon  = $this->customAddButton['icon'] ?? 'fa fa-plus';
                    $class = $this->customAddButton['class'] ?? 'btn btn-primary artigrid-add-btn';
                    $attributes = $this->customAddButton['attributes'] ?? [];
                    $attrString = '';
                    foreach ($attributes as $key => $value) {
                        $attrString .= sprintf(' %s="%s"', $key, htmlspecialchars($value, ENT_QUOTES));
                    }
                    ?>
                    <button class="<?= $class ?>" data-action="add" <?= $attrString ?>>
                        <i class="<?= $icon ?>"></i> <?= $label ?>
                    </button>
                    <?php
                }
            } else {
                ?>
                <button class="btn btn-primary artigrid-add-btn" style="white-space:nowrap;" data-action="add">
                    <i class="fa fa-circle-plus"></i> <?= $this->lang['add'] ?>
                </button>
                <?php
            }
        }
        $addBtnHtml = ob_get_clean();
        ob_start();
        if ($this->actions['refresh']): ?>
            <a href="#" class="btn btn-light"
            onclick="var b=this.closest('.artigrid-container');var i=ArtiGrid.instances.find(x=>x.box===b);if(i)i.loadData(i.page);return false;">
                <i class="fa fa-refresh"></i>
            </a>
        <?php endif;
        $refreshHtml = ob_get_clean();
        ob_start();
        if (!empty($this->exportTypes)): ?>
            <div class="dropdown d-inline-block" style="flex-shrink:0;">
                <button class="btn btn-light btn-sm dropdown-toggle" type="button"
                        data-bs-toggle="dropdown">
                    <i class="fa fa-download"></i> Export
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <?php if (in_array('excel', $this->exportTypes)): ?>
                        <button class="dropdown-item artigrid-export" data-type="excel">Excel</button>
                    <?php endif; ?>
                    <?php if (in_array('csv', $this->exportTypes)): ?>
                        <button class="dropdown-item artigrid-export" data-type="csv">CSV</button>
                    <?php endif; ?>
                    <?php if (in_array('pdf', $this->exportTypes)): ?>
                        <button class="dropdown-item artigrid-export" data-type="pdf">PDF</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif;
        $exportHtml = ob_get_clean();
        ob_start();
        if (!empty($this->actions['edit_multiple'])): ?>
            <button class="btn btn-warning btn-sm artigrid-edit-multiple" style="display: none;">
                <i class="fa fa-pen"></i> <?= $this->lang['edit_multiple'] ?>
            </button>
        <?php endif;
        ob_start();
        if (!empty($this->bulkCustomAction)):
            $attrs = '';
            foreach (($this->bulkCustomAction['attributes'] ?? []) as $k => $v) {
                $attrs .= sprintf(
                    ' %s="%s"',
                    $k,
                    htmlspecialchars($v, ENT_QUOTES)
                );
            }
        ?>
        <button
            class="<?= $this->bulkCustomAction['class'] ?> artigrid-bulk-custom btn-sm"
            data-url="<?= htmlspecialchars($this->bulkCustomAction['url'], ENT_QUOTES) ?>"
            data-confirm='<?= htmlspecialchars(json_encode($this->bulkCustomAction['confirm'] ?? []), ENT_QUOTES) ?>'
            style="display:none;"
            <?= $attrs ?>>
            <i class="<?= $this->bulkCustomAction['icon'] ?>"></i>
            <?= $this->bulkCustomAction['label'] ?>
        </button>
        <?php
        endif;
        $bulkCustomHtml = ob_get_clean();
        $bulkEditHtml = ob_get_clean();
        ob_start();
        if ($this->actions['delete_multiple']): ?>
            <button class="btn btn-danger btn-sm artigrid-delete-multiple" style="display: none;">
                <i class="fa fa-trash"></i> <?= $this->lang['delete_multiple'] ?>
            </button>
        <?php endif;
        $bulkDeleteHtml = ob_get_clean();

        ob_start();
        if ($this->rowTemplate) {
            $tag = strtolower($this->rowWrapperTag ?: 'div');
            $allowedTags = ['div', 'table', 'ul', 'ol', 'tbody'];
            if (!in_array($tag, $allowedTags, true)) {
                $tag = 'div';
            }
            $attrStr = '';
            foreach ($this->rowWrapperAttrs as $k => $v) {
                $attrStr .= ' ' . htmlspecialchars($k, ENT_QUOTES) . '="' . htmlspecialchars($v, ENT_QUOTES) . '"';
            }
            $wrapperClass = htmlspecialchars($this->rowWrapperClass ?? '', ENT_QUOTES);

            if ($tag === 'table') {
                echo "<table class=\"{$wrapperClass}\"{$attrStr}><tbody class=\"artigrid-cards-wrapper\"></tbody></table>";
            } else {
                echo "<{$tag} class=\"artigrid-cards-wrapper {$wrapperClass}\"{$attrStr}></{$tag}>";
            }
        } else {
            ?>
            <div class="card-body p-0 table-responsive" style="overflow-x:auto;">
                <table class="table table-hover table-sm align-middle mb-0 artigrid-table text-nowrap">
                    <thead class="table-light">
                    <tr>
                        <?php if ($this->actions['delete']): ?>
                            <th><input type="checkbox" class="artigrid-select-all"></th>
                        <?php endif; ?>
                        <?php foreach ($cols as $c): ?>
                            <th data-column="<?= $c ?>">
                                <?= $this->colRename[$c] ?? $c ?>
                                <i class="fa fa-sort-up" style="display:none;"></i>
                                <i class="fa fa-sort-down" style="display:none;"></i>
                            </th>
                        <?php endforeach; ?>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <?php
        }
        $tableHtml = ob_get_clean();
        $paginationHtml = '<ul class="pagination pagination-sm mb-0 artigrid-pagination"></ul>';
        ob_start();
        if ($this->actions['dropdownpage']): ?>
            <select class="form-select form-select-sm artigrid-perpage" style="width:auto;">
                <?php foreach ($this->perPageOptions as $o): ?>
                    <option value="<?= $o ?>" <?= $o == $this->perPage ? 'selected' : '' ?>>
                        <?= $o === 'all' ? 'All' : $o ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif;
        $perPageHtml = ob_get_clean();
        if (!empty($this->crudTemplate)) {
            $template = $this->crudTemplate;
            $replacements = [
                '{search}'        => $searchInputHtml . $searchColumnHtml,
                '{search_input}'  => $searchInputHtml,
                '{search_column}' => $searchColumnHtml,
                '{add_button}'    => $addBtnHtml,
                '{refresh}'       => $refreshHtml,
                '{export}'        => $exportHtml,
                '{bulk_delete}'   => $bulkDeleteHtml,
                '{bulk_edit}'     => $bulkEditHtml,
                '{table}'         => $tableHtml,
                '{pagination}'    => $paginationHtml,
                '{perpage}'       => $perPageHtml,
            ];
            foreach ($replacements as $key => $value) {
                $template = str_replace($key, $value, $template);
            }
            if (strpos($this->crudTemplate, '{table}') === false) {
                return $this->renderError("The template must include {table}");
            }
            $template = preg_replace('/\{[a-z_]+\}/i', '', $template);
        } else {
            ob_start();
            ?>
            <div class="card-header bg-white border-0">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                    <div class="d-flex flex-column flex-md-row gap-2 align-items-stretch align-items-md-center w-100">
                        <?= $bulkEditHtml ?>    
                        <?= $bulkDeleteHtml ?>
                        <?= $bulkCustomHtml ?>
                        <?= $searchInputHtml ?>
                        <?= $searchColumnHtml ?>
                        <?= $exportHtml ?>
                    </div>
                    <div class="d-flex justify-content-end align-items-center gap-2 flex-shrink-0">
                        <?= $addBtnHtml ?>
                        <?= $refreshHtml ?>
                    </div>
                </div>
            </div>
            <?= $tableHtml ?>
            <div class="card-footer bg-white border-0 d-flex flex-column flex-md-row justify-content-between gap-2">
                <?= $perPageHtml ?>
                <?= $paginationHtml ?>
            </div>
            <?php
            $template = ob_get_clean();
        }
        ob_start();
        ?>
        <div id="<?= $gridId ?>"
            class="artigrid-container card shadow-sm mb-4"
            data-csrf="<?= $token ?>"
            data-render-mode="<?= !empty($this->timelineMode) ? 'timeline' : 'table' ?>"
            data-timeline='<?= htmlspecialchars(json_encode($this->timelineConfig), ENT_QUOTES) ?>'
            data-column-colors='<?= json_encode($this->columnColors) ?>'
            data-summary-row='<?= htmlspecialchars(json_encode($this->summaryRow), ENT_QUOTES) ?>'
            data-summary-config='<?= htmlspecialchars(json_encode($this->summaryConfig), ENT_QUOTES) ?>'
            data-row-colors='<?= json_encode($this->rowColors ?? []) ?>'
            data-date-columns='<?= json_encode($dateColumns) ?>'
            data-modal="<?= $this->useModal ? '1' : '0' ?>"
            data-lang='<?= json_encode($this->lang) ?>'
            data-table="<?= $this->table ?>"
            data-perpage="<?= $this->perPage ?>"
            data-buttonsDropdown="<?= $this->buttonsDropdown ? '1' : '0' ?>"
            data-perpage-options='<?= json_encode($this->perPageOptions) ?>'
            data-actions='<?= json_encode($this->actions) ?>'
            data-bulk-delete-condition='<?= json_encode($this->bulkDeleteCondition) ?>'
            data-bulk-custom='<?= json_encode($this->bulkCustomAction) ?>'
            data-columns='<?= json_encode($cols) ?>'
            data-col-rename='<?= json_encode($this->colRename) ?>'
            data-mode="<?= $this->useQuery ? 'query' : 'table' ?>"
            data-query="<?= htmlspecialchars($this->query, ENT_QUOTES) ?>"
            data-custom-buttons='<?= json_encode($this->customButtons) ?>'
            data-primary-key="<?= $this->primaryKey ?>"
            data-joins='<?= json_encode($this->joins) ?>'
            data-select='<?= htmlspecialchars(json_encode($this->selectOptions ?? []), ENT_QUOTES) ?>'
            data-sort-column="<?= $this->sortColumn ?>"
            data-sort-order="<?= $this->sortOrder ?? 'asc' ?>"
            data-groupby='<?= json_encode($this->groupByColumns ?? []) ?>'
            data-where='<?= htmlspecialchars(json_encode($this->whereConditions ?: []), ENT_QUOTES) ?>'
            data-add-modal="#<?= $gridId ?>-Modal"
            data-grid-id="<?= $gridId ?>"
            data-callbacks='<?= htmlspecialchars(json_encode($this->callbacks ?? []), ENT_QUOTES) ?>'
            data-json='<?= htmlspecialchars(json_encode($this->jsonRows ?? []), ENT_QUOTES) ?>'
            data-config='<?= htmlspecialchars(json_encode($this->buildConfig()), ENT_QUOTES) ?>'
            data-row-template='<?= htmlspecialchars($this->rowTemplate ?? '', ENT_QUOTES) ?>'
            data-row-wrapper-tag="<?= htmlspecialchars($this->rowWrapperTag ?? 'div', ENT_QUOTES) ?>"
            data-inline-edit='<?= json_encode([
                'enabled' => $this->inlineEditEnabled ?? false,
                'config' => $this->inlineEditConfig ?? []
            ]) ?>'
            data-action-conditions='<?= json_encode($this->actionConditions) ?>'
            data-inline-config='<?= json_encode($this->inlineEditConfig) ?>'
            data-field-types='<?= json_encode($this->getFieldTypesMap()) ?>'
            data-baseurl='<?= $this->getBaseUrl() ?>'>
            <div class="artigrid-crud-view">
                <div class="artigrid-spinner-overlay" style="display:none;">
                    <div class="artigrid-spinner"></div>
                </div>
                <?= $template ?>
            </div>
            <div class="artigrid-form-view d-none"></div>
        </div>
        <?php
        if (($this->actions['add'] || $this->actions['edit'] || $this->actions['view']) && $this->useModal) {
            echo $this->renderModal($gridId);
        }
        $this->loadAssetsOnce();
        return ob_get_clean();
    }

    protected function buildCrudControls(): array
    {
        $resolvedCols = $this->getColumns();
        $resolvedCols = array_map(function ($c) {
            return is_array($c) ? $c['name'] : $c;
        }, $resolvedCols);
        $searchCols = !empty($this->columns) ? $this->columns : $resolvedCols;

        ob_start();
        if ($this->actions['search']): ?>
            <input type="text" class="form-control form-control-sm artigrid-search"
                placeholder="<?= $this->lang['search'] ?>" style="max-width:150px;">
        <?php endif;
        $searchInputHtml = ob_get_clean();

        ob_start();
        if ($this->actions['search']): ?>
            <select class="form-select form-select-sm artigrid-search-col" style="width:auto;">
                <option value=""><?= $this->lang['All'] ?></option>
                <?php foreach ($searchCols as $c):
                    if (!is_string($c)) continue;
                    $label = $this->colRename[$c] ?? $c;
                ?>
                    <option value="<?= $c ?>"><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif;
        $searchColumnHtml = ob_get_clean();

        ob_start();
        if ($this->actions['add'] && $this->isAddAllowed()) {
            if ($this->customAddButton) {
                if (is_string($this->customAddButton)) {
                    echo $this->customAddButton;
                } elseif (is_callable($this->customAddButton)) {
                    echo call_user_func($this->customAddButton, $this);
                } elseif (is_array($this->customAddButton)) {
                    $label = $this->customAddButton['label'] ?? $this->lang['add'];
                    $icon  = $this->customAddButton['icon'] ?? 'fa fa-plus';
                    $class = $this->customAddButton['class'] ?? 'btn btn-primary artigrid-add-btn';
                    $attributes = $this->customAddButton['attributes'] ?? [];
                    $attrString = '';
                    foreach ($attributes as $key => $value) {
                        $attrString .= sprintf(' %s="%s"', $key, htmlspecialchars($value, ENT_QUOTES));
                    }
                    ?>
                    <button class="<?= $class ?>" data-action="add" <?= $attrString ?>>
                        <i class="<?= $icon ?>"></i> <?= $label ?>
                    </button>
                    <?php
                }
            } else {
                ?>
                <button class="btn btn-primary artigrid-add-btn" style="white-space:nowrap;" data-action="add">
                    <i class="fa fa-circle-plus"></i> <?= $this->lang['add'] ?>
                </button>
                <?php
            }
        }
        $addBtnHtml = ob_get_clean();

        ob_start();
        if ($this->actions['refresh']): ?>
            <a href="#" class="btn btn-light"
            onclick="var b=this.closest('.artigrid-container');var i=ArtiGrid.instances.find(x=>x.box===b);if(i)i.loadData(i.page);return false;">
                <i class="fa fa-refresh"></i>
            </a>
        <?php endif;
        $refreshHtml = ob_get_clean();

        ob_start();
        if (!empty($this->exportTypes)): ?>
            <div class="dropdown d-inline-block" style="flex-shrink:0;">
                <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fa fa-download"></i> Export
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <?php if (in_array('excel', $this->exportTypes)): ?>
                        <button class="dropdown-item artigrid-export" data-type="excel">Excel</button>
                    <?php endif; ?>
                    <?php if (in_array('csv', $this->exportTypes)): ?>
                        <button class="dropdown-item artigrid-export" data-type="csv">CSV</button>
                    <?php endif; ?>
                    <?php if (in_array('pdf', $this->exportTypes)): ?>
                        <button class="dropdown-item artigrid-export" data-type="pdf">PDF</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif;
        $exportHtml = ob_get_clean();

        $gridRef = $this->runtimeGridId ?: $this->id;
        ob_start();
        if (!empty($this->exportTypes)): ?>
            <div class="dropdown d-inline-block" style="flex-shrink:0;">
                <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fa fa-download"></i> Export
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <?php foreach (['excel' => 'Excel', 'csv' => 'CSV', 'pdf' => 'PDF'] as $t => $label): ?>
                        <?php if (in_array($t, $this->exportTypes)): ?>
                            <button type="button" class="dropdown-item"
                                onclick="document.querySelector('#<?= htmlspecialchars($gridRef) ?> .artigrid-export[data-type=&quot;<?= $t ?>&quot;]').click();">
                                <?= $label ?>
                            </button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif;
        $exportProxyHtml = ob_get_clean();

        ob_start();
        if (!empty($this->actions['edit_multiple'])): ?>
            <button class="btn btn-warning btn-sm artigrid-edit-multiple" style="display: none;">
                <i class="fa fa-pen"></i> <?= $this->lang['edit_multiple'] ?>
            </button>
        <?php endif;
        $bulkEditHtml = ob_get_clean();

        ob_start();
        if ($this->actions['delete_multiple']): ?>
            <button class="btn btn-danger btn-sm artigrid-delete-multiple" style="display: none;">
                <i class="fa fa-trash"></i> <?= $this->lang['delete_multiple'] ?>
            </button>
        <?php endif;
        $bulkDeleteHtml = ob_get_clean();

        ob_start();
        if ($this->actions['dropdownpage']): ?>
            <select class="form-select form-select-sm artigrid-perpage" style="width:auto;">
                <?php foreach ($this->perPageOptions as $o): ?>
                    <option value="<?= $o ?>" <?= $o == $this->perPage ? 'selected' : '' ?>>
                        <?= $o === 'all' ? 'Todos' : $o ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif;
        $perPageHtml = ob_get_clean();

        return [
            '{search}'        => $searchInputHtml . $searchColumnHtml,
            '{search_input}'  => $searchInputHtml,
            '{search_column}' => $searchColumnHtml,
            '{add_button}'    => $addBtnHtml,
            '{refresh}'       => $refreshHtml,
            '{export}'        => $exportHtml,
            '{export_proxy}'  => $exportProxyHtml,
            '{bulk_delete}'   => $bulkDeleteHtml,
            '{bulk_edit}'     => $bulkEditHtml,
            '{pagination}'    => '<ul class="pagination pagination-sm mb-0 artigrid-pagination"></ul>',
            '{perpage}'       => $perPageHtml,
        ];
    }

    protected function getFieldCss(string $field): string
    {
        if (empty($this->fieldCss[$field])) {
            return '';
        }
        return implode(' ', $this->fieldCss[$field]);
    }

    protected function renderFieldInput(string $name, bool $isRequired, string $class, string $value, string $attrHtml): string
    {
        $type = $this->fieldTypes[$name] ?? 'text';
        if ($type === 'textarea') {
            return '<textarea name="'.$name.'" class="form-control '.$class.' '.($isRequired?'required-field':'').'" '.$attrHtml.'>'
                .htmlspecialchars($value).
            '</textarea>';
        }
        if ($type === 'file') {
            return '<input type="file" name="'.$name.'" class="form-control '.$class.' '.($isRequired?'required-field':'').'" '.$attrHtml.'>';
        }
        return '<input type="'.$type.'" name="'.$name.'" value="'.htmlspecialchars($value).'" class="form-control '.$class.' '.($isRequired?'required-field':'').'" '.$attrHtml.'>';
    }

    protected function renderFieldLabel(string $label, bool $required): string
    {
        return '<label class="form-label">'
            .htmlspecialchars($label)
            .($required ? ' <span class="text-danger">*</span>' : '')
            .'</label>';
    }

    protected function getDateColumns(): array
    {
        $dateCols = [];
        $columns = $this->getTableColumns();
        foreach ($columns as $col) {
            $type = strtolower($col['type'] ?? '');
            if (
                strpos($type, 'date') !== false ||
                strpos($type, 'datetime') !== false ||
                strpos($type, 'timestamp') !== false
            ) {
                $dateCols[] = $col['name'];
            }
        }
        return $dateCols;
    }

    protected function detectFieldType(string $field): string
    {
        if (isset($this->customFieldTypes[$field])) {
            return $this->customFieldTypes[$field];
        }
        $columns = $this->getTableColumns();
        $fieldLower = strtolower($field);
        foreach ($columns as $col) {
            if ($col['name'] === $field) {
                $type = strtolower($col['type']);
                $type = preg_replace('/\(.*/', '', $type);
                $type = trim($type);
                if ($type === 'tinyint') return 'checkbox';
                if (in_array($type, ['int', 'decimal', 'float', 'double'])) {
                    return 'number';
                }
                if (in_array($type, ['datetime', 'timestamp'])) return 'datetime';
                if ($type === 'date') return 'date';
                if ($type === 'time') return 'time';
                if ($type === 'year') return 'year';
                if ($type === 'date') return 'date';
                if (in_array($type, ['text', 'longtext', 'mediumtext'])) {
                    return 'textarea';
                }
                break;
            }
        }
        if (strpos($fieldLower, 'password') !== false) return 'password';
        if (strpos($fieldLower, 'email') !== false) return 'email';
        if (
            strpos($fieldLower, 'phone') !== false ||
            strpos($fieldLower, 'telefono') !== false
        ) {
            return 'tel';
        }
        if (strpos($fieldLower, 'datetime') !== false || strpos($fieldLower, 'timestamp') !== false) return 'datetime';
        if (strpos($fieldLower, 'fecha_hora') !== false) return 'datetime';
        if (strpos($fieldLower, 'hora') !== false || strpos($fieldLower, 'time') !== false) return 'time';
        if (strpos($fieldLower, 'fecha') !== false || strpos($fieldLower, 'date') !== false) return 'date';
        if (
            strpos($fieldLower, 'image') !== false ||
            strpos($fieldLower, 'imagen') !== false
        ) {
            return 'image';
        }
        if (
            strpos($fieldLower, 'file') !== false ||
            strpos($fieldLower, 'archivo') !== false
        ) {
            return 'file';
        }
        if (
            strpos($fieldLower, 'estado') !== false ||
            strpos($fieldLower, 'status') !== false
        ) {
            return 'select';
        }
        if (
            strpos($fieldLower, 'activo') !== false ||
            strpos($fieldLower, 'enabled') !== false
        ) {
            return 'checkbox';
        }
        return 'text';
    }

    protected function detectTemporalType(string $field): ?string
    {
        if (isset($this->customFieldTypes[$field])) {
            $t = strtolower($this->customFieldTypes[$field]);
            if (in_array($t, ['date','time','datetime','timestamp','year'], true)) {
                return $t === 'timestamp' ? 'datetime' : $t;
            }
            return null;
        }
        foreach ($this->getTableColumns() as $col) {
            if (($col['name'] ?? '') === $field) {
                $type = trim(strtolower(preg_replace('/\(.*/', '', $col['type'] ?? '')));
                switch ($type) {
                    case 'date':                 return 'date';
                    case 'time':                 return 'time';
                    case 'datetime':
                    case 'timestamp':            return 'datetime';
                    case 'year':                 return 'year';
                }
                return null;
            }
        }
        $f = strtolower($field);
        if (strpos($f,'datetime') !== false || strpos($f,'timestamp') !== false) return 'datetime';
        if (strpos($f,'fecha_hora') !== false) return 'datetime';
        if (strpos($f,'hora') !== false || strpos($f,'time') !== false) return 'time';
        if (strpos($f,'fecha') !== false || strpos($f,'date') !== false) return 'date';
        return null;
    }

    protected function formatTemporalValue($value, string $kind): string
    {
        if ($value === null) return '';
        $value = trim((string)$value);
        if ($value === '') return '';
        $norm = str_replace('T', ' ', $value);
        switch ($kind) {
            case 'date':
                return substr($norm, 0, 10);
            case 'time':
                if (strpos($norm, ' ') !== false) $norm = substr($norm, strpos($norm, ' ') + 1);
                if (preg_match('/^\d{1,2}:\d{2}$/', $norm)) $norm .= ':00';
                return substr($norm, 0, 8);
            case 'datetime':
                if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $norm)) $norm .= ':00';
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $norm)) return $norm . ' 00:00:00';
                return strlen($norm) >= 16 ? substr($norm, 0, 19) : $norm;
            case 'year':
                return substr(preg_replace('/\D/', '', $norm), 0, 4);
        }
        return $value;
    }

    public function normalizeTemporalData(array $data): array
    {
        if (empty($data)) return $data;
        $types = [];
        foreach ($this->getTableColumns() as $col) {
            $name = $col['name'] ?? null;
            if ($name === null) continue;
            $types[$name] = trim(strtolower(preg_replace('/\(.*/', '', $col['type'] ?? '')));
        }
        foreach ($data as $k => $v) {
            $sqlType = $types[$k] ?? null;
            if (!in_array($sqlType, ['date','time','datetime','timestamp','year'], true)) continue;
            if ($v === null) continue;
            $v = trim((string)$v);
            if ($v === '') { $data[$k] = null; continue; }   // vacío -> NULL (evita el 1292)
            switch ($sqlType) {
                case 'date':
                    $data[$k] = substr(str_replace('T', ' ', $v), 0, 10);
                    break;
                case 'time':
                    if (strpos($v, 'T') !== false) $v = substr($v, strpos($v, 'T') + 1);
                    if (strpos($v, ' ') !== false) $v = substr($v, strpos($v, ' ') + 1);
                    if (preg_match('/^\d{1,2}:\d{2}$/', $v)) $v .= ':00';
                    $data[$k] = $v;
                    break;
                case 'datetime':
                case 'timestamp':
                    $v = str_replace('T', ' ', $v);
                    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $v)) $v .= ':00';
                    $data[$k] = $v;
                    break;
                case 'year':
                    $data[$k] = substr(preg_replace('/\D/', '', $v), 0, 4);
                    break;
            }
        }
        return $data;
    }

    public function auth()
    {
        return $_SESSION['artigrid_auth'] ?? null;
    }

    public function can(string $action)
    {
        $auth = $this->auth();
        if (!$auth || empty($auth['permissions'])) {
            return false;
        }
        return in_array($action, $auth['permissions']);
    }

    public function canAny(array $actions): bool
    {
        foreach ($actions as $action) {
            if ($this->can($action)) {
                return true;
            }
        }
        return false;
    }

    public function canAll(array $actions): bool
    {
        foreach ($actions as $action) {
            if (!$this->can($action)) {
                return false;
            }
        }
        return true;
    }

    public function isRole(string $role): bool
    {
        return ($this->auth()['role'] ?? null) === $role;
    }

    protected function renderFormFields(array $cols, array $required = [], array $values = []): string
    {
        ob_start();
        ?>
        <div class="arti-grid-box"
            data-baseurl="<?= $this->getBaseUrl() ?>"
            data-config='<?= htmlspecialchars(json_encode($this->buildConfig()), ENT_QUOTES) ?>'>
        <?php
        $groups = $this->arrangeFields($cols);
        foreach ($groups as $group) {
            if (!empty($group['label']) && ($group['showLabel'] ?? true)) {
                echo '<h6 class="mt-3 mb-2">' . htmlspecialchars($group['label']) . '</h6>';
            }
            if (!empty($group['row'])) {
                echo '<div class="row">';
            }
            foreach ($group['fields'] as $c) {
                if ($group['row']) {
                    echo '<div class="col-md">';
                }
                if (is_array($c)) {
                    $name  = $c['name'] ?? null;
                    $label = $c['label'] ?? $name;
                } else {
                    $name  = $c;
                    $label = $this->colRename[$c] ?? $c;
                }
                if (!$name || $name === 'id') {
                    if ($group['row']) echo '</div>';
                    continue;
                }
                $isRequired = in_array($name, $required, true);
                $class = trim($this->getFieldCss($name));
                $value = $values[$name] ?? '';
                if (isset($this->formFieldValues[$name])) {
                    $default = $this->formFieldValues[$name];
                    $value = is_callable($default) ? $default() : $default;
                }
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                $attrHtml = '';
                if (isset($this->fieldAttributes[$name])) {
                    foreach ($this->fieldAttributes[$name] as $k => $v) {
                        if ($k === 'value') {
                            $value = $v;
                            continue;
                        }
                        $attrHtml .= ' ' . htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
                    }
                }
                if (isset($this->comboBoxes[$name])) {
                    $cfg = $this->comboBoxes[$name];
                    ?>
                    <div class="mb-3">
                        <label class="form-label">
                            <?= htmlspecialchars($label) ?>
                            <?= $isRequired ? '<span class="text-danger">*</span>' : '' ?>
                        </label>
                        <?php if ($cfg['source'] === 'array'): ?>
                            <select name="<?= $name ?>"
                                class="form-select <?= $class ?> <?= $isRequired ? 'required-field' : '' ?>"
                                data-static="1"
                                <?= $this->allFieldsRequired ? 'required' : '' ?>
                                <?= $this->chosenAttr($name) ?>
                                <?= $this->select2Attr($name) ?>
                                <?= $attrHtml ?>>
                                <option value="">Select</option>
                                <?php foreach ($cfg['options'] as $val => $text): ?>
                                    <option value="<?= htmlspecialchars($val) ?>"
                                        <?= (string)$value === (string)$val ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($text) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                        <?php $isDependent = !empty($cfg['dependsOn']); ?>
                        <select name="<?= $name ?>"
                                class="form-select <?= $isDependent ? 'artigrid-dependent-select' : '' ?> <?= $class ?> <?= $isRequired ? 'required-field' : '' ?>"
                                <?php if ($isDependent): ?>
                                    data-depends-on="<?= htmlspecialchars($cfg['dependsOn']) ?>"
                                    data-depends-field="<?= htmlspecialchars($cfg['dependsField'] ?? '') ?>"
                                    data-selected="<?= htmlspecialchars($value) ?>"
                                    data-where='<?= htmlspecialchars(json_encode($cfg['where'] ?? []), ENT_QUOTES, 'UTF-8') ?>'
                                <?php endif; ?>
                                <?= $this->allFieldsRequired ? 'required' : '' ?>
                                 <?= $this->select2Attr($name) ?>
                                <?= $attrHtml ?>>
                            <option value="">Select</option>
                            <?php if (!$isDependent): ?>
                                <?php
                                $table    = $cfg['table'];
                                $valueCol = $cfg['value'];
                                $labelColumnSql = is_array($cfg['label'])
                                    ? "CONCAT_WS(' ', " . implode(', ', array_map(
                                        fn($col) => "`$col`",
                                        $cfg['label']
                                    )) . ")"
                                    : "`{$cfg['label']}`";
                                $whereSql = '';
                                $params   = [];
                                if (!empty($cfg['where']) && is_array($cfg['where'])) {
                                    $clauses = [];
                                    foreach ($cfg['where'] as $col => $condition) {
                                        $operator = '=';
                                        $val = $condition;
                                        if (is_array($condition) && count($condition) === 2) {
                                            [$operator, $val] = $condition;
                                            $operator = strtoupper($operator);

                                            $allowedOperators = [
                                                '=',
                                                '!=',
                                                '<>',
                                                '>',
                                                '<',
                                                '>=',
                                                '<=',
                                                'LIKE',
                                                'NOT LIKE',
                                                'IN',
                                                'NOT IN'
                                            ];
                                            if (!in_array($operator, $allowedOperators, true)) {
                                                throw new InvalidArgumentException("Operator not allowed in where: {$operator}");
                                            }
                                        }
                                        $safeCol = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
                                        if (is_array($val)) {
                                            if (!in_array($operator, ['IN', 'NOT IN'], true)) {
                                                throw new InvalidArgumentException(
                                                    "The operator {$operator} requires a scalar value."
                                                );
                                            }
                                            if (empty($val)) {
                                                throw new InvalidArgumentException(
                                                    "The operator {$operator} requires at least one value."
                                                );
                                            }
                                            $placeholders = [];
                                            foreach (array_values($val) as $i => $v) {
                                                $p = ":where_{$safeCol}_{$i}";
                                                $placeholders[] = $p;
                                                $params[$p] = $v;
                                            }
                                            $clauses[] = "`$safeCol` {$operator} (" . implode(', ', $placeholders) . ")";
                                        } else {
                                            $param = ':where_' . $safeCol;
                                            $clauses[] = "`$safeCol` {$operator} {$param}";
                                            $params[$param] = $val;
                                        }
                                    }
                                    $whereSql = 'WHERE ' . implode(' AND ', $clauses);
                                }
                                $sql = "
                                    SELECT `$valueCol` AS val, $labelColumnSql AS txt
                                    FROM `$table`
                                    $whereSql
                                    ORDER BY txt
                                ";
                                $stmt = $this->db->prepare($sql);
                                $stmt->execute($params);
                                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($rows as $row): ?>
                                    <option value="<?= htmlspecialchars($row['val']) ?>"
                                        <?= (string)$value === (string)$row['val'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($row['txt']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    <?php endif; ?>
                    </div>
                    <?php
                    if ($group['row']) echo '</div>';
                    continue;
                }
                if (isset($this->checkboxGroups[$name])) {
                    $cfg      = $this->checkboxGroups[$name];
                    $options  = $cfg['options'];
                    $sep      = $cfg['separator'] ?? ',';
                    $selected = is_array($value)
                        ? $value
                        : ($value === '' ? [] : explode($sep, (string)$value));
                    $selected = array_map('trim', $selected);
                    ?>
                    <div class="mb-3">
                        <label class="form-label">
                            <?= htmlspecialchars($label) ?>
                            <?= $isRequired ? '<span class="text-danger">*</span>' : '' ?>
                        </label>
                        <?php foreach ($options as $val => $text): ?>
                            <div class="form-check">
                                <input
                                    class="form-check-input artigrid-checkbox-group <?= $class ?> <?= $isRequired ? 'required-field' : '' ?>"
                                    type="checkbox"
                                    name="<?= $name ?>[]"
                                    value="<?= htmlspecialchars($val) ?>"
                                    data-min="<?= (int)($cfg['min'] ?? 1) ?>"
                                    <?= in_array((string)$val, $selected, true) ? 'checked' : '' ?>>
                                <label class="form-check-label"><?= htmlspecialchars($text) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php
                    if ($group['row']) echo '</div>';
                    continue;
                }
                if (isset($this->radioFields[$name])) {
                    $options = $this->radioFields[$name];
                    ?>
                    <div class="mb-3">
                        <label class="form-label">
                            <?= htmlspecialchars($label) ?>
                            <?= $isRequired ? '<span class="text-danger">*</span>' : '' ?>
                        </label>
                        <?php foreach ($options as $val => $text): ?>
                            <div class="form-check">
                                <input 
                                    class="form-check-input <?= $class ?> <?= $isRequired ? 'required-field' : '' ?>"
                                    type="radio"
                                    name="<?= $name ?>"
                                    value="<?= htmlspecialchars($val) ?>"
                                    <?= $value == $val ? 'checked' : '' ?>
                                    <?= $this->allFieldsRequired ? 'required' : '' ?>
                                >
                                <label class="form-check-label">
                                    <?= htmlspecialchars($text) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php
                    if ($group['row']) echo '</div>';
                    continue;
                }
                if (isset($this->colorFields[$name])) {
                    ?>
                    <div class="mb-3">
                        <label class="form-label">
                            <?= htmlspecialchars($label) ?>
                            <?= $isRequired ? '<span class="text-danger">*</span>' : '' ?>
                        </label>
                        <input type="text"
                            name="<?= $name ?>"
                            value="<?= htmlspecialchars($value) ?>"
                            class="form-control <?= $class ?> <?= $isRequired ? 'required-field' : '' ?>"
                            <?= $this->colorAttr($name) ?>
                            <?= $this->allFieldsRequired ? 'required' : '' ?>
                            <?= $attrHtml ?>>
                    </div>
                    <?php
                    if ($group['row']) echo '</div>';
                    continue;
                }
                $type = $this->fieldTypes[$name] ?? $this->detectFieldType($name);
                if ($type === 'hidden') {
                    echo '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars($value) . '">';
                    if ($group['row']) echo '</div>';
                    continue;
                }
                if ($type === 'checkbox') {
                    ?>
                    <div class="form-check mb-3">
                        <input 
                            type="checkbox"
                            name="<?= $name ?>"
                            value="1"
                            class="form-check-input <?= $class ?> <?= $isRequired ? 'required-field' : '' ?>"
                            <?= (string)$value === '1' ? 'checked' : '' ?>
                            <?= $this->allFieldsRequired ? 'required' : '' ?>
                            <?= $attrHtml ?>
                        >
                        <label class="form-check-label">
                            <?= htmlspecialchars($label) ?>
                            <?= $isRequired ? '<span class="text-danger">*</span>' : '' ?>
                        </label>
                    </div>
                    <?php
                    if ($group['row']) echo '</div>';
                    continue;
                }
                ?>
                <div class="mb-3">
                    <label class="form-label">
                        <?= htmlspecialchars($label) ?>
                        <?= $isRequired ? '<span class="text-danger">*</span>' : '' ?>
                    </label>
                    <?php if ($type === 'textarea'): ?>
                    <textarea name="<?= $name ?>"
                        class="form-control <?= $class ?> <?= $isRequired ? 'required-field' : '' ?>"
                        <?= $this->allFieldsRequired ? 'required' : '' ?>
                        <?= $this->ckeditorAttr($name) ?>
                        <?= $this->summernoteAttr($name) ?>
                        <?= $attrHtml ?>><?= htmlspecialchars($value) ?></textarea>
                    <?php elseif ($type === 'date'): ?>
                        <input type="text"
                            name="<?= $name ?>"
                            value="<?= htmlspecialchars($this->formatTemporalValue($value, 'date')) ?>"
                            class="form-control artigrid-date <?= $class ?> <?= $isRequired ? 'required-field' : '' ?>"
                            <?= $this->allFieldsRequired ? 'required' : '' ?> <?= $attrHtml ?>>
                    <?php elseif ($type === 'time'): ?>
                        <input type="text"
                            name="<?= $name ?>"
                            value="<?= htmlspecialchars($this->formatTemporalValue($value, 'time')) ?>"
                            class="form-control artigrid-time <?= $class ?> <?= $isRequired ? 'required-field' : '' ?>"
                            <?= $this->allFieldsRequired ? 'required' : '' ?> <?= $attrHtml ?>>
                    <?php elseif ($type === 'datetime'): ?>
                        <input type="text"
                            name="<?= $name ?>"
                            value="<?= htmlspecialchars($this->formatTemporalValue($value, 'datetime')) ?>"
                            class="form-control artigrid-datetime <?= $class ?> <?= $isRequired ? 'required-field' : '' ?>"
                            <?= $this->allFieldsRequired ? 'required' : '' ?> <?= $attrHtml ?>>
                    <?php elseif ($type === 'year'): ?>
                        <input type="number" min="1901" max="2155"
                            name="<?= $name ?>"
                            value="<?= htmlspecialchars($this->formatTemporalValue($value, 'year')) ?>"
                            class="form-control <?= $class ?> <?= $isRequired ? 'required-field' : '' ?>"
                            <?= $this->allFieldsRequired ? 'required' : '' ?> <?= $attrHtml ?>>

                    <?php elseif ($type === 'number'): ?>
                        <input type="number"
                            name="<?= $name ?>"
                            value="<?= htmlspecialchars($value) ?>"
                            class="form-control <?= $class ?> <?= $isRequired ? 'required-field' : '' ?>"
                            <?= $this->allFieldsRequired ? 'required' : '' ?>
                            <?= $attrHtml ?>>
                    <?php elseif ($type === 'image'):
                        $imgCfg = array_merge([
                            'multiple' => false, 'crop' => true, 'aspectRatio' => null,
                            'width' => null, 'height' => null, 'maxFiles' => 10
                        ], $this->imageFieldsConfig[$name] ?? []);
                        $inputName = $imgCfg['multiple'] ? $name . '[]' : $name;

                        $existingArr = [];
                        if (is_string($value) && $value !== '') {
                            $decoded = json_decode($value, true);
                            $existingArr = is_array($decoded) ? $decoded : [$value];
                        } elseif (is_array($value)) {
                            $existingArr = $value;
                        }
                        $existingArr  = array_values(array_filter($existingArr));
                        $existingJson = htmlspecialchars(json_encode($existingArr), ENT_QUOTES);
                    ?>
                    <input type="file"
                        name="<?= $inputName ?>"
                        accept="image/*"
                        <?= $imgCfg['multiple'] ? 'multiple' : '' ?>
                        data-image-config='<?= htmlspecialchars(json_encode($imgCfg), ENT_QUOTES) ?>'
                        data-field="<?= htmlspecialchars($name) ?>"
                        data-existing='<?= $existingJson ?>'
                        data-upload-url="<?= $this->getBaseUrl() ?>uploads/"
                        class="form-control artigrid-image-input <?= $class ?> <?= $isRequired ? 'required-field' : '' ?>"
                        <?= ($this->allFieldsRequired && empty($existingArr)) ? 'required' : '' ?>
                        <?= $attrHtml ?>>
                    <div class="artigrid-image-preview-list mt-2"></div>
                    <?php elseif ($type === 'file'):
                        $existingFile = is_string($value) ? trim($value) : '';
                        $existingJson = htmlspecialchars(json_encode($existingFile !== '' ? [$existingFile] : []), ENT_QUOTES);
                    ?>
                    <input type="file"
                        name="<?= $name ?>"
                        data-field="<?= htmlspecialchars($name) ?>"
                        data-existing='<?= $existingJson ?>'
                        data-upload-url="<?= $this->getBaseUrl() ?>uploads/"
                        class="form-control artigrid-file-input <?= $class ?> <?= $isRequired ? 'required-field' : '' ?>"
                        <?= ($this->allFieldsRequired && $existingFile === '') ? 'required' : '' ?>
                        <?= $attrHtml ?>>
                    <?php if ($existingFile !== ''): ?>
                        <div class="mt-1 small">
                            <a href="<?= $this->getBaseUrl() ?>uploads/<?= htmlspecialchars($existingFile) ?>" target="_blank">
                                <i class="fa fa-paperclip"></i> <?= htmlspecialchars($existingFile) ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <input type="hidden" name="<?= htmlspecialchars($name) ?>_keep" value='<?= $existingJson ?>'>
                    <?php else: ?>
                        <input type="<?= htmlspecialchars($type) ?>"
                            name="<?= $name ?>"
                            value="<?= htmlspecialchars($value) ?>"
                            class="form-control <?= $class ?> <?= $isRequired ? 'required-field' : '' ?>"
                            <?= $this->allFieldsRequired ? 'required' : '' ?>
                            <?= $attrHtml ?>>
                    <?php endif; ?>
                </div>
                <?php
                if ($group['row']) echo '</div>';
            }
            if ($group['row']) echo '</div>';
        }
        ?>
        </div>
        <div class="artigrid-spinner-overlay" style="display:none;">
            <div class="artigrid-spinner"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    protected function renderSingleField(string $name, array $required): string
    {
        $isRequired = in_array($name, $required) ? 'required' : '';
        return '
            <input 
                type="text"
                name="'.$name.'"
                class="form-control"
                '.$isRequired.'
            >
        ';
    }

    protected function renderSelectForm(string $validationQuery = '', string $submitLabel = 'Login', string $mode = 'table'): string
    {
        if ($mode !== 'table') return '';
        $cols = $this->resolveFormFields('select');
        $required = $this->requiredFields ?? [];
        $inputs = [];
        foreach ($cols as $col) {
            $name = is_array($col) ? $col['name'] : $col;
            $fieldHtml = $this->renderFormFields([$col], $required);
            $fieldHtml = preg_replace('/<label\b[^>]*>.*?<\/label>/is', '', $fieldHtml);
            $fieldHtml = preg_replace('/^<div[^>]*>|<\/div>$/i', '', trim($fieldHtml));
            $inputs[$name] = trim($fieldHtml);
        }
        $actions = '
            <div class="mb-3 text-center">
                <button type="submit" class="btn btn-primary btn-block">'.$submitLabel.'</button>
                <button type="reset" class="btn btn-danger btn-block">Cancel</button>
            </div>
        ';
        $template = $this->selectFormTemplate;
        if (!empty($template)) {
            foreach ($inputs as $name => $html) {
                $template = str_replace('{'.$name.'}', $html, $template);
            }
            $template = str_replace('{action}', $actions, $template);
            $nestedHtml = '';
            if (!$this->useQuery && !empty($this->nestedGrids)) {
                ob_start();
                foreach ($this->nestedGrids as $index => $nt) {
                    
                }
                $nestedHtml = ob_get_clean();
            }
            $template = str_replace('{nested_tables}', $nestedHtml, $template);
        } else {
            $template = $this->renderFormFields($cols, $required, $data) . $actions;
        }
        $token = $this->csrfToken();
        ob_start(); ?>
            <form class="artigrid-select-form"
                enctype="multipart/form-data"
                data-grid-id="<?= $this->id ?>"
                data-table="<?= htmlspecialchars($this->crudTable ?? $this->table) ?>"
                data-baseurl="<?= $this->getBaseUrl() ?>"
                data-validation-query="<?= htmlspecialchars($validationQuery) ?>"
                data-lang='<?= htmlspecialchars(json_encode($this->lang), ENT_QUOTES) ?>'>
                <?= $template ?>
                <input type="hidden" name="csrf_token" value="<?= $token ?>">
            </form>
            <div class="artigrid-select-result mt-3"></div>
        <?php
        $this->loadAssetsOnce();
        return ob_get_clean();
    }

    protected function csrfToken(): string
    {
        if (empty($_SESSION['artigrid_csrf'])) {
            $_SESSION['artigrid_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['artigrid_csrf'];
    }

    protected function renderInsertForm(): string 
    {
        if ($this->useQuery && !$this->crudTable) return '';
        if (!isset($_SESSION['artigrid'][$this->id])) {
            $_SESSION['artigrid'][$this->id] = [];
        }
        $_SESSION['artigrid'][$this->id] = array_merge(
            $_SESSION['artigrid'][$this->id],
            [
                'table' => $this->table,
                'primaryKey' => $this->primaryKey,
                'columns' => $this->getTableColumns(),
                'config' => $this->buildConfig()
            ]
        );
        $cols = $this->resolveFormFields('add');
        $required = $this->requiredFields ?? [];
        $inputs = [];
        foreach ($cols as $col) {
            $colName = is_array($col) ? $col['name'] : $col;
            $fieldHtml = $this->renderFormFields([$col], $required);
            $fieldHtml = preg_replace('/<label\b[^>]*>.*?<\/label>/is', '', $fieldHtml);
            $fieldHtml = preg_replace('/^<div[^>]*>|<\/div>$/i', '', trim($fieldHtml));
            $inputs[$colName] = trim($fieldHtml);
        }
        $actions = '
            <div class="mb-3 text-center">
                <button type="submit" class="btn btn-primary">'.$this->lang['save'].'</button>
                <button type="reset" class="btn btn-danger">'.$this->lang['cancel'].'</button>
            </div>
        ';
        $template = '';
        if (!empty($this->insertFormTemplate)) {
            if (is_file($this->insertFormTemplate)) {
                extract($inputs);
                ob_start();
                include $this->insertFormTemplate;
                $template = ob_get_clean();
            } else {
                $template = $this->insertFormTemplate;
            }
        }
        if (!empty($template)) {
            foreach ($inputs as $name => $html) {
                $template = str_replace('{'.$name.'}', $html, $template);
            }
            $template = str_replace('{action}', $actions, $template);
        } else {
            $template = $this->renderFormFields($cols, $required) . $actions;
        }
        $token = $this->csrfToken();
        ob_start(); ?>
        <form class="artigrid-add-form"
            data-table="<?= htmlspecialchars($this->crudTable ?? $this->table) ?>"
            data-grid-id="<?= $this->id ?>"
            data-baseurl="<?= $this->getBaseUrl() ?>"
            data-field-conditions='<?= json_encode($this->fieldConditions, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'
            data-config='<?= htmlspecialchars(json_encode($this->buildConfig()), ENT_QUOTES) ?>'
            data-lang='<?= htmlspecialchars(json_encode($this->lang), ENT_QUOTES) ?>'
            enctype="multipart/form-data">
            <?= $template ?>
            <div class="artigrid-spinner-overlay" style="display:none;">
                <div class="artigrid-spinner"></div>
            </div>
            <input type="hidden" name="csrf_token" value="<?= $token ?>">
        </form>
        <?php
        $this->loadAssetsOnce();
        return ob_get_clean();
    }

    private function renderError(string $message)
    {
        return "
        <div style='
            padding:15px;
            background:#fee2e2;
            border:1px solid #ef4444;
            color:#991b1b;
            border-radius:6px;
            margin:10px 0;
            font-family:Arial'>
            <strong>ArtiGrid Error:</strong> $message
        </div>";
    }

    protected function renderViewForm(string $id): string
    {
        $data = $this->viewRowData;
        if (empty($data)) {
            return $this->renderError(
                "Record with ID <b>{$id}</b> does not exist in table <b>{$this->table}</b>."
            );
        }
        $cols = $this->resolveViewFields('view');
        $values = [];
        foreach ($cols as $col) {
            $colName = is_array($col) ? $col['name'] : $col;
            $value = $data[$colName] ?? '';
            if (is_string($value)) {
                $value = htmlspecialchars($value, ENT_QUOTES);
            }
            $values[$colName] = $value;
        }
        $template = $this->viewFormTemplate;
        if (!empty($template)) {
            foreach ($cols as $col) {
                $colName = is_array($col) ? $col['name'] : $col;
                if (strpos($template, '{'.$colName.'|raw}') !== false) {
                    $rawVal = $data[$colName] ?? '';
                    $template = str_replace(
                        '{'.$colName.'|raw}',
                        html_entity_decode((string)$rawVal, ENT_QUOTES, 'UTF-8'),
                        $template
                    );
                }
            }
            foreach ($values as $name => $val) {
                $template = str_replace('{'.$name.'}', $val, $template);
            }
            $template = preg_replace_callback('/{label:(.*?)}/', function ($m) {
                $col = $m[1];
                return $this->colRename[$col] ?? ucfirst($col);
            }, $template);
            $template = preg_replace_callback('/{value:(.*?)}/', function ($m) use ($data) {
                $val = $data[$m[1]] ?? '';
                return is_string($val)
                    ? htmlspecialchars($val, ENT_QUOTES)
                    : $val;
            }, $template);
        } else {
            $rows = '';
            foreach ($cols as $col) {
                $colName = is_array($col) ? $col['name'] : $col;
                $label = $this->colRename[$colName] ?? ucfirst($colName);
                $value = $data[$colName] ?? '';
                if (is_string($value)) {
                    $value = htmlspecialchars($value, ENT_QUOTES);
                }
                $rows .= "
                    <tr>
                        <th style='width:30%; white-space:nowrap;'>{$label}</th>
                        <td>{$value}</td>
                    </tr>
                ";
            }
            $template = "
                <div class='table-responsive'>
                    <table class='table table-bordered table-striped table-sm mb-3'>
                        <tbody>{$rows}</tbody>
                    </table>
                </div>
            ";
        }
        ob_start();
    ?>
    <div class="artigrid-view-form"
        data-grid-id="<?= $this->id ?>"
        data-table="<?= htmlspecialchars($this->table) ?>"
        data-row-id="<?= $id ?>">
        <?= $template ?>
        <div class="artigrid-spinner-overlay" style="display:none;">
            <div class="artigrid-spinner"></div>
        </div>
    </div>
    <?php if (!$this->useQuery && !empty($this->nestedGrids)): ?>
        <?php foreach ($this->nestedGrids as $nt): ?>
            <?php
                $parentId = $data[$nt['parentKey']] ?? null;
                if (!$parentId) continue;
            ?>
            <div class="nested_table mt-3"
                data-child-table="<?= htmlspecialchars($nt['childTable']) ?>"
                data-child-key="<?= htmlspecialchars($nt['childKey']) ?>"
                data-parent-key="<?= htmlspecialchars($nt['parentKey']) ?>"
                data-grid-id="<?= htmlspecialchars($nt['id']) ?>"
                data-parent-id="<?= htmlspecialchars($parentId) ?>"
                data-preloaded="1">
                <div class="d-flex align-items-center mb-2 text-muted">
                    <i class="fa fa-list me-2"></i>
                    <strong><?= htmlspecialchars($nt['label']) ?></strong>
                </div>
                <div class="nested-grid-content">
                    <?= $this->renderNestedInline($nt, $parentId) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php
        $this->loadAssetsOnce();
        return ob_get_clean();
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    protected function renderEditForm(string $id): string
    {
        $data = $this->editRowData;
        if (empty($data)) {
            return $this->renderError(
                "Record with ID <b>{$id}</b> does not exist in table <b>{$this->table}</b>."
            );
        }
        $cols = $this->resolveFormFields('edit');
        $required = $this->requiredFields ?? [];
        $inputs = [];
        foreach ($cols as $col) {
            $colName = is_array($col) ? $col['name'] : $col;
            $fieldHtml = $this->renderFormFields([$col], $required, $data);
            $fieldHtml = preg_replace('/<label\b[^>]*>.*?<\/label>/is', '', $fieldHtml);
            $fieldHtml = preg_replace('/^<div[^>]*>|<\/div>$/i', '', trim($fieldHtml));
            $inputs[$colName] = trim($fieldHtml);
        }
        $actions = '
            <div class="mb-3 text-center">
                <button type="submit" class="btn btn-primary">'.$this->lang['save'].'</button>
                <button type="reset" class="btn btn-danger">'.$this->lang['cancel'].'</button>
            </div>
        ';
        $template = $this->editFormTemplate;
        if (!empty($template)) {
            foreach ($inputs as $name => $html) {
                $template = str_replace('{'.$name.'}', $html, $template);
            }
            $template = str_replace('{action}', $actions, $template);
        } else {
            $template = $this->renderFormFields($cols, $required, $data) . $actions;
        }
        $token = $this->csrfToken();
        ob_start();
    ?>
    <form class="artigrid-edit-form"
        data-grid-id="<?= $this->id ?>"
        data-baseurl="<?= $this->getBaseUrl() ?>"
        data-field-conditions='<?= json_encode($this->fieldConditions, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'
        data-primary-key="<?= htmlspecialchars($this->primaryKey) ?>"
        data-table="<?= htmlspecialchars($this->crudTable ?? $this->table) ?>"
        data-row-id="<?= $id ?>"
        data-mode="<?= $this->useQuery ? 'query' : 'table' ?>"
        data-lang='<?= htmlspecialchars(json_encode($this->lang), ENT_QUOTES) ?>'>
        <?= $template ?>
        <div class="artigrid-spinner-overlay" style="display:none;">
            <div class="artigrid-spinner"></div>
        </div>
        <input type="hidden" name="csrf_token" value="<?= $token ?>">
    </form>
    <?php if (!$this->useQuery && !empty($this->nestedGrids)): ?>
        <?php foreach ($this->nestedGrids as $index => $nt): ?>
            <?php
                $parentId = $data[$nt['parentKey']] ?? null;
                if (!$parentId) continue;
            ?>
            <div class="nested_table mt-4 p-3 border rounded"
                data-nested-index="<?= $index ?>"
                data-nested-container="<?= htmlspecialchars($nt['childTable']) ?>"
                data-child-table="<?= htmlspecialchars($nt['childTable']) ?>"
                data-child-key="<?= htmlspecialchars($nt['childKey']) ?>"
                data-parent-key="<?= htmlspecialchars($nt['parentKey']) ?>"
                data-grid-id="<?= htmlspecialchars($nt['id']) ?>"
                data-parent-id="<?= htmlspecialchars($parentId) ?>"
                data-preloaded="1">
                <div class="d-flex align-items-center mb-2">
                    <i class="fa fa-list text-primary me-2"></i>
                    <strong><?= htmlspecialchars($nt['label']) ?></strong>
                    <span class="badge bg-light text-dark ms-2"><?= $parentId ?></span>
                </div>
                <div class="nested-grid-content">
                    <?= $this->renderNestedInline($nt, $parentId) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php
        $this->loadAssetsOnce();
        return ob_get_clean();
    }

    protected function renderModal(?string $gridId = null): string
    {
        ob_start(); ?>
        <div class="modal fade"
            id="<?=$gridId ?>-Modal"
            data-bs-backdrop="static"
            data-bs-keyboard="false">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title random_title"><?= $this->table ?></h6>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body content_modal"></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    protected function nestedUses(string $prop): bool
    {
        foreach ($this->nestedGrids as $nt) {
            if (!empty($nt['nestedGrid']) && $nt['nestedGrid'] instanceof self) {
                if (!empty($nt['nestedGrid']->{$prop})) return true;
                if ($nt['nestedGrid']->nestedUses($prop)) return true; // recursivo, N niveles
            }
            if (!empty($nt['config'][$prop])) return true;
        }
        return false;
    }

    protected function loadAssetsOnce(): void
    {
        static $loaded = false;
        static $ckeditorLoaded = false;
        static $summernoteLoaded = false;
        static $select2Loaded  = false;
        static $chosenLoaded   = false;
        static $imageCropLoaded = false;

        if (!$loaded) {
            $loaded = true;
            if ($this->template === 'bootstrap4') { ?>
                <link rel="stylesheet" href="<?=$this->getBaseUrl()?>assets/css/bootstrap.min.css">
                <link rel="stylesheet" href="<?=$this->getBaseUrl()?>assets/css/sweetalert2.min.css">
                <script src="<?=$this->getBaseUrl()?>assets/js/jquery.slim.min.js"></script>
                <script src="<?=$this->getBaseUrl()?>assets/js/bootstrap.bundle.min.js"></script>
                <script src="<?=$this->getBaseUrl()?>assets/js/sweetalert2.min.js"></script>
            <?php } else { ?>
                <link rel="stylesheet" href="<?=$this->getBaseUrl()?>assets/css/bootstrap.css">
                <link rel="stylesheet" href="<?=$this->getBaseUrl()?>assets/css/sweetalert2.min.css">
                <script src="<?=$this->getBaseUrl()?>assets/js/bootstrap.bundle.js"></script>
                <script src="<?=$this->getBaseUrl()?>assets/js/sweetalert2.min.js"></script>
            <?php } ?>
            <link rel="stylesheet" href="<?=$this->getBaseUrl()?>assets/css/artigrid.css">
            <link rel="stylesheet" href="<?=$this->getBaseUrl()?>assets/css/spiner.css">
            <link rel="stylesheet" href="<?=$this->getBaseUrl()?>assets/css/all.min.css">
            <link rel="stylesheet" href="<?=$this->getBaseUrl()?>assets/css/flatpickr.min.css">
            <script src="<?=$this->getBaseUrl()?>assets/js/flatpickr.js"></script>
            <script src="<?=$this->getBaseUrl()?>assets/js/artigrid.js"></script>
            <script src="<?=$this->getBaseUrl()?>assets/js/jscolor.min.js"></script>
            <link rel="stylesheet" href="<?=$this->getBaseUrl()?>assets/css/artigrid-advanced-filter.css">
            <script src="<?=$this->getBaseUrl()?>assets/js/artigrid-advanced-filter.js"></script>
            <?php
        }

        if (!empty($this->ckeditorFields) && !$ckeditorLoaded) {
            $ckeditorLoaded = true;
            ?>
            <script src="<?=$this->getBaseUrl()?>assets/js/ckeditor.js"></script>
            <script src="<?=$this->getBaseUrl()?>assets/js/artigrid-ckeditor.js"></script>
            <?php
        }

        if (!empty($this->summernoteFields) && !$summernoteLoaded) {
            $summernoteLoaded = true;
            ?>
            <link rel="stylesheet" href="<?=$this->getBaseUrl()?>assets/css/summernote-bs5.min.css">
            <script src="<?=$this->getBaseUrl()?>assets/js/jquery.min.js"></script>
            <script src="<?=$this->getBaseUrl()?>assets/js/summernote-bs5.min.js"></script>
            <script src="<?=$this->getBaseUrl()?>assets/js/summernote-es-ES.min.js"></script>
            <script src="<?=$this->getBaseUrl()?>assets/js/artigrid-summernote.js"></script>
            <?php
        }

        if (!empty($this->select2Fields) && !$select2Loaded) {
            $select2Loaded = true;
            ?>
            <link rel="stylesheet" href="<?=$this->getBaseUrl()?>assets/css/tom-select.bootstrap5.min.css">
            <script src="<?=$this->getBaseUrl()?>assets/js/tom-select.complete.min.js"></script>
            <script src="<?=$this->getBaseUrl()?>assets/js/artigrid-select2.js"></script>
            <?php
        }

        if (!empty($this->chosenFields) && !$chosenLoaded) {
            $chosenLoaded = true;
            ?>
            <link rel="stylesheet" href="<?=$this->getBaseUrl()?>assets/css/artigrid-chosen.css">
            <script src="<?=$this->getBaseUrl()?>assets/js/artigrid-chosen.js"></script>
            <?php
        }

        if ((!empty($this->imageFieldsConfig) || $this->nestedUses('imageFieldsConfig')) && !$imageCropLoaded) {
            $imageCropLoaded = true;
            ?>
            <script src="<?=$this->getBaseUrl()?>assets/js/artigrid-imagecrop.js"></script>
            <?php
        }
    }

    protected function syncNestedSessions(): void
    {
        foreach ($this->nestedGrids as $nt) {
            if (!isset($nt['nestedGrid']) || !($nt['nestedGrid'] instanceof self)) {
                continue;
            }
            $nestedGrid = $nt['nestedGrid'];
            $nestedId   = $nestedGrid->getId();
            $_SESSION['artigrid'][$nestedId] = [
                'config'         => $nestedGrid->buildConfig(),
                'table'          => $nt['childTable'],
                'parent_table'   => $this->table,
                'parent_key'     => $nt['parentKey'],
                'child_key'      => $nt['childKey'],
                'label'          => $nt['label'],
                'nest_level'     => ($this->ajaxConfig['nest_level'] ?? 0) + 1,
                'parent_relation' => [
                    'parent_table' => $this->table,
                    'parent_key'   => $nt['parentKey'],
                    'child_key'    => $nt['childKey'],
                ],
            ];
            $nestedGrid->syncNestedSessions();
        }
    }
}