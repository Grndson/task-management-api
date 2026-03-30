<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Tasks</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/tasks.css') }}"/>
</head>
<body>

<div id="toast"></div>

<div class="wrap">

  <div class="page-header">
    <h1>Tasks</h1>
    <p>task management api</p>
  </div>

  <div class="stats-row">
    <div class="stat"><span class="stat-num" id="s-total">—</span><span class="stat-label">Total</span></div>
    <div class="stat"><span class="stat-num" id="s-pending">—</span><span class="stat-label">Pending</span></div>
    <div class="stat"><span class="stat-num" id="s-inprog">—</span><span class="stat-label">In Progress</span></div>
    <div class="stat"><span class="stat-num" id="s-done">—</span><span class="stat-label">Done</span></div>
  </div>

  <div class="layout">

    <!-- Create form -->
    <div class="sidebar">
      <div class="section-label">New task</div>

      <div class="field">
        <label>Title</label>
        <input id="f-title" type="text" placeholder="Task title"/>
        <div class="field-err" id="e-title"></div>
      </div>

      <div class="field">
        <label>Due date</label>
        <input id="f-date" type="date"/>
        <div class="field-err" id="e-date"></div>
      </div>

      <div class="field">
        <label>Priority</label>
        <select id="f-priority">
          <option value="">Select...</option>
          <option value="high">High</option>
          <option value="medium">Medium</option>
          <option value="low">Low</option>
        </select>
        <div class="field-err" id="e-priority"></div>
      </div>

      <button class="btn-create" id="btn-submit" onclick="createTask()">Add task</button>

      <!-- Daily Report -->
      <div class="section-label" style="margin-top:32px">Daily report</div>

      <div class="field">
        <label>Date</label>
        <input id="r-date" type="date"/>
      </div>

      <button class="btn-report" id="btn-report" onclick="loadReport()">Generate report</button>

      <div id="report-output"></div>
    </div>

    <!-- Task list -->
    <div>
      <div class="list-header">
        <div class="section-label" style="margin:0">All tasks</div>
        <div class="filters">
          <button class="filter-btn active" onclick="setFilter(this,'')">All</button>
          <button class="filter-btn" onclick="setFilter(this,'pending')">Pending</button>
          <button class="filter-btn" onclick="setFilter(this,'in_progress')">In progress</button>
          <button class="filter-btn" onclick="setFilter(this,'done')">Done</button>
        </div>
      </div>

      <div id="task-list">
        <div class="state-msg">loading...</div>
      </div>
    </div>

  </div>
</div>

<script src="{{ asset('js/tasks.js') }}"></script>
</body>
</html>