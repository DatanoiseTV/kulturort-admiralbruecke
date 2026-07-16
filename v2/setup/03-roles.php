<?php

namespace ProcessWire;

/**
 * Editor role for volunteers: edit content, manage the newsletter,
 * no access to users, modules or settings.
 * Run on the server: php setup/03-roles.php (from the PW root).
 */

include __DIR__ . '/../index.php';

wire('users')->setCurrentUser(wire('users')->get('djam'));

$roles       = wire('roles');
$permissions = wire('permissions');
$templates   = wire('templates');

$role = $roles->get('redaktion');
if (!$role || !$role->id) {
    $role = $roles->add('redaktion');
    echo "role: redaktion created\n";
}

foreach (['page-view', 'page-edit', 'newsletter-admin', 'page-sort', 'page-move'] as $permissionName) {
    $permission = $permissions->get($permissionName);
    if ($permission && $permission->id && !$role->hasPermission($permission)) {
        $role->addPermission($permission);
        echo "permission: $permissionName\n";
    }
}
$role->save();

$contentTemplates = ['home', 'ort', 'djam', 'bilder', 'termine', 'news', 'newspost',
                     'chronik', 'aufruf', 'zitat', 'dokumente', 'dokument',
                     'feedback', 'anmeldung', 'textsection'];

foreach ($contentTemplates as $templateName) {
    $template = $templates->get($templateName);
    if (!$template) {
        continue;
    }
    $template->useRoles = 1;
    $viewRoles = $template->roles;
    $guestRole = wire('roles')->get('guest');
    if (!$viewRoles->has($guestRole)) {
        $viewRoles->add($guestRole);
    }
    if (!$viewRoles->has($role)) {
        $viewRoles->add($role);
    }
    $template->roles = $viewRoles;
    $editRoles = $template->editRoles;
    if (!in_array($role->id, $editRoles)) {
        $editRoles[] = $role->id;
        $template->editRoles = $editRoles;
    }
    $template->save();
}
echo "edit access granted on content templates\n";

// German admin translation pack (best effort)
$default = wire('languages')->getDefault();
if ($default->language_files->count() < 5) {
    $zipPath = sys_get_temp_dir() . '/pw-lang-de.zip';
    $data = @file_get_contents('https://github.com/jmartsch/pw-lang-de/archive/refs/heads/main.zip');
    if ($data !== false) {
        file_put_contents($zipPath, $data);
        $extractDir = sys_get_temp_dir() . '/pw-lang-de';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($extractDir);
            $zip->close();
            $jsonFiles = glob($extractDir . '/*/site/assets/files/*/*.json')
                ?: glob($extractDir . '/*/*.json');
            $default->of(false);
            $added = 0;
            foreach ($jsonFiles as $jsonFile) {
                $default->language_files->add($jsonFile);
                $added++;
            }
            $default->save();
            echo "language pack: $added files\n";
        }
    } else {
        echo "language pack: download failed (skipped)\n";
    }
}

echo "roles done\n";
