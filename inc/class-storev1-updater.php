<?php
defined('ABSPATH') || exit;

final class StoreV1_Updater {
    const REPO = 'https://github.com/DouglasReisofc/storev1-theme';
    const API = 'https://api.github.com/repos/DouglasReisofc/storev1-theme/releases/latest';
    const SLUG = 'storev1-theme';
    const RELEASE_CACHE = 'storev1_latest_release_v2';

    public static function boot() {
        add_filter('update_themes_github.com', [__CLASS__, 'check'], 10, 4);
        // WordPress builds the update screen from this transient. Injecting
        // here keeps the updater reliable even when another updater does not
        // invoke the host-specific filter.
        add_filter('pre_set_site_transient_update_themes', [__CLASS__, 'inject'], 10, 1);
    }

    private static function release($force = false) {
        $release = $force ? false : get_transient(self::RELEASE_CACHE);
        if (false !== $release && is_array($release)) return $release;
        $response = wp_remote_get(self::API, [
            'timeout' => 10,
            'headers' => ['Accept'=>'application/vnd.github+json','User-Agent'=>'StoreV1-Theme-Updater'],
        ]);
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) return [];
        $release = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($release)) return [];
        set_transient(self::RELEASE_CACHE, $release, HOUR_IN_SECONDS);
        return $release;
    }

    private static function update_data($force = false) {
        $release = self::release($force);
        if (empty($release['tag_name']) || !empty($release['draft']) || !empty($release['prerelease'])) return [];
        $version = ltrim((string) $release['tag_name'], 'v');
        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) return [];
        $asset_name = 'storev1-theme-' . $version . '.zip';
        foreach (isset($release['assets']) && is_array($release['assets']) ? $release['assets'] : [] as $asset) {
            if (!empty($asset['name']) && $asset_name === $asset['name']) {
                return [
                    'theme'=>self::stylesheet(), 'version'=>$version,
                    'new_version'=>$version, 'url'=>self::REPO . '/releases/tag/v' . $version,
                    'package'=>self::REPO . '/releases/download/v' . $version . '/' . $asset_name,
                    'requires'=>'6.1', 'requires_php'=>'7.4',
                ];
            }
        }
        return [];
    }

    public static function inject($transient) {
        if (!is_object($transient)) return $transient;
        $stylesheet = self::stylesheet();
        $current = wp_get_theme($stylesheet)->get('Version');
        $force = is_admin() && current_user_can('update_themes') && isset($_GET['force-check']);
        $update = self::update_data($force);
        if ($update && version_compare($current, $update['version'], '<')) {
            if (!isset($transient->response) || !is_array($transient->response)) $transient->response = [];
            $update['theme'] = $stylesheet;
            $transient->response[$stylesheet] = $update;
        }
        return $transient;
    }

    private static function stylesheet() {
        $stylesheet = function_exists('get_stylesheet') ? get_stylesheet() : '';
        return $stylesheet ?: self::SLUG;
    }

    public static function check($update, $theme_data, $stylesheet, $locales) {
        if (self::stylesheet() !== $stylesheet && self::SLUG !== $stylesheet) return $update;
        $release = get_transient(self::RELEASE_CACHE);
        $force = is_admin() && current_user_can('update_themes') && isset($_GET['force-check']);
        if (false === $release || $force) {
            $release = self::release($force);
        }
        if (empty($release['tag_name']) || !empty($release['draft']) || !empty($release['prerelease'])) return $update;
        $version = ltrim($release['tag_name'], 'v');
        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) return $update;
        foreach (isset($release['assets']) && is_array($release['assets']) ? $release['assets'] : [] as $asset) {
            $expected = self::REPO . '/releases/download/v' . $version . '/storev1-theme-' . $version . '.zip';
            if (isset($asset['name'], $asset['browser_download_url']) &&
                'storev1-theme-' . $version . '.zip' === $asset['name'] &&
                $expected === $asset['browser_download_url']) {
                return [
                    'id'=>self::REPO, 'theme'=>$stylesheet, 'version'=>$version,
                    'url'=>self::REPO . '/releases/tag/v' . $version,
                    'package'=>$expected, 'requires'=>'6.1', 'requires_php'=>'7.4',
                ];
            }
        }
        return $update;
    }
}
StoreV1_Updater::boot();
