<?php
declare(strict_types=1);
script('adminoffboard', 'vue.min');
script('adminoffboard', 'vue-router.min');
script('adminoffboard', 'adminoffboard');
style('adminoffboard', 'adminoffboard');
$version = $_['version'] ?? '0.1.0';
?>
<div id="adminoffboard-app">
    <div class="adminoffboard-container" style="display:flex; height:100vh;">
        <nav class="adminoffboard-nav" style="width:280px; background:var(--color-primary); color:var(--color-primary-text); padding:20px 0; flex-shrink:0;">
            <div class="nav-header" style="padding:0 20px 20px; border-bottom:1px solid rgba(255,255,255,0.1);">
                <h1 style="font-size:18px; font-weight:600; margin:0;">Admin Offboard</h1>
                <span class="version" style="font-size:11px; opacity:0.7;">v<?php p($version); ?></span>
            </div>
            <ul class="nav-menu" style="list-style:none; padding:20px 0; margin:0;">
                <li class="nav-item" data-view="dashboard"><a href="#/dashboard" style="display:flex; align-items:center; padding:10px 15px; color:rgba(255,255,255,0.8); text-decoration:none; cursor:pointer;">Dashboard</a></li>
                <li class="nav-item" data-view="offboard"><a href="#/offboard" style="display:flex; align-items:center; padding:10px 15px; color:rgba(255,255,255,0.8); text-decoration:none; cursor:pointer;">Offboard Users</a></li>
                <li class="nav-item" data-view="bulk"><a href="#/bulk" style="display:flex; align-items:center; padding:10px 15px; color:rgba(255,255,255,0.8); text-decoration:none; cursor:pointer;">Bulk Operations</a></li>
                <li class="nav-item" data-view="audit"><a href="#/audit" style="display:flex; align-items:center; padding:10px 15px; color:rgba(255,255,255,0.8); text-decoration:none; cursor:pointer;">Audit Logs</a></li>
                <li class="nav-item" data-view="queue"><a href="#/queue" style="display:flex; align-items:center; padding:10px 15px; color:rgba(255,255,255,0.8); text-decoration:none; cursor:pointer;">Queue</a></li>
                <li class="nav-item" data-view="settings"><a href="#/settings" style="display:flex; align-items:center; padding:10px 15px; color:rgba(255,255,255,0.8); text-decoration:none; cursor:pointer;">Settings</a></li>
            </ul>
        </nav>
        <main id="app-content" style="flex:1; padding:20px;"></main>
    </div>
</div>
