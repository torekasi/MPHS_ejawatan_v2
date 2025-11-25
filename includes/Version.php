<?php
/**
 * @FileID: version_helper
 * @Module: Versioning
 * @Author: Nefi
 * @LastModified: 2025-11-25
 * @SecurityTag: validated
 */

declare(strict_types=1);

function version_file_path(): string {
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'version.json';
}

function get_app_version_components(): array {
    $path = version_file_path();
    if (!is_file($path)) {
        return ['major' => 2, 'minor' => 0, 'patch' => 1];
    }
    $raw = @file_get_contents($path);
    if ($raw === false) {
        return ['major' => 2, 'minor' => 0, 'patch' => 1];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return ['major' => 2, 'minor' => 0, 'patch' => 1];
    }
    $major = isset($data['major']) ? (int)$data['major'] : 2;
    $minor = isset($data['minor']) ? (int)$data['minor'] : 0;
    $patch = isset($data['patch']) ? (int)$data['patch'] : 1;
    if ($major < 0 || $minor < 0 || $patch < 1) {
        $major = 2; $minor = 0; $patch = 1;
    }
    return ['major' => $major, 'minor' => $minor, 'patch' => $patch];
}

function get_app_version_label(): string {
    $v = get_app_version_components();
    return 'ejawatan v' . $v['major'] . '.' . $v['minor'] . '.' . $v['patch'];
}

function bump_version_for_push(): array {
    $v = get_app_version_components();
    $v['patch'] += 1;
    if ($v['patch'] > 30) {
        $v['minor'] += 1;
        $v['patch'] = 1;
    }
    $json = json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    @file_put_contents(version_file_path(), $json);
    return $v;
}

