<?php

require_once 'ArtiGrid.php';
$pdo = DB::connect();

$tables = [];
try {
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
} catch (\Throwable $e) {
    $tables = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ArtiGrid Builder</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
<style>
    :root { --ag-border:#e2e5ea; --ag-muted:#6c757d; --ag-bg:#f8f9fb; }
    body { background:var(--ag-bg); font-size:14px; }
    .ag-card { background:#fff; border:1px solid var(--ag-border); border-radius:12px; padding:14px 18px; margin-bottom:16px; }
    .ag-sec-head { display:flex; align-items:center; justify-content:space-between; cursor:pointer; user-select:none; }
    .ag-sec-head h6 { margin:0; display:flex; align-items:center; gap:8px; font-weight:600; }
    .ag-badge { font-size:12px; background:#e6f1fb; color:#0c447c; padding:1px 9px; border-radius:8px; }
    .ag-sec-body { margin-top:14px; }
    .ag-label { font-size:13px; color:var(--ag-muted); display:block; margin-bottom:4px; }
    .ag-row { display:flex; gap:8px; align-items:flex-end; flex-wrap:wrap; }
    .ag-sub { border:1px solid var(--ag-border); border-radius:8px; padding:10px; margin-bottom:10px; }
    .ag-mono { font-family:ui-monospace,Menlo,Consolas,monospace; }
    pre.ag-code { margin:0; font-size:12.5px; line-height:1.6; background:#0f172a; color:#e2e8f0;
        border-radius:8px; padding:14px; overflow-x:auto; white-space:pre; }
    .ag-sticky { position:sticky; top:16px; }
    .ag-chip { font-size:12px; background:#e6f1fb; color:#0c447c; padding:4px 10px; border-radius:8px; }
    .ag-toggle-tabs button.active { border-color:#378add; color:#0c447c; }
    table.ag-prev th, table.ag-prev td { white-space:nowrap; padding:6px 8px; font-size:12.5px; }
</style>
</head>
<body>
<div class="container-fluid py-3">
    <div class="d-flex align-items-center gap-2 mb-3">
        <i class="fa fa-table-cells-large fa-lg text-primary"></i>
        <h4 class="mb-0">ArtiGrid Builder</h4>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="ag-card">
                <label class="ag-label">Tipo de salida</label>
                <div class="ag-toggle-tabs d-flex gap-2 flex-wrap">
                    <button class="btn btn-sm btn-light active" data-render="crud"><i class="fa fa-table"></i> CRUD</button>
                    <button class="btn btn-sm btn-light" data-render="calendar"><i class="fa fa-calendar-days"></i> Calendar</button>
                    <button class="btn btn-sm btn-light" data-render="insert"><i class="fa fa-pen-to-square"></i> Formulario</button>
                    <button class="btn btn-sm btn-light" data-render="select"><i class="fa fa-right-to-bracket"></i> Login</button>
                    <button class="btn btn-sm btn-light" data-render="chart"><i class="fa fa-chart-column"></i> Gráfico</button>
                </div>
                <p class="small text-muted mt-2 mb-0" id="renderHint"></p>
            </div>
            <div class="ag-card" data-sec data-only="calendar" style="display:none;">
                <div class="ag-sec-head" data-toggle>
                    <h6><i class="fa fa-calendar-days"></i> Configuración del calendario</h6>
                    <i class="fa fa-chevron-up" data-chev></i>
                </div>
                <div class="ag-sec-body">
                    <div class="row g-2">
                        <div class="col-6"><label class="ag-label">titleField</label><select class="form-select form-select-sm" id="calTitle"></select></div>
                        <div class="col-6"><label class="ag-label">startField</label><select class="form-select form-select-sm" id="calStart"></select></div>
                        <div class="col-6"><label class="ag-label">endField</label><select class="form-select form-select-sm" id="calEnd"></select></div>
                        <div class="col-6"><label class="ag-label">colorField</label><select class="form-select form-select-sm" id="calColor"></select></div>
                        <div class="col-6"><label class="ag-label">allDayField</label><select class="form-select form-select-sm" id="calAllDay"></select></div>
                        <div class="col-6"><label class="ag-label">Vista inicial</label>
                            <select class="form-select form-select-sm" id="calView">
                                <option value="dayGridMonth">dayGridMonth</option>
                                <option value="timeGridWeek">timeGridWeek</option>
                                <option value="timeGridDay">timeGridDay</option>
                                <option value="listWeek">listWeek</option>
                            </select></div>
                        <div class="col-6"><label class="ag-label">Locale</label><input class="form-control form-control-sm" id="calLocale" value="es"></div>
                    </div>
                    <label class="small d-flex gap-2 align-items-center mt-2">
                        <input type="checkbox" id="calPicker" checked> Usar colorPicker() en el campo de color</label>
                    <div id="calPickerOpts" class="row g-2 mt-1">
                        <div class="col-6"><label class="ag-label">Paleta (colores separados por espacio)</label>
                            <input class="form-control form-control-sm" id="calPalette" value="#3788d8 #28a745 #dc3545 #fd7e14"></div>
                        <div class="col-6"><label class="ag-label">Posición del picker</label>
                            <select class="form-select form-select-sm" id="calPickerPos">
                                <option value="fixed">fixed (recomendado en modal)</option>
                                <option value="">default</option>
                            </select></div>
                    </div>
                </div>
            </div>
            <div class="ag-card" data-sec data-only="chart" style="display:none;">
                <div class="ag-sec-head" data-toggle>
                    <h6><i class="fa fa-chart-column"></i> Configuración del gráfico <span class="ag-badge" id="dsCount"></span></h6>
                    <i class="fa fa-chevron-up" data-chev></i>
                </div>
                <div class="ag-sec-body">
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="ag-label">Tipo de gráfico</label>
                            <select class="form-select form-select-sm" id="chartType">
                                <option value="bar">bar</option><option value="line">line</option>
                                <option value="pie">pie</option><option value="doughnut">doughnut</option>
                                <option value="radar">radar</option><option value="polarArea">polarArea</option>
                            </select></div>
                        <div class="col-6"><label class="ag-label">Labels (separados por coma)</label>
                            <input class="form-control form-control-sm" id="chartLabels" value="Ene, Feb, Mar, Abr"></div>
                    </div>
                    <div id="dsList"></div>
                    <button class="btn btn-sm btn-light" id="addDs"><i class="fa fa-plus"></i> Añadir dataset</button>
                </div>
            </div>
            <div class="ag-card" data-sec data-only="select" style="display:none;">
                <div class="ag-sec-head" data-toggle>
                    <h6><i class="fa fa-right-to-bracket"></i> Configuración de login</h6>
                    <i class="fa fa-chevron-up" data-chev></i>
                </div>
                <div class="ag-sec-body">
                    <div class="row g-2">
                        <div class="col-6"><label class="ag-label">Campo usuario</label><select class="form-select form-select-sm" id="loginUserField"></select></div>
                        <div class="col-6"><label class="ag-label">Campo contraseña</label><select class="form-select form-select-sm" id="loginPassField"></select></div>
                    </div>
                    <label class="small d-flex gap-2 align-items-center mt-2">
                        <input type="checkbox" id="loginTemplate" checked> Incluir template HTML del formulario</label>
                    <label class="small d-flex gap-2 align-items-center mt-1">
                        <input type="checkbox" id="loginCallback" checked> Incluir callback de validación de ejemplo</label>
                    <p class="small text-muted mt-2 mb-0">El callback se genera como bloque aparte (functions.php + callbacks/&lt;tabla&gt;.php).</p>
                </div>
            </div>
            <div class="ag-card" data-sec>
                <div class="ag-sec-head" data-toggle>
                    <h6><i class="fa fa-database"></i> Fuente de columnas</h6>
                    <i class="fa fa-chevron-up" data-chev></i>
                </div>
                <div class="ag-sec-body">
                    <div class="ag-toggle-tabs d-flex gap-2 mb-3">
                        <button class="btn btn-sm btn-light active" id="modeDb">Base de datos</button>
                        <button class="btn btn-sm btn-light" id="modeManual">Manual</button>
                        <button class="btn btn-sm btn-light" id="modeDdl">Pegar DDL</button>
                    </div>

                    <div id="paneDb">
                        <label class="ag-label">Selecciona la tabla</label>
                        <select class="form-select" id="dbTable">
                            <option value="">— elige una tabla —</option>
                            <?php foreach ($tables as $t): ?>
                                <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="paneManual" style="display:none;">
                        <label class="ag-label">Columnas separadas por coma</label>
                        <input class="form-control" id="manualCols" value="id, title, description, start_date, color, status">
                    </div>

                    <div id="paneDdl" style="display:none;">
                        <label class="ag-label">Pega tu CREATE TABLE</label>
                        <textarea class="form-control ag-mono" id="ddl" rows="6" placeholder="CREATE TABLE events (&#10;  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,&#10;  title VARCHAR(255),&#10;  start_date DATETIME,&#10;  ...&#10;);"></textarea>
                    </div>

                    <button class="btn btn-sm btn-primary mt-2" id="loadCols">
                        <i class="fa fa-rotate"></i> Cargar columnas
                    </button>
                    <span class="text-muted ms-2 small" id="colsInfo"></span>
                </div>
            </div>
            <div class="ag-card" data-sec>
                <div class="ag-sec-head" data-toggle>
                    <h6><i class="fa fa-gear"></i> Configuración básica</h6>
                    <i class="fa fa-chevron-up" data-chev></i>
                </div>
                <div class="ag-sec-body">
                    <div class="row g-3">
                        <div class="col-6"><label class="ag-label">Tabla</label>
                            <input class="form-control" id="cfgTable" value="events"></div>
                        <div class="col-6"><label class="ag-label">Primary key</label>
                            <input class="form-control" id="cfgPk" value="id"></div>
                        <div class="col-6"><label class="ag-label">Template</label>
                            <select class="form-select" id="cfgTemplate">
                                <option value="bootstrap5">bootstrap5</option>
                                <option value="bootstrap4">bootstrap4</option>
                            </select></div>
                        <div class="col-6"><label class="ag-label">Registros por página</label>
                            <input type="number" class="form-control" id="cfgPerPage" value="10"></div>
                    </div>
                    <div class="d-flex gap-4 mt-3 flex-wrap">
                        <label class="small d-flex gap-2 align-items-center">
                            <input type="checkbox" id="cfgModal" checked> Usar modal</label>
                        <label class="small d-flex gap-2 align-items-center">
                            <input type="checkbox" id="cfgReqAll"> Todos los campos requeridos</label>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-6"><label class="ag-label">Ordenar por (orderby)</label>
                            <select class="form-select" id="cfgOrderField"><option value="">— ninguno —</option></select></div>
                        <div class="col-6"><label class="ag-label">Dirección</label>
                            <select class="form-select" id="cfgOrderDir">
                                <option value="desc">desc</option><option value="asc">asc</option>
                            </select></div>
                    </div>
                </div>
            </div>
            <div class="ag-card" data-sec data-modes="crud calendar">
                <div class="ag-sec-head" data-toggle>
                    <h6><i class="fa fa-sliders"></i> Acciones <span class="ag-badge" id="actCount"></span></h6>
                    <i class="fa fa-chevron-down" data-chev></i>
                </div>
                <div class="ag-sec-body" style="display:none;">
                    <div class="row g-2" id="actionsGrid"></div>
                </div>
            </div>
            <div class="ag-card" data-sec data-modes="crud calendar insert select">
                <div class="ag-sec-head" data-toggle>
                    <h6><i class="fa fa-table-columns"></i> Columnas y campos <span class="ag-badge" id="colCount"></span></h6>
                    <i class="fa fa-chevron-down" data-chev></i>
                </div>
                <div class="ag-sec-body" style="display:none;">
                    <div id="colsTable"><p class="text-muted small mb-0">Carga las columnas primero.</p></div>
                </div>
            </div>
            <div class="ag-card" data-sec data-modes="crud calendar chart">
                <div class="ag-sec-head" data-toggle>
                    <h6><i class="fa fa-filter"></i> Filtros fijos (where) <span class="ag-badge" id="whereCount"></span></h6>
                    <i class="fa fa-chevron-down" data-chev></i>
                </div>
                <div class="ag-sec-body" style="display:none;">
                    <div id="whereList"></div>
                    <button class="btn btn-sm btn-light" id="addWhere"><i class="fa fa-plus"></i> Añadir condición</button>
                </div>
            </div>
            <div class="ag-card" data-sec data-modes="crud calendar insert">
                <div class="ag-sec-head" data-toggle>
                    <h6><i class="fa fa-caret-down"></i> Comboboxes y dependientes <span class="ag-badge" id="comboCount"></span></h6>
                    <i class="fa fa-chevron-down" data-chev></i>
                </div>
                <div class="ag-sec-body" style="display:none;">
                    <div id="comboList"></div>
                    <button class="btn btn-sm btn-light" id="addCombo"><i class="fa fa-plus"></i> Añadir combobox</button>
                </div>
            </div>
            <div class="ag-card" data-sec data-modes="crud">
                <div class="ag-sec-head" data-toggle>
                    <h6><i class="fa fa-filter-circle-dollar"></i> Filtros avanzados <span class="ag-badge" id="advCount"></span></h6>
                    <i class="fa fa-chevron-down" data-chev></i>
                </div>
                <div class="ag-sec-body" style="display:none;">
                    <div class="row g-3 mb-2">
                        <div class="col-6"><label class="ag-label">Título del panel</label>
                            <input class="form-control" id="filterTitle" value="Advanced Filters"></div>
                        <div class="col-6"><label class="ag-label">Posición</label>
                            <select class="form-select" id="filterPos">
                                <option value="top">top</option><option value="bottom">bottom</option>
                                <option value="left">left</option><option value="right">right</option>
                            </select></div>
                    </div>
                    <label class="small d-flex gap-2 align-items-center mb-2">
                        <input type="checkbox" id="filterOpen"> Panel abierto por defecto</label>
                    <div id="filterList"></div>
                    <button class="btn btn-sm btn-light" id="addFilter"><i class="fa fa-plus"></i> Añadir filtro</button>
                </div>
            </div>
            <div class="ag-card" data-sec data-modes="crud">
                <div class="ag-sec-head" data-toggle>
                    <h6><i class="fa fa-sitemap"></i> Tablas anidadas (nested) <span class="ag-badge" id="nestCount"></span></h6>
                    <i class="fa fa-chevron-down" data-chev></i>
                </div>
                <div class="ag-sec-body" style="display:none;">
                    <div id="nestList"></div>
                    <button class="btn btn-sm btn-light" id="addNest"><i class="fa fa-plus"></i> Añadir tabla anidada</button>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="ag-sticky">
                <div class="ag-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <strong id="prevTitle">tabla</strong>
                        <span class="ag-chip" id="prevAdd"><i class="fa fa-plus"></i> Add</span>
                    </div>
                    <div id="prevFilter"></div>
                    <div style="overflow-x:auto;">
                        <table class="table table-sm ag-prev mb-0"><thead id="prevHead"></thead><tbody id="prevBody"></tbody></table>
                    </div>
                    <div class="small text-muted mt-2" id="prevNested"></div>
                    <p class="small text-muted mt-2 mb-0" id="prevMeta"></p>
                </div>

                <div class="ag-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <strong>Código PHP</strong>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-light" id="btnCopy"><i class="fa fa-copy"></i> Copiar</button>
                            <button class="btn btn-sm btn-light" id="btnDownload"><i class="fa fa-download"></i> .php</button>
                        </div>
                    </div>
                    <pre class="ag-code" id="codeOut"></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const ACTION_KEYS = ["add","view","edit","clone","delete","search","filter","refresh","checkbox","dropdownpage","pagination","delete_multiple","edit_multiple"];
const FIELD_TYPES = ["text","number","date","time","datetime","year","textarea","checkbox","image","file","select","hidden","email","password"];
const FILTER_TYPES = ["text","number","date","datetime","date_range","number_range","select","select_cascade","checkbox","radio","boolean"];
const uid = () => Math.random().toString(36).slice(2,8);

const S = {
  renderMode: "crud",
  sourceMode: "db",
  columns: [],
  actions: Object.fromEntries(ACTION_KEYS.map(k => [k, true])),
  requiredFields: [],
  colRenames: {},
  fieldTypeOverrides: {},
  whereConds: [],
  orderBy: { field: "", dir: "desc" },
  comboboxes: [],
  filters: [],
  nested: [],
  datasets: [{ id: uid(), label: "Serie 1", source: "static", data: "10, 25, 18, 30", color: "rgba(55,138,221,0.5)" }],
};

const RENDER_HINTS = {
  crud:     "Grilla CRUD completa con render().",
  calendar: "Calendario FullCalendar con render('calendar'). Reusa el modal y los formularios.",
  insert:   "Solo el formulario de alta con render('insert'), sin grilla.",
  select:   "Formulario de login con render('select') + callback beforeSelect.",
  chart:    "Gráfico con chart_view(true) + chart_labels(). Datasets estáticos o SQL.",
};

function parseDDL(ddl) {
  const out = { table: "", columns: [] };
  if (!ddl || !ddl.trim()) return out;
  const tm = ddl.match(/create\s+table\s+`?([a-z0-9_]+)`?/i);
  if (tm) out.table = tm[1];
  const open = ddl.indexOf("("), close = ddl.lastIndexOf(")");
  if (open === -1 || close === -1) return out;
  const body = ddl.slice(open + 1, close);
  const lines = []; let depth = 0, cur = "";
  for (const ch of body) {
    if (ch === "(") depth++;
    if (ch === ")") depth--;
    if (ch === "," && depth === 0) { lines.push(cur); cur = ""; } else cur += ch;
  }
  if (cur.trim()) lines.push(cur);
  const skip = /^\s*(primary\s+key|unique|key|index|constraint|foreign\s+key)/i;
  for (const raw of lines) {
    const line = raw.trim();
    if (!line || skip.test(line)) continue;
    const m = line.match(/^`?([a-z0-9_]+)`?\s+([a-z]+)(\([^)]*\))?/i);
    if (!m) continue;
    const name = m[1], sqlType = (m[2] || "").toLowerCase();
    let type = "text";
    if (sqlType === "tinyint") type = "checkbox";
    else if (["int","integer","bigint","smallint","decimal","float","double"].includes(sqlType)) type = "number";
    else if (sqlType === "date") type = "date";
    else if (sqlType === "time") type = "time";
    else if (["datetime","timestamp"].includes(sqlType)) type = "datetime";
    else if (sqlType === "year") type = "year";
    else if (["text","longtext","mediumtext"].includes(sqlType)) type = "textarea";
    const isPk = /primary\s+key/i.test(line) || /auto_increment/i.test(line);
    out.columns.push({ name, type, pk: isPk });
  }
  const pk = body.match(/primary\s+key\s*\(\s*`?([a-z0-9_]+)`?/i);
  if (pk) out.columns = out.columns.map(c => c.name === pk[1] ? { ...c, pk: true } : c);
  return out;
}

function genColumnConfigLines(chain, requiredAll) {
  const reqList = S.requiredFields.filter(Boolean);
  if (reqList.length && !requiredAll) chain.push(`    ->validation_required([${reqList.map(f => `'${f}'`).join(", ")}])`);
  Object.entries(S.colRenames).forEach(([from, to]) => { if (to && to.trim()) chain.push(`    ->colRename('${from}', '${to}')`); });
  Object.entries(S.fieldTypeOverrides).forEach(([f, t]) => { if (t) chain.push(`    ->fieldType('${f}', '${t}')`); });
}

function genComboLines(chain) {
  S.comboboxes.forEach(cb => {
    if (!cb.field) return;
    if (cb.source === "array") {
      const opts = (cb.options || []).filter(o => o.val).map(o => `        '${o.val}' => '${o.label || o.val}'`).join(",\n");
      chain.push(`    ->combobox('${cb.field}', [\n${opts}\n    ])`);
    } else {
      const args = [`'${cb.field}'`, `'${cb.table}'`, `'${cb.valueCol}'`];
      if (cb.labelCol.includes(",")) args.push(`[${cb.labelCol.split(",").map(s => `'${s.trim()}'`).join(", ")}]`);
      else args.push(`'${cb.labelCol}'`);
      if (cb.dependsOn) { args.push(`'${cb.dependsOn}'`); args.push(`'${cb.dependsField}'`); }
      chain.push(`    ->combobox(${args.join(", ")})`);
    }
  });
}

function generatePHP() {
  const mode = S.renderMode;
  const table = el("cfgTable").value, pk = el("cfgPk").value;
  const template = el("cfgTemplate").value, perPage = parseInt(el("cfgPerPage").value) || 10;
  const useModal = el("cfgModal").checked, requiredAll = el("cfgReqAll").checked;

  const L = [];
  L.push("<?php");
  L.push("require_once 'ArtiGrid.php';");
  L.push("");
  L.push("$grid = new ArtiGrid();");
  L.push("");

  if (mode === "chart") {
    const chain = [`$grid->table('${table || "your_table"}')`];
    chain.push(`    ->template('${template}')`);
    S.whereConds.forEach(w => { if (w.field) chain.push(`    ->where('${w.field}', '${w.op}', '${w.value}')`); });
    L.push(chain.join("\n") + ";");
    L.push("");

    const labels = el("chartLabels").value.split(",").map(s => `'${s.trim()}'`).join(", ");
    const dsLines = S.datasets.map(ds => {
      const dataExpr = ds.source === "sql"
        ? `'#${ds.data.replace(/^#/, "")}'`
        : `[${ds.data.split(",").map(v => v.trim()).join(", ")}]`;
      return `    [
        'label' => '${ds.label}',
        'data' => ${dataExpr},
        'backgroundColor' => '${ds.color}',
        'borderColor' => '${ds.color}',
        'borderWidth' => 1
    ]`;
    }).join(",\n");

    L.push(`$grid->chart_labels(`);
    L.push(`    [${labels}],`);
    L.push(`    [\n${dsLines}\n    ],`);
    L.push(`    '${el("chartType").value}',`);
    L.push(`    [`);
    L.push(`        'scales' => ['y' => ['beginAtZero' => true]],`);
    L.push(`        'plugins' => ['legend' => ['display' => true]]`);
    L.push(`    ]`);
    L.push(`);`);
    L.push("");
    L.push("$grid->chart_view(true);");
    L.push("");
    L.push("echo $grid->render();");
    return L.join("\n");
  }

  /* ===== LOGIN (select) ===== */
  if (mode === "select") {
    if (el("loginTemplate").checked) {
      L.push("$html = '");
      L.push("<div class=\"container d-flex justify-content-center align-items-center\">");
      L.push("    <div class=\"col-md-8 col-lg-4\">");
      L.push("        <div class=\"card shadow-lg border-0\">");
      L.push("            <div class=\"card-body p-4\">");
      L.push("                <h4 class=\"text-center mb-4\">Login</h4>");
      L.push(`                <div class="mb-3"><label class="form-label">Usuario *</label>{${el("loginUserField").value || "user"}}</div>`);
      L.push(`                <div class="mb-3"><label class="form-label">Contraseña *</label>{${el("loginPassField").value || "password"}}</div>`);
      L.push("                <div class=\"d-grid\">{action}</div>");
      L.push("            </div>");
      L.push("        </div>");
      L.push("    </div>");
      L.push("</div>';");
      L.push("$grid->setSelectFormTemplate($html);");
      L.push("");
    }
    const uField = el("loginUserField").value || "user";
    const pField = el("loginPassField").value || "password";
    const chain = [`$grid->table('${table || "users"}')`];
    chain.push(`    ->template('${template}')`);
    chain.push("    ->required(false)");
    chain.push(`    ->validation_required('${uField}')`);
    chain.push(`    ->validation_required('${pField}')`);
    chain.push(`    ->formFields(['${uField}', '${pField}'])`);
    L.push(chain.join("\n") + ";");
    L.push("");
    L.push("echo $grid->render('select');");

    if (el("loginCallback").checked) {
      L.push("");
      L.push("/* ============================================================");
      L.push(`   callbacks/${table || "users"}.php`);
      L.push("   ============================================================");
      L.push("return [");
      L.push("    'beforeSelect' => [");
      L.push("        ['callback' => 'login', 'file' => 'functions.php'],");
      L.push("    ]");
      L.push("];");
      L.push("");
      L.push("   functions.php");
      L.push("function login($data) {");
      L.push("    if (session_status() === PHP_SESSION_NONE) session_start();");
      L.push("    $db = DB::connect();");
      L.push(`    $user = trim($data['${uField}'] ?? '');`);
      L.push(`    $pass = trim($data['${pField}'] ?? '');`);
      L.push("    if ($user === '' || $pass === '')");
      L.push("        return ['success' => false, 'message' => 'Usuario o contraseña vacíos'];");
      L.push(`    $stmt = $db->prepare("SELECT * FROM ${table || "users"} WHERE ${uField} = ? LIMIT 1");`);
      L.push("    $stmt->execute([$user]);");
      L.push("    $row = $stmt->fetch(PDO::FETCH_ASSOC);");
      L.push("    if ($row && password_verify($pass, $row['password'])) {");
      L.push("        $_SESSION['artigrid_auth'] = [");
      L.push("            'id' => $row['id'], 'usuario' => $row['" + uField + "'],");
      L.push("            'rol' => $row['rol'] ?? 'user',");
      L.push("            'permissions' => ['view','add','edit','delete']");
      L.push("        ];");
      L.push("        return ['success' => true, 'message' => 'Login correcto', 'redirect' => 'panel.php'];");
      L.push("    }");
      L.push("    return ['success' => false, 'message' => 'Credenciales incorrectas'];");
      L.push("}");
      L.push("============================================================ */");
    }
    return L.join("\n");
  }

  const chain = [`$grid->table('${table || "your_table"}')`];
  chain.push(`    ->template('${template}')`);
  if (pk && pk !== "id") chain.push(`    ->primaryKey('${pk}')`);
  if (mode !== "insert") chain.push(`    ->perPage(${perPage})`);
  if (useModal && mode !== "insert") chain.push("    ->modal()");
  chain.push(`    ->required(${requiredAll ? "true" : "false"})`);

  if (mode === "crud" || mode === "calendar") {
    ACTION_KEYS.forEach(k => { if (S.actions[k] === false) chain.push(`    ->unset('${k}', false)`); });
  }

  genColumnConfigLines(chain, requiredAll);

  if (mode === "crud") {
    const visible = S.columns.filter(c => c.show).map(c => c.name);
    if (visible.length && visible.length !== S.columns.length) chain.push(`    ->crudCol([${visible.map(c => `'${c}'`).join(", ")}])`);
  }

  if (mode === "crud" || mode === "calendar") {
    S.whereConds.forEach(w => { if (w.field) chain.push(`    ->where('${w.field}', '${w.op}', '${w.value}')`); });
    if (S.orderBy.field) chain.push(`    ->orderby('${S.orderBy.field}', '${S.orderBy.dir}')`);
  }

  if (mode === "calendar") {
    const cal = [];
    const add = (k, id, q = true) => { const v = el(id).value; if (v) cal.push(`        '${k}' => ${q ? `'${v}'` : v}`); };
    add("titleField", "calTitle");
    add("startField", "calStart");
    if (el("calEnd").value) add("endField", "calEnd");
    if (el("calColor").value) add("colorField", "calColor");
    if (el("calAllDay").value) add("allDayField", "calAllDay");
    add("initialView", "calView");
    add("locale", "calLocale");
    chain.push(`    ->calendar([\n${cal.join(",\n")}\n    ])`);

    if (el("calPicker").checked && el("calColor").value) {
      const opts = [];
      if (el("calPalette").value) opts.push(`'palette' => '${el("calPalette").value}'`);
      opts.push(`'format' => 'hex'`);
      if (el("calPickerPos").value) opts.push(`'position' => '${el("calPickerPos").value}'`);
      chain.push(`    ->colorPicker('${el("calColor").value}', [${opts.join(", ")}])`);
    }
  }

  genComboLines(chain);

  L.push(chain.join("\n") + ";");
  L.push("");

  if (mode === "crud") {
    S.nested.forEach(nt => {
      if (!nt.childTable) return;
      const parts = [];
      const acts = Object.entries(nt.actions).filter(([, v]) => v).map(([k]) => `'${k}' => true`);
      if (acts.length) parts.push(`    "actions" => [${acts.join(", ")}]`);
      if (nt.columns) parts.push(`    "columns" => [${nt.columns.split(",").map(s => `'${s.trim()}'`).join(", ")}]`);
      if (nt.formFields) parts.push(`    "formFields" => [${nt.formFields.split(",").map(s => `'${s.trim()}'`).join(", ")}]`);
      if (nt.perPage) parts.push(`    "perPage" => ${nt.perPage}`);
      const cfg = parts.length ? `, [\n${parts.join(",\n")}\n]` : "";
      L.push(`$grid->nestedTable("${nt.label || nt.childTable}", "${nt.parentKey}", "${nt.childTable}", "${nt.childKey}"${cfg});`);
      L.push("");
    });
  }

  if (mode === "crud") {
    const filterTitle = el("filterTitle").value, filterOpen = el("filterOpen").checked, filterPosition = el("filterPos").value;
    const vf = S.filters.filter(f => f.field);
    if (vf.length) {
      if (filterTitle) L.push(`$grid->advancedFilterTitle('${filterTitle}');`);
      if (filterOpen) L.push("$grid->advancedFilterOpen(true);");
      if (filterPosition && filterPosition !== "top") L.push(`$grid->advancedFilterPosition('${filterPosition}');`);
      L.push("");
      vf.forEach(f => {
        const extra = [];
        if (f.label) extra.push(`'label' => '${f.label}'`);
        if (f.operator) extra.push(`'operator' => '${f.operator}'`);
        if (f.group) extra.push(`'group' => '${f.group}'`);
        if (f.type === "select_cascade") {
          if (f.dependsOn) extra.push(`'depends_on' => '${f.dependsOn}'`);
          if (f.cTable) extra.push(`'table' => '${f.cTable}'`);
          if (f.cValue) extra.push(`'value' => '${f.cValue}'`);
          if (f.cLabel) extra.push(`'label_col' => '${f.cLabel}'`);
          if (f.dependsField) extra.push(`'depends_field' => '${f.dependsField}'`);
        }
        let optsArg = "[]";
        if (["select","checkbox","radio"].includes(f.type) && f.options) {
          optsArg = `[${f.options.split(",").map(p => { const t = p.trim(); return `'${t}' => '${t}'`; }).join(", ")}]`;
        }
        const extraArg = extra.length ? `, [${extra.join(", ")}]` : "";
        L.push(`$grid->advancedFilter('${f.field}', '${f.type}', ${optsArg}${extraArg});`);
      });
      L.push("");
      if (!filterPosition || filterPosition === "top") L.push("echo $grid->renderAdvancedFilterPanel();");
    }
  }

  if (mode === "calendar") L.push("echo $grid->render('calendar');");
  else if (mode === "insert") L.push("echo $grid->render('insert');");
  else L.push("echo $grid->render();");

  return L.join("\n");
}

const el = (id) => document.getElementById(id);
const colNames = () => S.columns.map(c => c.name);
function optionsHtml(selected) {
  return `<option value="">—</option>` + colNames().map(n =>
    `<option value="${n}" ${n === selected ? "selected" : ""}>${n}</option>`).join("");
}

function renderActions() {
  el("actionsGrid").innerHTML = ACTION_KEYS.map(k => `
    <div class="col-4">
      <label class="small d-flex gap-2 align-items-center">
        <input type="checkbox" data-act="${k}" ${S.actions[k] ? "checked" : ""}> ${k}
      </label>
    </div>`).join("");
  el("actionsGrid").querySelectorAll("[data-act]").forEach(cb =>
    cb.addEventListener("change", e => { S.actions[e.target.dataset.act] = e.target.checked; refresh(); }));
  el("actCount").textContent = ACTION_KEYS.filter(k => S.actions[k]).length;
}

function renderCols() {
  const box = el("colsTable");
  if (!S.columns.length) { box.innerHTML = `<p class="text-muted small mb-0">Carga las columnas primero.</p>`; el("colCount").textContent = ""; return; }
  let h = `<div class="row gx-2 mb-1 small text-muted">
      <div class="col-1"></div><div class="col-3">Columna</div><div class="col-3">Renombrar</div><div class="col-3">Tipo</div><div class="col-2">Req.</div></div>`;
  S.columns.forEach((c, i) => {
    h += `<div class="row gx-2 mb-1 align-items-center">
      <div class="col-1"><input type="checkbox" data-cshow="${i}" ${c.show ? "checked" : ""}></div>
      <div class="col-3 ag-mono small">${c.name}</div>
      <div class="col-3"><input class="form-control form-control-sm" data-crename="${c.name}" value="${S.colRenames[c.name] || ""}" placeholder="(label)"></div>
      <div class="col-3"><select class="form-select form-select-sm" data-ctype="${c.name}">
        <option value="">auto</option>
        ${FIELD_TYPES.map(t => `<option value="${t}" ${S.fieldTypeOverrides[c.name] === t ? "selected" : ""}>${t}</option>`).join("")}
      </select></div>
      <div class="col-2"><input type="checkbox" data-creq="${c.name}" ${S.requiredFields.includes(c.name) ? "checked" : ""}></div>
    </div>`;
  });
  box.innerHTML = h;
  box.querySelectorAll("[data-cshow]").forEach(x => x.addEventListener("change", e => { S.columns[+e.target.dataset.cshow].show = e.target.checked; refresh(); }));
  box.querySelectorAll("[data-crename]").forEach(x => x.addEventListener("input", e => { S.colRenames[e.target.dataset.crename] = e.target.value; refresh(); }));
  box.querySelectorAll("[data-ctype]").forEach(x => x.addEventListener("change", e => { S.fieldTypeOverrides[e.target.dataset.ctype] = e.target.value; refresh(); }));
  box.querySelectorAll("[data-creq]").forEach(x => x.addEventListener("change", e => {
    const n = e.target.dataset.creq;
    S.requiredFields = e.target.checked ? [...S.requiredFields, n] : S.requiredFields.filter(f => f !== n);
    refresh();
  }));
  el("colCount").textContent = S.columns.length;
}

function renderWhere() {
  el("whereList").innerHTML = S.whereConds.map(w => `
    <div class="ag-row mb-2" data-wid="${w.id}">
      <select class="form-select form-select-sm" style="flex:2" data-wf>${optionsHtml(w.field)}</select>
      <select class="form-select form-select-sm" style="flex:1" data-wo>
        ${["=","!=",">","<",">=","<=","LIKE"].map(o => `<option ${o === w.op ? "selected" : ""}>${o}</option>`).join("")}
      </select>
      <input class="form-control form-control-sm" style="flex:2" data-wv value="${w.value}" placeholder="valor">
      <button class="btn btn-sm btn-light" data-wdel><i class="fa fa-trash"></i></button>
    </div>`).join("");
  el("whereList").querySelectorAll("[data-wid]").forEach(row => {
    const id = row.dataset.wid, w = S.whereConds.find(x => x.id === id);
    row.querySelector("[data-wf]").addEventListener("change", e => { w.field = e.target.value; refresh(); });
    row.querySelector("[data-wo]").addEventListener("change", e => { w.op = e.target.value; refresh(); });
    row.querySelector("[data-wv]").addEventListener("input", e => { w.value = e.target.value; refresh(); });
    row.querySelector("[data-wdel]").addEventListener("click", () => { S.whereConds = S.whereConds.filter(x => x.id !== id); renderWhere(); refresh(); });
  });
  el("whereCount").textContent = S.whereConds.length || "";
}

function renderCombos() {
  el("comboList").innerHTML = S.comboboxes.map(cb => `
    <div class="ag-sub" data-cbid="${cb.id}">
      <div class="ag-row">
        <div style="flex:1"><label class="ag-label">Campo</label>
          <select class="form-select form-select-sm" data-cbfield>${optionsHtml(cb.field)}</select></div>
        <div style="flex:1"><label class="ag-label">Origen</label>
          <select class="form-select form-select-sm" data-cbsrc>
            <option value="table" ${cb.source === "table" ? "selected" : ""}>Tabla</option>
            <option value="array" ${cb.source === "array" ? "selected" : ""}>Array</option>
          </select></div>
        <button class="btn btn-sm btn-light" data-cbdel><i class="fa fa-trash"></i></button>
      </div>
      ${cb.source === "table" ? `
      <div class="row g-2 mt-1">
        <div class="col-4"><label class="ag-label">Tabla</label><input class="form-control form-control-sm" data-cbtable value="${cb.table}"></div>
        <div class="col-4"><label class="ag-label">Col. valor</label><input class="form-control form-control-sm" data-cbval value="${cb.valueCol}"></div>
        <div class="col-4"><label class="ag-label">Col. label</label><input class="form-control form-control-sm" data-cblabel value="${cb.labelCol}" placeholder="name o name,lastName"></div>
        <div class="col-6"><label class="ag-label">Depende de (padre)</label><input class="form-control form-control-sm" data-cbdep value="${cb.dependsOn}" placeholder="(opcional)"></div>
        <div class="col-6"><label class="ag-label">Col. FK en hija</label><input class="form-control form-control-sm" data-cbdepf value="${cb.dependsField}" placeholder="(opcional)"></div>
      </div>` : `
      <div class="mt-1"><label class="ag-label">Opciones (valor:etiqueta por línea)</label>
        <textarea class="form-control form-control-sm" data-cbopts rows="3" placeholder="activo:Activo&#10;inactivo:Inactivo">${(cb.options || []).map(o => `${o.val}:${o.label}`).join("\n")}</textarea></div>`}
    </div>`).join("");
  el("comboList").querySelectorAll("[data-cbid]").forEach(row => {
    const id = row.dataset.cbid, cb = S.comboboxes.find(x => x.id === id);
    row.querySelector("[data-cbfield]").addEventListener("change", e => { cb.field = e.target.value; refresh(); });
    row.querySelector("[data-cbsrc]").addEventListener("change", e => { cb.source = e.target.value; renderCombos(); refresh(); });
    row.querySelector("[data-cbdel]").addEventListener("click", () => { S.comboboxes = S.comboboxes.filter(x => x.id !== id); renderCombos(); refresh(); });
    if (cb.source === "table") {
      row.querySelector("[data-cbtable]").addEventListener("input", e => { cb.table = e.target.value; refresh(); });
      row.querySelector("[data-cbval]").addEventListener("input", e => { cb.valueCol = e.target.value; refresh(); });
      row.querySelector("[data-cblabel]").addEventListener("input", e => { cb.labelCol = e.target.value; refresh(); });
      row.querySelector("[data-cbdep]").addEventListener("input", e => { cb.dependsOn = e.target.value; refresh(); });
      row.querySelector("[data-cbdepf]").addEventListener("input", e => { cb.dependsField = e.target.value; refresh(); });
    } else {
      row.querySelector("[data-cbopts]").addEventListener("input", e => {
        cb.options = e.target.value.split("\n").map(l => { const [val, label] = l.split(":"); return { val: (val || "").trim(), label: (label || "").trim() }; });
        refresh();
      });
    }
  });
  el("comboCount").textContent = S.comboboxes.length || "";
}

function renderFilters() {
  el("filterList").innerHTML = S.filters.map(f => `
    <div class="ag-sub" data-fid="${f.id}">
      <div class="row g-2 align-items-end">
        <div class="col-4"><label class="ag-label">Campo</label><select class="form-select form-select-sm" data-ffield>${optionsHtml(f.field)}</select></div>
        <div class="col-4"><label class="ag-label">Tipo</label>
          <select class="form-select form-select-sm" data-ftype>${FILTER_TYPES.map(t => `<option value="${t}" ${t === f.type ? "selected" : ""}>${t}</option>`).join("")}</select></div>
        <div class="col-3"><label class="ag-label">Label</label><input class="form-control form-control-sm" data-flabel value="${f.label}"></div>
        <div class="col-1"><button class="btn btn-sm btn-light" data-fdel><i class="fa fa-trash"></i></button></div>
      </div>
      <div class="row g-2 mt-1">
        <div class="col-6"><label class="ag-label">Operador</label><input class="form-control form-control-sm" data-fop value="${f.operator}" placeholder="LIKE, =, >=…"></div>
        <div class="col-6"><label class="ag-label">Grupo</label><input class="form-control form-control-sm" data-fgroup value="${f.group}" placeholder="(opcional)"></div>
      </div>
      ${["select","checkbox","radio"].includes(f.type) ? `
      <div class="mt-1"><label class="ag-label">Opciones (separadas por coma)</label>
        <input class="form-control form-control-sm" data-fopts value="${f.options}" placeholder="Activo, Inactivo, Pendiente"></div>` : ""}
      ${f.type === "select_cascade" ? `
      <div class="row g-2 mt-1">
        <div class="col-4"><label class="ag-label">Depende de</label><input class="form-control form-control-sm" data-fdep value="${f.dependsOn}"></div>
        <div class="col-4"><label class="ag-label">Tabla</label><input class="form-control form-control-sm" data-fctable value="${f.cTable}"></div>
        <div class="col-4"><label class="ag-label">Col. valor</label><input class="form-control form-control-sm" data-fcval value="${f.cValue}"></div>
        <div class="col-6"><label class="ag-label">Col. label</label><input class="form-control form-control-sm" data-fclabel value="${f.cLabel}"></div>
        <div class="col-6"><label class="ag-label">Col. FK hija</label><input class="form-control form-control-sm" data-fdepf value="${f.dependsField}"></div>
      </div>` : ""}
    </div>`).join("");
  el("filterList").querySelectorAll("[data-fid]").forEach(row => {
    const id = row.dataset.fid, f = S.filters.find(x => x.id === id);
    const bind = (sel, key, re) => { const n = row.querySelector(sel); if (n) n.addEventListener("input", e => { f[key] = e.target.value; if (re) renderFilters(); refresh(); }); };
    row.querySelector("[data-ffield]").addEventListener("change", e => { f.field = e.target.value; refresh(); });
    row.querySelector("[data-ftype]").addEventListener("change", e => { f.type = e.target.value; renderFilters(); refresh(); });
    bind("[data-flabel]", "label"); bind("[data-fop]", "operator"); bind("[data-fgroup]", "group");
    bind("[data-fopts]", "options"); bind("[data-fdep]", "dependsOn"); bind("[data-fctable]", "cTable");
    bind("[data-fcval]", "cValue"); bind("[data-fclabel]", "cLabel"); bind("[data-fdepf]", "dependsField");
    row.querySelector("[data-fdel]").addEventListener("click", () => { S.filters = S.filters.filter(x => x.id !== id); renderFilters(); refresh(); });
  });
  el("advCount").textContent = S.filters.length || "";
}

function renderNested() {
  el("nestList").innerHTML = S.nested.map(nt => `
    <div class="ag-sub" data-nid="${nt.id}">
      <div class="row g-2 align-items-end">
        <div class="col-5"><label class="ag-label">Etiqueta</label><input class="form-control form-control-sm" data-nlabel value="${nt.label}"></div>
        <div class="col-6"><label class="ag-label">Tabla hija</label><input class="form-control form-control-sm" data-nchild value="${nt.childTable}"></div>
        <div class="col-1"><button class="btn btn-sm btn-light" data-ndel><i class="fa fa-trash"></i></button></div>
      </div>
      <div class="row g-2 mt-1">
        <div class="col-5"><label class="ag-label">Parent key</label><input class="form-control form-control-sm" data-npk value="${nt.parentKey}"></div>
        <div class="col-5"><label class="ag-label">Child key</label><input class="form-control form-control-sm" data-nck value="${nt.childKey}"></div>
        <div class="col-2"><label class="ag-label">perPage</label><input type="number" class="form-control form-control-sm" data-npp value="${nt.perPage}"></div>
      </div>
      <div class="row g-2 mt-1">
        <div class="col-6"><label class="ag-label">Columnas (coma)</label><input class="form-control form-control-sm" data-ncols value="${nt.columns}"></div>
        <div class="col-6"><label class="ag-label">Campos form (coma)</label><input class="form-control form-control-sm" data-nff value="${nt.formFields}"></div>
      </div>
      <div class="d-flex gap-3 mt-2 flex-wrap">
        ${["add","edit","delete","view"].map(a => `<label class="small d-flex gap-1 align-items-center"><input type="checkbox" data-nact="${a}" ${nt.actions[a] ? "checked" : ""}>${a}</label>`).join("")}
      </div>
    </div>`).join("");
  el("nestList").querySelectorAll("[data-nid]").forEach(row => {
    const id = row.dataset.nid, nt = S.nested.find(x => x.id === id);
    const bind = (sel, key, num) => row.querySelector(sel).addEventListener("input", e => { nt[key] = num ? (parseInt(e.target.value) || 5) : e.target.value; refresh(); });
    bind("[data-nlabel]", "label"); bind("[data-nchild]", "childTable"); bind("[data-npk]", "parentKey");
    bind("[data-nck]", "childKey"); bind("[data-npp]", "perPage", true); bind("[data-ncols]", "columns"); bind("[data-nff]", "formFields");
    row.querySelectorAll("[data-nact]").forEach(x => x.addEventListener("change", e => { nt.actions[e.target.dataset.nact] = e.target.checked; refresh(); }));
    row.querySelector("[data-ndel]").addEventListener("click", () => { S.nested = S.nested.filter(x => x.id !== id); renderNested(); refresh(); });
  });
  el("nestCount").textContent = S.nested.length || "";
}
function renderDatasets() {
  el("dsList").innerHTML = S.datasets.map(ds => `
    <div class="ag-sub" data-dsid="${ds.id}">
      <div class="row g-2 align-items-end">
        <div class="col-4"><label class="ag-label">Etiqueta</label><input class="form-control form-control-sm" data-dslabel value="${ds.label}"></div>
        <div class="col-3"><label class="ag-label">Origen</label>
          <select class="form-select form-select-sm" data-dssrc>
            <option value="static" ${ds.source === "static" ? "selected" : ""}>Estático</option>
            <option value="sql" ${ds.source === "sql" ? "selected" : ""}>SQL (#)</option>
          </select></div>
        <div class="col-4"><label class="ag-label">Color</label><input class="form-control form-control-sm" data-dscolor value="${ds.color}"></div>
        <div class="col-1"><button class="btn btn-sm btn-light" data-dsdel><i class="fa fa-trash"></i></button></div>
      </div>
      <div class="mt-1"><label class="ag-label">${ds.source === "sql" ? "Consulta SQL (sin el #)" : "Valores (separados por coma)"}</label>
        <input class="form-control form-control-sm ag-mono" data-dsdata value="${ds.data}" placeholder="${ds.source === "sql" ? "select quantityOrdered from orderdetails where id IN (60,61,62)" : "10, 25, 18, 30"}"></div>
    </div>`).join("");
  el("dsList").querySelectorAll("[data-dsid]").forEach(row => {
    const id = row.dataset.dsid, ds = S.datasets.find(x => x.id === id);
    row.querySelector("[data-dslabel]").addEventListener("input", e => { ds.label = e.target.value; refresh(); });
    row.querySelector("[data-dssrc]").addEventListener("change", e => { ds.source = e.target.value; renderDatasets(); refresh(); });
    row.querySelector("[data-dscolor]").addEventListener("input", e => { ds.color = e.target.value; refresh(); });
    row.querySelector("[data-dsdata]").addEventListener("input", e => { ds.data = e.target.value; refresh(); });
    row.querySelector("[data-dsdel]").addEventListener("click", () => { S.datasets = S.datasets.filter(x => x.id !== id); renderDatasets(); refresh(); });
  });
  el("dsCount").textContent = S.datasets.length || "";
}
function fillColumnSelects() {
  const opts = (withEmpty) => (withEmpty ? `<option value="">—</option>` : "") + colNames().map(n => `<option value="${n}">${n}</option>`).join("");
  const set = (id, withEmpty, preferred) => {
    const sel = el(id); if (!sel) return;
    const prev = sel.value;
    sel.innerHTML = opts(withEmpty);
   
    if (preferred && colNames().includes(preferred)) sel.value = preferred;
    else if (prev && colNames().includes(prev)) sel.value = prev;
  };
  set("calTitle", false, "title");
  set("calStart", false, colNames().find(n => /start|fecha|date/i.test(n)));
  set("calEnd", true, colNames().find(n => /end|fin/i.test(n)));
  set("calColor", true, colNames().find(n => /color/i.test(n)));
  set("calAllDay", true, colNames().find(n => /all_day|todo_dia/i.test(n)));

  set("loginUserField", false, colNames().find(n => /user|usuario|email|rut/i.test(n)));
  set("loginPassField", false, colNames().find(n => /pass|clave/i.test(n)));
}
function renderPreview() {
  const mode = S.renderMode;
  const table = el("cfgTable").value, pk = el("cfgPk").value;
  const vis = S.columns.filter(c => c.show);
  el("prevTitle").textContent = (table || "tabla") + " · " + mode;
  const a = S.actions;
  el("prevAdd").style.display = (mode === "crud" || mode === "calendar") && a.add ? "" : "none";
  if (mode === "chart") {
    el("prevFilter").innerHTML = "";
    el("prevHead").innerHTML = "";
    el("prevBody").innerHTML = `<tr><td class="text-muted small py-3"><i class="fa fa-chart-column me-2"></i>Gráfico ${el("chartType").value} · ${S.datasets.length} dataset(s)</td></tr>`;
    el("prevNested").innerHTML = "";
    el("prevMeta").textContent = `Vista previa simulada · labels: ${el("chartLabels").value}`;
    return;
  }
  if (mode === "select") {
    el("prevFilter").innerHTML = "";
    el("prevHead").innerHTML = "";
    el("prevBody").innerHTML = `<tr><td class="text-muted small py-3"><i class="fa fa-right-to-bracket me-2"></i>Formulario de login (${el("loginUserField").value || "user"} / ${el("loginPassField").value || "password"})</td></tr>`;
    el("prevNested").innerHTML = el("loginCallback").checked ? '<i class="fa fa-code"></i> + callback beforeSelect' : "";
    el("prevMeta").textContent = "Vista previa simulada · render('select')";
    return;
  }
  if (mode === "calendar") {
    el("prevFilter").innerHTML = "";
    el("prevHead").innerHTML = "";
    const cells = Array.from({ length: 14 }, (_, i) => `<span style="display:inline-flex;width:13%;height:26px;margin:1px;border:0.5px solid #e2e5ea;border-radius:4px;align-items:center;justify-content:center;font-size:10px;color:#999">${i + 1}</span>`).join("");
    el("prevBody").innerHTML = `<tr><td style="padding:0"><div style="display:flex;flex-wrap:wrap">${cells}</div>
      <div class="small text-muted mt-1">title: ${el("calTitle").value || "?"} · start: ${el("calStart").value || "?"}${el("calColor").value ? " · color: " + el("calColor").value + (el("calPicker").checked ? " (picker)" : "") : ""}</div></td></tr>`;
    el("prevNested").innerHTML = "";
    el("prevMeta").textContent = `Vista previa simulada · render('calendar') · ${el("calView").value}`;
    return;
  }
  const vf = S.filters.filter(f => f.field);
  el("prevFilter").innerHTML = (mode === "crud" && vf.length)
    ? `<div class="small text-muted border border-1 rounded p-2 mb-2" style="border-style:dashed!important"><i class="fa fa-filter"></i> ${el("filterTitle").value} (${vf.length})</div>` : "";

  let head = "<tr>";
  if (mode === "crud" && a.checkbox) head += `<th><input type="checkbox" disabled></th>`;
  vis.forEach(c => head += `<th>${S.colRenames[c.name] || c.name}</th>`);
  if (mode === "crud") head += `<th>Actions</th>`;
  head += "</tr>";
  el("prevHead").innerHTML = head;

  let body = "";
  const rows = mode === "insert" ? 1 : 3;
  for (let r = 0; r < rows; r++) {
    body += "<tr>";
    if (mode === "crud" && a.checkbox) body += `<td><input type="checkbox" disabled></td>`;
    vis.forEach(c => body += `<td class="text-muted">${mode === "insert" ? '<input class="form-control form-control-sm" disabled placeholder="' + c.name + '">' : (c.name === pk ? (r + 1) : "···")}</td>`);
    if (mode === "crud") body += `<td class="text-muted">${a.view ? '<i class="fa fa-eye me-2"></i>' : ""}${a.edit ? '<i class="fa fa-pencil me-2"></i>' : ""}${a.delete ? '<i class="fa fa-trash"></i>' : ""}</td>`;
    body += "</tr>";
  }
  el("prevBody").innerHTML = body;

  const nn = mode === "crud" ? S.nested.filter(n => n.childTable) : [];
  el("prevNested").innerHTML = nn.length ? `<i class="fa fa-sitemap"></i> ${nn.map(n => n.label || n.childTable).join(", ")}` : "";
  el("prevMeta").textContent = `Vista previa simulada · ${mode === "insert" ? "render('insert')" : "perPage " + (parseInt(el("cfgPerPage").value) || 10)} · ${el("cfgTemplate").value}`;
}

function refresh() {
  el("codeOut").textContent = generatePHP();
  renderPreview();
}
function setColumns(cols, ft) {
  S.columns = cols.map(c => ({ name: c.name, show: true }));
  S.fieldTypeOverrides = ft || {};
  el("cfgOrderField").innerHTML = `<option value="">— ninguno —</option>` + colNames().map(n => `<option value="${n}">${n}</option>`).join("");
  el("colsInfo").textContent = `${cols.length} columnas cargadas`;
  fillColumnSelects();
  renderCols(); renderWhere(); renderCombos(); renderFilters(); refresh();
}

async function loadFromDb(table) {
  const res = await fetch("builder_ajax.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({ action: "describe", table }),
  });
  const cols = await res.json();
  if (cols.error) { alert(cols.error); return; }
  const ft = {};
  cols.forEach(c => { if (c.type !== "text") ft[c.name] = c.type; });
  const pk = cols.find(c => c.pk);
  if (pk) el("cfgPk").value = pk.name;
  el("cfgTable").value = table;
  setColumns(cols, ft);
}

function loadColumns() {
  if (S.sourceMode === "db") {
    const t = el("dbTable").value;
    if (!t) { alert("Elige una tabla."); return; }
    loadFromDb(t);
  } else if (S.sourceMode === "ddl") {
    const p = parseDDL(el("ddl").value);
    if (p.table) el("cfgTable").value = p.table;
    const pk = p.columns.find(c => c.pk);
    if (pk) el("cfgPk").value = pk.name;
    const ft = {}; p.columns.forEach(c => { if (c.type !== "text") ft[c.name] = c.type; });
    setColumns(p.columns, ft);
  } else {
    const names = el("manualCols").value.split(",").map(s => s.trim()).filter(Boolean);
    setColumns(names.map(n => ({ name: n })), {});
  }
}
function setMode(m) {
  S.sourceMode = m;
  el("paneDb").style.display = m === "db" ? "" : "none";
  el("paneManual").style.display = m === "manual" ? "" : "none";
  el("paneDdl").style.display = m === "ddl" ? "" : "none";
  ["modeDb","modeManual","modeDdl"].forEach(id => el(id).classList.remove("active"));
  el(m === "db" ? "modeDb" : m === "manual" ? "modeManual" : "modeDdl").classList.add("active");
}
function applyRenderMode(m) {
  S.renderMode = m;
  document.querySelectorAll("[data-render]").forEach(b => b.classList.toggle("active", b.dataset.render === m));
  el("renderHint").textContent = RENDER_HINTS[m] || "";

  document.querySelectorAll("[data-only]").forEach(sec => {
    sec.style.display = sec.dataset.only === m ? "" : "none";
  });
  document.querySelectorAll("[data-modes]").forEach(sec => {
    const allowed = sec.dataset.modes.split(" ");
    sec.style.display = allowed.includes(m) ? "" : "none";
  });
  refresh();
}

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("[data-toggle]").forEach(h => {
    h.addEventListener("click", () => {
      const body = h.parentElement.querySelector(".ag-sec-body");
      const chev = h.querySelector("[data-chev]");
      const open = body.style.display !== "none";
      body.style.display = open ? "none" : "";
      chev.className = "fa fa-chevron-" + (open ? "down" : "up");
    });
  });

  document.querySelectorAll("[data-render]").forEach(b => b.addEventListener("click", () => applyRenderMode(b.dataset.render)));

  el("modeDb").addEventListener("click", () => setMode("db"));
  el("modeManual").addEventListener("click", () => setMode("manual"));
  el("modeDdl").addEventListener("click", () => setMode("ddl"));
  el("loadCols").addEventListener("click", loadColumns);
  el("dbTable").addEventListener("change", e => { if (e.target.value) loadFromDb(e.target.value); });

  ["cfgTable","cfgPk","cfgTemplate","cfgPerPage","cfgModal","cfgReqAll","filterTitle","filterPos","filterOpen"].forEach(id =>
    el(id).addEventListener("input", refresh));
  el("cfgOrderField").addEventListener("change", e => { S.orderBy.field = e.target.value; refresh(); });
  el("cfgOrderDir").addEventListener("change", e => { S.orderBy.dir = e.target.value; refresh(); });

  ["calTitle","calStart","calEnd","calColor","calAllDay","calView","calLocale","calPicker","calPalette","calPickerPos"].forEach(id =>
    el(id).addEventListener("input", () => {
      el("calPickerOpts").style.display = el("calPicker").checked ? "" : "none";
      refresh();
    }));

  ["chartType","chartLabels"].forEach(id => el(id).addEventListener("input", refresh));
  el("addDs").addEventListener("click", () => { S.datasets.push({ id: uid(), label: "Serie " + (S.datasets.length + 1), source: "static", data: "0, 0, 0, 0", color: "rgba(40,167,69,0.5)" }); renderDatasets(); refresh(); });

  ["loginUserField","loginPassField","loginTemplate","loginCallback"].forEach(id => el(id).addEventListener("input", refresh));

  el("addWhere").addEventListener("click", () => { S.whereConds.push({ id: uid(), field: "", op: "=", value: "" }); renderWhere(); refresh(); });
  el("addCombo").addEventListener("click", () => { S.comboboxes.push({ id: uid(), field: "", source: "table", table: "", valueCol: "id", labelCol: "name", dependsOn: "", dependsField: "", options: [{ val: "", label: "" }] }); renderCombos(); refresh(); });
  el("addFilter").addEventListener("click", () => { S.filters.push({ id: uid(), field: "", type: "text", label: "", operator: "", group: "", options: "", dependsOn: "", cTable: "", cValue: "id", cLabel: "name", dependsField: "" }); renderFilters(); refresh(); });
  el("addNest").addEventListener("click", () => { S.nested.push({ id: uid(), label: "", parentKey: el("cfgPk").value, childTable: "", childKey: "", columns: "", formFields: "", perPage: 5, actions: { add: true, edit: true, delete: true, view: true } }); renderNested(); refresh(); });

  el("btnCopy").addEventListener("click", () => {
    navigator.clipboard.writeText(el("codeOut").textContent).then(() => {
      const b = el("btnCopy"); const old = b.innerHTML;
      b.innerHTML = '<i class="fa fa-check"></i> Copiado';
      setTimeout(() => b.innerHTML = old, 1500);
    });
  });
  el("btnDownload").addEventListener("click", () => {
    const blob = new Blob([el("codeOut").textContent], { type: "text/plain" });
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = (el("cfgTable").value || "grid") + ".php";
    a.click(); URL.revokeObjectURL(a.href);
  });

  renderActions();
  renderDatasets();
  applyRenderMode("crud");
});
</script>
</body>
</html>
